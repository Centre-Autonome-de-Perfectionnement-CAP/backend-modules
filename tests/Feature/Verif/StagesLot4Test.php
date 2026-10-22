<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageReport;
use App\Modules\Stages\Models\StageReportVersion;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\ReportService;
use App\Modules\Stages\Services\StageSettingsService;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\ReportPlan;
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

/** Module Stages - Lot 4 : rapports périodiques et final. */
class StagesLot4Test extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, array{channel: string, to: ?string, text: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Storage::fake('local');
        Cache::flush();
        DB::table('stage_settings')->delete();
        $test = $this;
        $this->app->instance(Notifier::class, new class($test) extends Notifier {
            public function __construct(private $test) {}
            public function whatsapp(?string $phone, string $message, string $context, string $module, bool $bulk = false): void
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
            'account_id' => $account->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addYear(), 'created_at' => now(),
        ]);

        return [$req, ['X-Stagiaire-Token' => $token, 'Accept' => 'application/json']];
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('rapport.pdf', 300, 'application/pdf');
    }

    // ── Tests ───────────────────────────────────────────────────────────

    public function test_weekly_plan_windows_and_final(): void
    {
        $plan = ReportPlan::periods(CarbonImmutable::parse('2026-11-04'), CarbonImmutable::parse('2026-12-31'), 'hebdomadaire', 2, 14);
        $first = $plan[0];
        $this->assertSame(['S2026-W45', '2026-11-04', '2026-11-06', '2026-11-06', '2026-11-10'],
            [$first['key'], $first['start'], $first['end'], $first['opens'], $first['closes']]);
        $final = end($plan);
        $this->assertSame(['FINAL', '2026-12-17', '2026-12-31'], [$final['key'], $final['opens'], $final['closes']]);
        $this->assertTrue(collect($plan)->every(fn ($p) => $p['kind'] === 'final' || $p['start'] < '2026-12-17'));

        $daily = ReportPlan::periods(CarbonImmutable::parse('2026-11-06'), CarbonImmutable::parse('2026-12-31'), 'journalier', 1, 14);
        $this->assertSame(['J2026-11-06', 'J2026-11-09'], [$daily[0]['key'], $daily[1]['key']], 'pas de rapport le week-end');
        $this->assertSame('2026-11-09', $daily[0]['closes']);
    }

    public function test_submission_versions_lateness_and_isolation(): void
    {
        [$req, $h] = $this->intern();
        $this->lead();

        $this->at('2026-11-05 10:00');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)
            ->assertStatus(422)->assertJsonPath('error_code', 'STAGE_REPORT_NOT_OPEN');

        $this->at('2026-11-06 16:00');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => UploadedFile::fake()->create('r.docx', 30)], $h)
            ->assertStatus(422);
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf(), 'note' => 'Semaine d\'intégration'], $h)
            ->assertStatus(201)->assertJsonPath('data.current_version', 1)->assertJsonPath('data.late', false)->json('data.id');
        Queue::assertPushed(SendNotificationJob::class);

        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)
            ->assertStatus(201)->assertJsonPath('data.current_version', 2);
        $this->assertCount(2, $this->getJson("/api/stagiaire/rapports/{$id}", $h)->assertOk()->json('data.versions'));
        $this->getJson("/api/stagiaire/rapports/{$id}/versions/1", $h)->assertOk()->assertJsonStructure(['content']);

        // Hors délai : accepté, marqué « en retard »
        $this->at('2026-11-18 09:00');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W46', 'fichier' => $this->pdf()], $h)
            ->assertStatus(201)->assertJsonPath('data.late', true);

        $ov = $this->getJson('/api/stagiaire/rapports', $h)->assertOk()->json('data');
        $this->assertSame('hebdomadaire', $ov['frequency']);
        $states = collect($ov['items'])->pluck('state', 'key');
        $this->assertSame('deposited', $states['S2026-W45']);
        $this->assertSame('upcoming', $states['S2026-W47'], 'semaine en cours : dépôt ouvert le vendredi');

        // Un autre stagiaire ne voit pas ce rapport
        [, $other] = $this->intern();
        $this->getJson("/api/stagiaire/rapports/{$id}", $other)->assertStatus(404);
    }

    private function lead(): User
    {
        return $this->user('responsable-division', 'formation_continue');
    }

    public function test_review_flow_supervisor_then_lead_last_word(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->lead();
        $sup = $this->user('superviseur');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $req->id, 'user_id' => $sup->id, 'created_at' => now()]);

        $this->at('2026-11-06 16:00');
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)->json('data.id');

        $this->actingAs($sup, 'sanctum');
        $this->assertSame([$id], array_column($this->getJson('/api/stages/admin/rapports?mine=1')->assertOk()->json('data'), 'id'));
        $this->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'rejected'])->assertStatus(422);
        $this->postJson("/api/stages/admin/rapports/{$id}/commentaires", ['body' => 'Ajoute le schéma réseau'])->assertOk();
        $this->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])->assertOk()
            ->assertJsonPath('data.status', 'supervisor_approved')->assertJsonPath('data.validated', true);

        // Validé : le stagiaire ne peut plus le remplacer
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)
            ->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_LOCKED');

        // Le responsable revient sur la décision
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'rejected', 'comment' => 'Trop court'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])
            ->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_LEAD_DECIDED');

        // Nouvelle version → de nouveau à examiner ; le stagiaire répond
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)->assertStatus(201);
        $this->postJson("/api/stagiaire/rapports/{$id}/commentaires", ['body' => 'Schéma ajouté page 4'], $h)->assertOk();
        $d = $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])
            ->assertOk()->json('data');
        $this->assertSame('approved', $d['status']);
        $this->assertSame(['superviseur', 'responsable', 'responsable'], array_column($d['reviews'], 'role'));
        $this->assertSame(['staff', 'stagiaire'], array_column($d['comments'], 'author'));
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'Rapport validé')));
    }

    public function test_final_report_validation_flag(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->lead();
        $svc = app(ReportService::class);

        $this->at('2026-12-16 10:00');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(422);
        $this->at('2026-12-18 10:00');
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(201)->json('data.id');
        $this->assertFalse($svc->finalValidated($req));
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])->assertOk();
        $this->assertTrue($svc->finalValidated($req));
        $this->getJson("/api/stages/admin/stagiaires/{$req->id}/rapports")->assertOk()->assertJsonPath('data.stats.final_validated', true);
    }

    public function test_final_report_corrected_after_defense(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->lead();
        $svc = app(ReportService::class);

        $this->at('2026-12-18 10:00');
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(201)->json('data.id');
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])->assertOk();
        $this->assertSame(1, StageReport::find($id)->official_version);

        // Après la soutenance (stage terminé) : version corrigée, la validation reste acquise
        $this->at('2027-01-20 10:00');
        $token = 'stg_' . Str::random(60); // le stagiaire se reconnecte
        DB::table('stage_intern_sessions')->insert([
            'account_id' => StageInternAccount::where('stage_request_id', $req->id)->value('id'),
            'token_hash' => hash('sha256', $token), 'expires_at' => now()->addYear(), 'created_at' => now(),
        ]);
        $h['X-Stagiaire-Token'] = $token;
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf(), 'note' => 'Corrections du jury'], $h)
            ->assertStatus(201)->assertJsonPath('data.correction_status', 'pending')->assertJsonPath('data.validated', true);
        $this->assertTrue($svc->finalValidated($req));
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(409);
        $this->getJson('/api/stages/admin/rapports?filter=to_review')->assertOk()->assertJsonFragment(['id' => $id]);

        // Non retenue : la version validée précédente fait toujours foi
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'rejected', 'comment' => 'Annexe manquante'])->assertOk();
        $r = StageReport::find($id);
        $this->assertSame(['rejected', 1, 'approved'], [$r->correction_status, $r->official_version, $r->status]);
        $this->assertTrue($svc->finalValidated($req));

        // Nouvelle version corrigée, validée : elle devient officielle
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(201);
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])->assertOk();
        $this->assertSame(3, StageReport::find($id)->official_version);
        $this->assertTrue((bool) StageReportVersion::where('report_id', $id)->where('version', 3)->value('post_defense'));

        // Délai dépassé (90 jours après la fin du stage)
        $this->at('2027-04-01 10:00');
        DB::table('stage_intern_sessions')->where('token_hash', hash('sha256', $token))->update(['expires_at' => now()->addDay()]);
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(409);
    }

    public function test_reminders_once_and_daily_digest(): void
    {
        [$req] = $this->intern();
        $this->lead();

        $this->at('2026-11-06 08:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->assertSame(1, collect($this->sent)->where('channel', 'whatsapp')->filter(fn ($s) => str_contains($s['text'], 'Rapport à déposer'))->count());

        $this->at('2026-11-10 08:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'Dernier jour')));

        // Fréquence journalière : récapitulatif du soir au responsable
        app(StageSettingsService::class)->update(['report_frequency' => 'journalier'], null);
        [, $h2] = $this->intern('2026-11-09', '2026-12-31');
        $this->at('2026-11-10 12:00');
        Queue::fake();
        $this->post('/api/stagiaire/rapports', ['period_key' => 'J2026-11-10', 'fichier' => $this->pdf()], $h2)->assertStatus(201);
        Queue::assertNothingPushed(); // pas de message par dépôt journalier
        $this->at('2026-11-10 17:45');
        $this->artisan('stages:process-reports --digest')->assertSuccessful();
        Queue::assertPushed(SendNotificationJob::class, fn ($j) => str_contains(json_encode($j->payload, JSON_UNESCAPED_UNICODE), 'Rapports journaliers'));
    }
}
