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

/** Module Stages - Lot 8 : fin anticipée (désistement, désistement constaté, révocation). */
class StagesLot8Test extends TestCase
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

    private function lead(): User
    {
        return $this->user('responsable-division', 'formation_continue');
    }

    private function supervise(StageRequest $req, User $sup): void
    {
        DB::table('stage_supervisions')->insert(['stage_request_id' => $req->id, 'user_id' => $sup->id, 'created_at' => now()]);
    }

    private function attend(StageRequest $req, array $days): void
    {
        foreach ($days as $day) {
            DB::table('stage_attendances')->insert([
                'stage_request_id' => $req->id, 'day' => $day, 'kind' => 'arrivee',
                'at' => CarbonImmutable::parse("{$day} 08:00", BusinessHours::TZ)->utc(), 'created_at' => now(),
            ]);
        }
    }

    public function test_intern_desists_from_space_immediately_with_note_and_lockout(): void
    {
        $this->at('2026-11-12 10:00');
        [$req, $h] = $this->intern();
        $this->attend($req, ['2026-11-09', '2026-11-10']);
        StageAttestation::create(['stage_request_id' => $req->id, 'kind' => 'demande', 'status' => 'a_valider']);

        $this->postJson('/api/stagiaire/fin-de-stage', ['password' => 'mauvais'], $h)->assertStatus(422);
        $this->assertSame('accepted', $req->fresh()->status);

        $this->postJson('/api/stagiaire/fin-de-stage', ['password' => 'Stage2026', 'motif' => 'Stage trouvé ailleurs'], $h)->assertOk();
        $req->refresh();
        $this->assertSame('desiste', $req->status);
        $this->assertSame('2026-11-10', $req->date_fin->toDateString(), 'fin = dernier jour de présence');
        $note = DB::table('stage_notes')->where('stage_request_id', $req->id)->where('kind', 'desistement')->first();
        $this->assertNotNull($note);
        $this->assertSame(0, (int) $note->version);
        Storage::disk('local')->assertExists($note->pdf_path);
        $this->assertSame('annulee', StageAttestation::where('stage_request_id', $req->id)->value('status'));
        $this->getJson('/api/stagiaire/moi', $h)->assertStatus(401);
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['channel'] === 'whatsapp' && $m['to'] === $req->whatsapp && str_contains($m['text'], 'désistement')));

        // Plus d'action possible sur un stage clos
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin", ['type' => 'desistement'])->assertStatus(409);
    }

    public function test_recorded_desist_rights_and_48h_undo(): void
    {
        $this->at('2026-11-20 09:00');
        [$req] = $this->intern();
        $this->attend($req, ['2026-11-10']);
        $sup = $this->user('superviseur');
        $other = $this->user('superviseur');
        $this->supervise($req, $sup);

        $this->actingAs($other, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin", ['type' => 'desistement'])->assertStatus(403);
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin", ['type' => 'desistement'])
            ->assertOk()->assertJsonPath('data.status', 'desiste_constate')->assertJsonPath('data.fin.can_undo', true);
        $snap = json_decode(DB::table('stage_notes')->where('stage_request_id', $req->id)->where('kind', 'desistement')->value('snapshot'), true);
        $this->assertSame(7, $snap['jours_absence'], 'jours ouvrés du 11 au 20 novembre');

        // Annulation dans les 48 h : le stage reprend avec sa fin prévue
        $this->at('2026-11-21 15:00');
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin/annuler")
            ->assertOk()->assertJsonPath('data.status', 'accepted');
        $this->assertSame('2026-12-31', $req->fresh()->date_fin->toDateString());
        $snap = json_decode(DB::table('stage_notes')->where('stage_request_id', $req->id)->where('kind', 'desistement')->value('snapshot'), true);
        $this->assertArrayHasKey('annulee_at', $snap);

        // Nouvelle fin, puis annulation trop tard
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin", ['type' => 'desistement'])->assertOk();
        $this->at('2026-11-23 16:00');
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin/annuler")->assertStatus(409);
    }

    public function test_revocation_needs_motif_and_blocks_any_attestation(): void
    {
        $this->at('2026-11-20 09:00');
        [$req] = $this->intern();
        $lead = $this->lead();

        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin", ['type' => 'revocation'])->assertStatus(422);
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin", [
            'type' => 'revocation', 'motif_type' => 'absences', 'motif' => 'Six absences non justifiées en novembre',
        ])->assertOk()->assertJsonPath('data.status', 'revoque');
        $this->assertSame('revocation', DB::table('stage_notes')->where('stage_request_id', $req->id)->orderByDesc('id')->value('kind'));

        $this->expectException(\App\Exceptions\BusinessException::class);
        app(AttestationService::class)->exceptional($req->fresh(), $lead, 'x', 'Demande du stagiaire pour son école');
    }

    public function test_special_attestation_still_possible_after_desist(): void
    {
        $this->at('2026-11-20 09:00');
        [$req] = $this->intern();
        $this->attend($req, ['2026-11-05', '2026-11-06']);
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin", ['type' => 'desistement'])->assertOk();

        $a = app(AttestationService::class)->exceptional($req->fresh(), $lead, 'x', 'Période effectuée demandée par l\'école');
        $this->assertSame('a_imprimer', $a->status);
        $this->assertSame('2026-11-06', $a->content['date_fin'], 'période réellement effectuée');
    }

    public function test_end_note_pdf_is_generated_from_text(): void
    {
        $this->at('2026-11-20 09:00');
        [$req] = $this->intern();
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/fin", [
            'type' => 'revocation', 'motif_type' => 'reglement', 'motif' => 'Article 4',
        ])->assertOk();
        $note = \App\Modules\Stages\Models\StageNote::where('stage_request_id', $req->id)->where('kind', 'revocation')->firstOrFail();
        $html = view('stages::pdfs.fin', ['revocation' => true, 'constate' => false, 'd' => [
            'appel' => 'Monsieur', 'masculin' => true, 'nom' => 'AHO', 'prenoms' => 'Luc', 'reference' => $req->reference,
            'formation' => 'L3', 'etablissement' => 'UAC', 'debut' => 'x', 'fin_prevue' => 'y', 'dernier' => null, 'effet' => 'z',
            'absences' => null, 'motif_type' => 'Manquement au règlement intérieur', 'motif' => 'Article 4',
        ], 'reference' => 'N° 1', 'ville' => 'Cotonou', 'date' => '20/11/2026', 'signataire_titre' => 'Le Responsable', 'signataire_nom' => null,
            'signature' => null, 'signature_box' => ['w' => 50, 'h' => 20], 'footer' => ''])->render();
        $this->assertStringContainsString('NOTE DE RÉVOCATION', $html);
        $this->assertStringContainsString('Aucune attestation de stage ne sera délivrée', $html);
        $this->assertStringStartsWith('%PDF', app(\App\Modules\Stages\Services\StagePdfService::class)->note($note)['content']);
        $this->actingAs($lead, 'sanctum')->getJson("/api/stages/admin/demandes/{$req->id}/notes/{$note->id}/pdf")->assertOk();
    }
}
