<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\AttestationService;
use App\Modules\Stages\Models\StageAttestation;
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

/** Module Stages — Lot 5 : attestations. */
class StagesLot5Test extends TestCase
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

    private function lead(): User
    {
        return $this->user('responsable-division', 'formation_continue');
    }

    /** Présent tous les jours ouvrés du $from au $to, sauf les jours listés. */
    private function attend(StageRequest $req, string $from, string $to, array $absent = []): void
    {
        for ($d = CarbonImmutable::parse($from); $d->lessThanOrEqualTo(CarbonImmutable::parse($to)); $d = $d->addDay()) {
            if ($d->isWeekend() || in_array($d->toDateString(), $absent, true)) {
                continue;
            }
            foreach (['arrivee' => '08:00', 'sortie' => '18:00'] as $k => $hm) {
                DB::table('stage_attendances')->insert([
                    'stage_request_id' => $req->id, 'day' => $d->toDateString(), 'kind' => $k,
                    'at' => CarbonImmutable::parse($d->toDateString() . " {$hm}", BusinessHours::TZ)->utc(), 'created_at' => now(),
                ]);
            }
        }
    }

    private function finalReport(StageRequest $req, string $status = 'approved'): void
    {
        DB::table('stage_reports')->insert([
            'stage_request_id' => $req->id, 'kind' => 'final', 'period_key' => 'FINAL', 'current_version' => 1,
            'status' => $status, 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ── Tests ───────────────────────────────────────────────────────────

    public function test_prepare_day_moves_to_friday_and_tick_prepares_once(): void
    {
        $svc = app(AttestationService::class);
        // Fin le lundi 30 nov. → J-7 = lundi 23 ; fin le dimanche 6 déc. → J-7 = dimanche 29 → vendredi 27
        [$a] = $this->intern('2026-11-02', '2026-11-30');
        [$b] = $this->intern('2026-11-02', '2026-12-06');
        $this->assertSame('2026-11-23', $svc->prepareDay($a)->toDateString());
        $this->assertSame('2026-11-27', $svc->prepareDay($b)->toDateString());
        $this->lead();

        $this->at('2026-11-20 08:15');
        $svc->tick();
        $this->assertSame(0, StageAttestation::whereIn('stage_request_id', [$a->id, $b->id])->count());

        $this->at('2026-11-23 08:15');
        $svc->tick();
        $this->assertSame(1, StageAttestation::where('stage_request_id', $a->id)->where('status', 'a_valider')->count());
        $this->assertSame(0, StageAttestation::where('stage_request_id', $b->id)->count());

        // Rattrapage : la tâche n'est pas passée le vendredi 27
        $this->at('2026-11-30 08:15');
        $svc->tick();
        $svc->tick();
        $this->assertSame(1, StageAttestation::where('stage_request_id', $b->id)->count());
        $this->assertSame(1, StageAttestation::where('stage_request_id', $a->id)->count(), 'jamais en double');
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === $a->whatsapp && str_contains($m['text'], 'attestation')));
    }

    public function test_validation_requires_conditions_then_print_and_delivery(): void
    {
        [$req, $h] = $this->intern('2026-11-02', '2026-11-27');
        $lead = $this->lead();
        $sec = $this->user('secretaire');

        $this->at('2026-11-20 10:00');
        $this->attend($req, '2026-11-02', '2026-11-19', ['2026-11-03', '2026-11-04', '2026-11-05', '2026-11-06']);
        $this->actingAs($lead, 'sanctum');
        $id = $this->postJson("/api/stages/admin/stagiaires/{$req->id}/attestation")->assertOk()->json('data.id');
        $this->assertSame($id, $this->postJson("/api/stages/admin/stagiaires/{$req->id}/attestation")->json('data.id'), 'une seule en cours');

        // Assiduité 10/14 (71 %) et pas de rapport final : refus
        $this->postJson("/api/stages/admin/attestations/{$id}/valider")->assertStatus(422)->assertJsonPath('error_code', 'STAGE_ATT_NOT_ELIGIBLE');

        // Le secrétariat ne voit pas encore cette attestation, ni les décisions
        $this->actingAs($sec, 'sanctum');
        $this->getJson("/api/stages/admin/attestations/{$id}")->assertStatus(404);
        $this->postJson("/api/stages/admin/attestations/{$id}/valider")->assertStatus(403);
        $this->getJson('/api/stages/admin/demandes')->assertStatus(403);

        // Absences justifiées + rapport final validé
        foreach (['2026-11-03', '2026-11-04', '2026-11-05'] as $d) {
            DB::table('stage_intern_requests')->insert(['stage_request_id' => $req->id, 'kind' => 'permission', 'type' => 'maladie', 'motif' => 'x',
                'date_from' => $d, 'date_to' => $d, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->finalReport($req);
        $this->actingAs($lead, 'sanctum');
        $this->putJson("/api/stages/admin/attestations/{$id}", ['structure' => '  du <b>Service</b> Informatique du CAP ', 'type_stage' => 'professionnel'])
            ->assertOk()->assertJsonPath('data.content.structure', 'du Service Informatique du CAP')
            ->assertJsonPath('data.content.type_stage', 'professionnel')->assertJsonPath('data.content.pays_naissance', 'Rép. du BENIN');
        $this->postJson("/api/stages/admin/attestations/{$id}/valider")->assertOk()
            ->assertJsonPath('data.status', 'a_imprimer')->assertJsonPath('data.eligibility.eligible', true);
        $this->putJson("/api/stages/admin/attestations/{$id}", ['type_stage' => 'x'])->assertStatus(409);
        Queue::assertPushed(SendNotificationJob::class); // secrétariat prévenu (heures ouvrées)

        // Secrétariat : numéro modifié, impression, remise
        $this->actingAs($sec, 'sanctum');
        $this->assertSame([$id], array_column($this->getJson('/api/stages/admin/attestations')->assertOk()->json('data'), 'id'));
        $this->postJson("/api/stages/admin/attestations/{$id}/remise", ['date' => '2026-11-20'])->assertStatus(409);
        $this->postJson("/api/stages/admin/attestations/{$id}/imprimee", ['numero' => '14-26', 'date' => '2026-11-19'])->assertOk()
            ->assertJsonPath('data.status', 'imprimee')->assertJsonPath('data.numero', '14-26')->assertJsonPath('data.document_date', '2026-11-19');
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === $req->whatsapp && str_contains($m['text'], 'retirer')));
        $this->postJson("/api/stages/admin/attestations/{$id}/remise", ['date' => '2026-11-25'])->assertStatus(422);
        $this->postJson("/api/stages/admin/attestations/{$id}/remise", ['date' => '2026-11-20'])->assertOk()
            ->assertJsonPath('data.status', 'remise')->assertJsonPath('data.delivered_on', '2026-11-20');
        $this->post("/api/stages/admin/attestations/{$id}/scan", ['file' => UploadedFile::fake()->create('scan.pdf', 200, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.has_scan', true);
        $this->getJson("/api/stages/admin/attestations/{$id}/scan")->assertOk()->assertJsonStructure(['content']);

        // PDF : texte (polices intégrées), numéro imprimé
        $pdf = base64_decode($this->getJson("/api/stages/admin/attestations/{$id}/pdf")->assertOk()->assertJsonPath('mime', 'application/pdf')->json('content'));
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('Albertus', $pdf);

        // Numéro déjà pris par un autre stagiaire
        [$other] = $this->intern('2026-11-02', '2026-11-27');
        $this->attend($other, '2026-11-02', '2026-11-19');
        $this->finalReport($other);
        $this->actingAs($lead, 'sanctum');
        $id2 = $this->postJson("/api/stages/admin/stagiaires/{$other->id}/attestation")->json('data.id');
        $this->postJson("/api/stages/admin/attestations/{$id2}/valider")->assertOk();
        $this->postJson("/api/stages/admin/attestations/{$id2}/imprimee", ['date' => '2026-10-01'])->assertStatus(422)->assertJsonPath('error_code', 'STAGE_ATT_DATE');
        $this->postJson("/api/stages/admin/attestations/{$id2}/imprimee", ['numero' => '14-26'])
            ->assertStatus(409)->assertJsonPath('error_code', 'STAGE_ATT_NUMBER_TAKEN');
        $this->postJson("/api/stages/admin/attestations/{$id2}/imprimee", [])->assertOk()
            ->assertJsonPath('data.numero', null)->assertJsonPath('data.document_date', '2026-11-20');
    }

    public function test_exceptional_requires_password_and_motif(): void
    {
        [$req] = $this->intern('2026-11-02', '2026-11-27');
        $lead = $this->lead();
        $sup = $this->user('superviseur');
        $this->at('2026-11-24 10:00');

        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/stagiaires/{$req->id}/attestation-exceptionnelle", ['password' => 'x', 'motif' => 'Stage interrompu pour raison de santé'])
            ->assertStatus(403);
        $this->actingAs($lead, 'sanctum');
        $this->postJson("/api/stages/admin/stagiaires/{$req->id}/attestation-exceptionnelle", ['password' => 'mauvais', 'motif' => 'Stage interrompu pour raison de santé'])
            ->assertStatus(422)->assertJsonPath('error_code', 'STAGE_ATT_PASSWORD');
        $this->postJson("/api/stages/admin/stagiaires/{$req->id}/attestation-exceptionnelle", ['password' => 'x', 'motif' => 'court'])->assertStatus(422);
        $this->postJson("/api/stages/admin/stagiaires/{$req->id}/attestation-exceptionnelle", ['password' => 'x', 'motif' => 'Stage interrompu pour raison de santé'])
            ->assertOk()->assertJsonPath('data.kind', 'exceptionnelle')->assertJsonPath('data.status', 'a_imprimer')
            ->assertJsonPath('data.eligibility.eligible', false);
        $this->postJson("/api/stages/admin/stagiaires/{$req->id}/attestation-exceptionnelle", ['password' => 'x', 'motif' => 'Stage interrompu pour raison de santé'])
            ->assertStatus(409);
        $this->assertTrue(DB::table('stage_request_history')->where('stage_request_id', $req->id)->where('action', 'attestation_exceptional')->exists());
    }

    public function test_intern_request_after_end_and_reset_on_end_date_change(): void
    {
        [$req, $h] = $this->intern('2026-11-02', '2026-11-27');
        $lead = $this->lead();

        $this->at('2026-11-20 10:00');
        $this->getJson('/api/stagiaire/attestation', $h)->assertOk()->assertJsonPath('data.can_request', false);
        $this->postJson('/api/stagiaire/attestation', ['motif_type' => 'perte'], $h)->assertStatus(422)->assertJsonPath('error_code', 'STAGE_ATT_TOO_EARLY');

        $this->at('2026-12-10 10:00');
        $this->postJson('/api/stagiaire/attestation', ['motif_type' => 'autre'], $h)->assertStatus(422);
        $this->postJson('/api/stagiaire/attestation', ['motif_type' => 'non_recue', 'motif' => 'Jamais reçue'], $h)
            ->assertStatus(201)->assertJsonPath('data.items.0.status', 'a_valider')->assertJsonPath('data.can_request', false);
        $this->postJson('/api/stagiaire/attestation', ['motif_type' => 'perte'], $h)->assertStatus(409);
        $a = StageAttestation::where('stage_request_id', $req->id)->first();
        $this->assertSame('demande', $a->kind);

        // Validée puis date de fin rectifiée : à valider de nouveau
        $this->attend($req, '2026-11-02', '2026-11-27');
        $this->finalReport($req);
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/attestations/{$a->id}/valider")->assertOk();
        app(\App\Modules\Stages\Services\StageRequestService::class)->rectify($req->fresh(), [
            'service' => $req->service, 'lieu' => $req->lieu, 'date_debut' => '2026-11-02', 'date_fin' => '2026-11-26', 'horaires' => $req->horaires,
        ], 'Correction de la date de fin', $lead);
        $this->assertSame('a_valider', $a->fresh()->status);
        $this->assertNull($a->fresh()->content);

        // Annulation par le responsable (motif obligatoire)
        $this->postJson("/api/stages/admin/attestations/{$a->id}/annuler", [])->assertStatus(422);
        $this->postJson("/api/stages/admin/attestations/{$a->id}/annuler", ['motif' => 'Doublon de dossier'])->assertOk()->assertJsonPath('data.status', 'annulee');
        $this->getJson('/api/stagiaire/attestation', $h)->assertOk()->assertJsonPath('data.can_request', true);
    }

    public function test_reminders_first_then_mondays_only(): void
    {
        [$req] = $this->intern('2026-11-02', '2026-11-27');
        $this->lead();
        $this->user('secretaire');
        $svc = app(AttestationService::class);
        $this->attend($req, '2026-11-02', '2026-11-27');
        $this->finalReport($req);

        $this->at('2026-11-23 08:15');
        $svc->tick();
        $a = StageAttestation::where('stage_request_id', $req->id)->firstOrFail();
        $this->assertNull($a->fresh()->reminded_at, 'pas de relance avant la fin du stage');

        $this->at('2026-11-27 08:15');
        $this->assertSame(1, $svc->tick()['reminded']);
        $this->at('2026-11-30 08:15'); // lundi
        $this->assertSame(1, $svc->tick()['reminded']);
        $this->at('2026-12-01 08:15');
        $this->assertSame(0, $svc->tick()['reminded']);
    }
}
