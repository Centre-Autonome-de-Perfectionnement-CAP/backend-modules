<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageReport;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\StageSettingsService;
use App\Modules\Stages\Support\BusinessHours;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Module Stages : une version de rapport se décide une fois par rôle ; le responsable peut revenir en arrière avec un motif. */
class StagesReportDecisionsTest extends TestCase
{
    use InternDevice;
    use DatabaseTransactions;

    /** @var array<int, array{channel: string, to: ?string, text: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInternDevice();
        Queue::fake();
        Storage::fake('local');
        Cache::flush();
        DB::table('stage_settings')->delete();
        $test = $this;
        $this->app->instance(Notifier::class, new class($test) extends Notifier {
            public function __construct(private $test) {}
            public function whatsapp(?string $phone, string $message, string $context, string $module, bool $bulk = false, array $fallbackEmail = [], ?\DateTimeInterface $at = null): void
            {
                $this->test->record('whatsapp', $phone, $message);
            }
            public function email(?string $to, string $subject, string $view, array $vars = [], array $attachments = [], ?string $toName = null, array $cc = [], bool $bulk = false): void
            {
                $this->test->record('email', $to, $subject);
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function record(string $channel, ?string $to, string $text): void
    {
        $this->sent[] = ['channel' => $channel, 'to' => $to, 'text' => $text];
    }

    private function at(string $local): void
    {
        $t = CarbonImmutable::parse($local, BusinessHours::TZ)->utc();
        Carbon::setTestNow($t);
        CarbonImmutable::setTestNow($t);
    }

    private function user(string $slug, ?string $type = null): User
    {
        $roleId = DB::table('roles')->where('slug', $slug)->value('id')
            ?? DB::table('roles')->insertGetId(['uuid' => Str::uuid(), 'name' => $slug, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('users')->insertGetId([
            'uuid' => Str::uuid(), 'last_name' => strtoupper($slug), 'first_name' => 'Test', 'email' => $slug . uniqid() . '@example.org',
            'password' => bcrypt('x'), 'phone' => '0197' . random_int(100000, 999999), 'chef_division_type' => $type,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_user')->insert(['role_id' => $roleId, 'user_id' => $id]);

        return User::find($id);
    }

    private function lead(): User
    {
        return $this->user('responsable-division', 'formation_continue');
    }

    /** Stagiaire activé (règlement accepté) + en-tête de session. */
    private function intern(string $start = '2026-11-02', string $end = '2026-12-31'): array
    {
        $req = StageRequest::create([
            'reference' => 'STG-' . strtoupper(Str::random(6)), 'status' => 'accepted', 'civilite' => 'M.',
            'nom' => 'AHO', 'prenoms' => 'Luc', 'date_naissance' => '2002-01-01', 'lieu_naissance' => 'Cotonou',
            'nationalite' => 'Béninoise', 'etablissement_origine' => 'UAC', 'filiere_niveau' => 'L3',
            'duree_souhaitee' => '2_mois', 'email' => uniqid() . '@example.org', 'whatsapp' => '+2290196' . random_int(100000, 999999),
            'files' => [], 'consent_at' => now(), 'service' => 'Service Informatique', 'lieu' => 'CAP-EPAC',
            'date_debut' => $start, 'date_fin' => $end, 'date_debut_reelle' => $start, 'accepted_at' => now(), 'activated_at' => now(),
            'horaires' => ['arrivee' => '08:00', 'pause_debut' => '13:00', 'pause_fin' => '15:00', 'sortie' => '18:00'],
        ]);
        $account = StageInternAccount::create([
            'stage_request_id' => $req->id, 'password' => 'Stage2026',
            'reglement_version' => app(StageSettingsService::class)->get('reglement_version'), 'reglement_accepted_at' => now(),
        ]);
        $token = 'stg_' . Str::random(60);
        DB::table('stage_intern_sessions')->insert([
            'account_id' => $account->id, 'token_hash' => hash('sha256', $token), 'device_hash' => $this->deviceHash(), 'expires_at' => now()->addYear(), 'created_at' => now(),
        ]);

        return [$req, ['X-Stagiaire-Token' => $token, 'Accept' => 'application/json']];
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('rapport.pdf', 300, 'application/pdf');
    }

    private function decide(int $id, string $decision, ?string $comment = null)
    {
        return $this->postJson("/api/stages/admin/rapports/{$id}/decision", array_filter(['decision' => $decision, 'comment' => $comment]));
    }

    private function supervise(StageRequest $req, User $sup): void
    {
        DB::table('stage_supervisions')->insert(['stage_request_id' => $req->id, 'user_id' => $sup->id, 'created_at' => now()]);
    }

    public function test_same_decision_is_refused_twice_and_supervisors_do_not_override_each_other(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->lead();
        $sup1 = $this->user('superviseur');
        $sup2 = $this->user('superviseur');
        $this->supervise($req, $sup1);
        $this->supervise($req, $sup2);
        $this->at('2026-11-06 16:00');
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)->assertStatus(201)->json('data.id');

        $this->actingAs($sup1, 'sanctum');
        $this->decide($id, 'approved')->assertOk()->assertJsonPath('data.status', 'supervisor_approved');
        $validated = fn () => collect($this->sent)->filter(fn ($s) => str_contains($s['text'], 'Rapport validé'))->count();
        $before = $validated();

        // Même décision une seconde fois, ou autre superviseur après lui : refusé, rien n'est enregistré ni notifié
        $this->decide($id, 'approved')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_ALREADY_DECIDED');
        $this->actingAs($sup2, 'sanctum')->decide($id, 'rejected', 'Trop court')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_ALREADY_DECIDED');
        $this->assertSame(1, DB::table('stage_report_reviews')->where('report_id', $id)->count());
        $this->assertSame($before, $validated());

        // Le responsable tranche une fois ; la même décision une seconde fois est refusée
        $this->actingAs($lead, 'sanctum')->decide($id, 'approved')->assertOk()->assertJsonPath('data.status', 'approved');
        $this->decide($id, 'approved')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_ALREADY_DECIDED');
        $this->assertSame(2, DB::table('stage_report_reviews')->where('report_id', $id)->count());
        // Les superviseurs ne reviennent plus sur une version tranchée par le responsable
        $this->actingAs($sup1, 'sanctum')->decide($id, 'rejected', 'Finalement non')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_LEAD_DECIDED');
    }

    public function test_lead_can_send_back_a_validated_report_and_screen_options_follow(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->lead();
        $sup = $this->user('superviseur');
        $this->supervise($req, $sup);
        $this->at('2026-11-06 16:00');
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)->assertStatus(201)->json('data.id');
        $options = fn (User $u) => $this->actingAs($u, 'sanctum')->getJson("/api/stages/admin/rapports/{$id}")->assertOk()->json('data.decisions');

        // Avant toute décision : tout est permis, rien à retirer
        $this->assertSame(['approve' => true, 'reject' => true, 'reverse' => false], $options($lead));
        $this->decide($id, 'approved')->assertOk();
        // Validé : il ne reste que « renvoyer à corriger » ; les superviseurs ne peuvent plus rien
        $this->assertSame(['approve' => false, 'reject' => true, 'reverse' => true], $options($lead));
        $this->assertSame(['approve' => false, 'reject' => false, 'reverse' => false], $options($sup));
        $this->actingAs($lead, 'sanctum')->decide($id, 'rejected')->assertStatus(422)->assertJsonPath('error_code', 'STAGE_REPORT_REJECT_COMMENT');
        $this->decide($id, 'rejected', 'Il manque le planning')->assertOk()->assertJsonPath('data.status', 'rejected');

        // En correction : une nouvelle demande reste possible, jamais une validation
        $this->assertSame(['approve' => false, 'reject' => true, 'reverse' => false], $options($lead));
        $this->decide($id, 'approved', 'Reçu par mail')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_IN_CORRECTION');
        $this->decide($id, 'rejected', 'Ajoutez aussi les annexes')->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertSame(3, DB::table('stage_report_reviews')->where('report_id', $id)->count());

        // Une nouvelle version rouvre la validation, une seule fois
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)->assertStatus(201);
        $this->assertSame(['approve' => true, 'reject' => true, 'reverse' => false], $options($lead));
        $this->decide($id, 'approved')->assertOk()->assertJsonPath('data.status', 'approved');
        $this->decide($id, 'approved')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_ALREADY_DECIDED');
    }

    public function test_document_in_correction_takes_new_correction_requests_but_no_validation(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->lead();
        $sup1 = $this->user('superviseur');
        $sup2 = $this->user('superviseur');
        $this->supervise($req, $sup1);
        $this->supervise($req, $sup2);
        $this->at('2026-11-06 16:00');
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)->assertStatus(201)->json('data.id');

        $this->actingAs($sup1, 'sanctum')->decide($id, 'rejected', 'Plan manquant')->assertOk()->assertJsonPath('data.status', 'supervisor_rejected');
        // Un autre superviseur peut redemander une correction, ni lui ni le responsable ne peuvent valider
        $this->actingAs($sup2, 'sanctum')->decide($id, 'rejected', 'Ajoutez les annexes')->assertOk()->assertJsonPath('data.status', 'supervisor_rejected');
        $this->decide($id, 'approved')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_IN_CORRECTION');
        $this->actingAs($lead, 'sanctum')->decide($id, 'approved')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_IN_CORRECTION');
        $this->assertSame(2, DB::table('stage_report_reviews')->where('report_id', $id)->count());

        // La nouvelle version rouvre la validation
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)->assertStatus(201);
        $this->decide($id, 'approved')->assertOk()->assertJsonPath('data.status', 'approved');
    }

    public function test_final_report_decision_is_locked_once_the_attestation_is_validated(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->lead();
        $this->at('2026-12-18 10:00');
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(201)->json('data.id');
        $this->actingAs($lead, 'sanctum')->decide($id, 'approved')->assertOk();

        // Attestation en préparation : le responsable peut encore changer sa décision
        $att = DB::table('stage_attestations')->insertGetId([
            'stage_request_id' => $req->id, 'kind' => 'normale', 'status' => 'a_valider', 'created_at' => now(), 'updated_at' => now(),
        ]);
        // Attestation validée : la validation du rapport final est définitive
        DB::table('stage_attestations')->where('id', $att)->update(['status' => 'a_imprimer']);
        $this->decide($id, 'rejected', 'Annexe manquante')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_LOCKED');
        $this->assertSame('approved', StageReport::find($id)->status);
        $this->getJson("/api/stages/admin/rapports/{$id}")->assertOk()->assertJsonPath('data.decisions.reject', false);

        // Attestation annulée : la décision peut de nouveau être changée
        DB::table('stage_attestations')->where('id', $att)->update(['status' => 'annulee']);
        $this->decide($id, 'rejected', 'Annexe manquante')->assertOk()->assertJsonPath('data.status', 'rejected');
    }

    public function test_corrected_version_after_defense_is_judged_once(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->lead();
        $sup = $this->user('superviseur');
        $this->supervise($req, $sup);
        $this->at('2026-12-18 10:00');
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(201)->json('data.id');
        $this->actingAs($lead, 'sanctum')->decide($id, 'approved')->assertOk();

        // Après la soutenance : version corrigée, le stagiaire se reconnecte
        $this->at('2027-01-20 10:00');
        $token = 'stg_' . Str::random(60);
        DB::table('stage_intern_sessions')->insert([
            'account_id' => StageInternAccount::where('stage_request_id', $req->id)->value('id'),
            'token_hash' => hash('sha256', $token), 'device_hash' => $this->deviceHash(), 'expires_at' => now()->addYear(), 'created_at' => now(),
        ]);
        $h['X-Stagiaire-Token'] = $token;
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(201)->assertJsonPath('data.correction_status', 'pending');

        // Un avis par superviseur sur la version corrigée
        $this->actingAs($sup, 'sanctum')->decide($id, 'approved')->assertOk();
        $this->decide($id, 'approved')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_ALREADY_DECIDED');

        // Le responsable tranche une fois (non retenue) : la version corrigée est jugée, plus rien n'est possible
        $this->actingAs($lead, 'sanctum')->decide($id, 'rejected', 'Annexe manquante')->assertOk();
        $this->decide($id, 'rejected', 'Encore')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_LOCKED');
        $this->decide($id, 'approved', 'Finalement oui')->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_LOCKED');
        $r = StageReport::find($id);
        $this->assertSame(['rejected', 1, 'approved'], [$r->correction_status, $r->official_version, $r->status]);
    }
}
