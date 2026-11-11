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

/** Module Stages - Lot 11 : attestations de travail (invitation sans compte, vérification, impression, intéressé prévenu, remise). */
class StagesLot11Test extends TestCase
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

    private function invite(string $phone = '0197112233'): string
    {
        $this->actingAs($this->lead(), 'sanctum')
            ->postJson('/api/stages/admin/invitations-stagiaires', ['numeros' => $phone])->assertOk()->assertJsonCount(1, 'data.invited');
        $wa = collect($this->sent)->last(fn ($s) => str_contains($s['text'], '/stage/rejoindre/'));
        $this->assertNotNull($wa);
        preg_match('#(?:invitation-superviseur|activer|rejoindre)/([A-Za-z0-9]{16})\b#', $wa['text'], $m);

        return $m[1];
    }

    private function form(string $token, string $start, string $end, array $extra = []): array
    {
        return $extra + [
            'token' => $token, 'civilite' => 'Mlle', 'nom' => 'adjovi', 'prenoms' => 'Carine', 'date_naissance' => '2002-04-12',
            'lieu_naissance' => 'Porto-Novo', 'nationalite' => 'Béninoise', 'email' => 'carine@example.org',
            'etablissement_origine' => 'EPAC / UAC', 'filiere' => 'Génie civil', 'cycle' => 'ingenieur', 'annee_etude' => 1, 'theme' => 'Réseau',
            'date_debut' => $start, 'date_fin' => $end, 'password' => 'Stage2026', 'password_confirmation' => 'Stage2026', 'consentement' => '1',
        ];
    }

    private function link(string $phone): string
    {
        $wa = collect($this->sent)->last(fn ($s) => $s['to'] === $phone && str_contains($s['text'], '/attestation-travail/'));
        $this->assertNotNull($wa);
        preg_match('#attestation-travail/([A-Za-z0-9]{16})\b#', $wa['text'], $m);

        return $m[1];
    }

    private function fiche(string $token, array $extra = []): array
    {
        return $extra + [
            'token' => $token, 'civilite' => 'Mlle', 'nom' => 'houngbo', 'prenoms' => "Ange-Marie d'Assise", 'email' => 'ange@example.org',
            'diplome' => 'Ingénieur en Génie Informatique', 'date_debut' => '2026-03-01', 'date_fin' => '2026-09-30',
            'fonctions' => ['Développeuse web', '', 'Assistante réseau'], 'taches' => ["l'assistance aux usagers ;", 'la maintenance du parc.'],
            'consentement' => '1',
        ];
    }

    public function test_full_flow_from_invitation_to_delivery(): void
    {
        $this->at('2026-11-11 10:00');
        $lead = $this->lead();
        $sec = $this->user('secretaire');

        // Seul le responsable invite ; numéro invalide refusé
        $this->actingAs($sec, 'sanctum')->postJson('/api/stages/admin/attestations-travail', ['numero' => '0197445566'])->assertForbidden();
        $this->actingAs($lead, 'sanctum')->postJson('/api/stages/admin/attestations-travail', ['numero' => '12'])->assertStatus(422);
        $id = $this->postJson('/api/stages/admin/attestations-travail', ['numero' => '0197445566'])->assertStatus(201)->json('data.id');
        $token = $this->link('+2290197445566');

        // Lien public : sans compte ; fiche envoyée une seule fois
        $this->getJson("/api/stages/attestation-travail?token={$token}")->assertOk()->assertJsonPath('data.phone', '••••••5566');
        $this->postJson('/api/stages/attestation-travail', $this->fiche($token, ['fonctions' => []]))->assertStatus(422);
        $this->postJson('/api/stages/attestation-travail', $this->fiche($token, ['date_fin' => '2027-01-31']))->assertStatus(422);
        $this->postJson('/api/stages/attestation-travail', $this->fiche($token))->assertStatus(201);
        $this->postJson('/api/stages/attestation-travail', $this->fiche($token))->assertStatus(410);
        $this->getJson("/api/stages/attestation-travail?token={$token}")->assertStatus(410);

        // Le secrétariat ne la voit pas encore ; le responsable vérifie et corrige
        $this->actingAs($sec, 'sanctum')->getJson("/api/stages/admin/attestations-travail/{$id}")->assertNotFound();
        $a = $this->actingAs($lead, 'sanctum')->getJson("/api/stages/admin/attestations-travail/{$id}")->assertOk()->json('data');
        $this->assertSame(['a_valider', 'HOUNGBO', ['Développeuse web', 'Assistante réseau']], [$a['status'], $a['values']['nom'], $a['values']['fonctions']]);
        $this->putJson("/api/stages/admin/attestations-travail/{$id}", ['nature' => 'à titre bénévole',
            'appreciation' => 'Son engagement a grandement contribué au bon fonctionnement du Service Informatique du Centre.'])->assertOk();

        // Aperçu PDF : texte réel, accords et dates en toutes lettres
        $pdf = base64_decode($this->getJson("/api/stages/admin/attestations-travail/{$id}/pdf")->assertOk()->json('content'));
        $this->assertStringStartsWith('%PDF', $pdf);
        $text = $this->pdfText($pdf);
        foreach (['ATTESTATION DE TRAVAIL', 'Mlle HOUNGBO', 'du diplôme d’Ingénieur', 'à titre bénévole', 'les fonctions de', 'Développeuse web',
            'du 1er mars 2026 au 30 septembre 2026', 'elle a notamment été chargée de', 'l’assistance', 'ce que de droit.'] as $needle) {
            $this->assertStringContainsString($needle, $text, $needle);
        }
        $this->assertStringNotContainsString('droit. -', $text);

        // Validation : le secrétariat est prévenu, le contenu est figé
        $this->postJson("/api/stages/admin/attestations-travail/{$id}/valider")->assertOk()->assertJsonPath('data.status', 'a_imprimer');
        $this->putJson("/api/stages/admin/attestations-travail/{$id}", ['nom' => 'Autre'])->assertStatus(409);

        // Secrétariat : impression (registre commun), signature, intéressé prévenu, remise
        $this->sent = [];
        $next = $this->actingAs($sec, 'sanctum')->getJson("/api/stages/admin/attestations-travail/{$id}")->assertOk()->json('data.next_number');
        $this->assertSame('001-26', $next);
        $this->postJson("/api/stages/admin/attestations-travail/{$id}/prevenir")->assertStatus(409); // pas encore imprimée
        $this->postJson("/api/stages/admin/attestations-travail/{$id}/imprimee", ['numero' => $next])->assertOk()->assertJsonPath('data.status', 'imprimee');
        $this->assertFalse(collect($this->sent)->contains(fn ($s) => $s['to'] === '+2290197445566')); // signature d'abord
        $this->postJson("/api/stages/admin/attestations-travail/{$id}/prevenir")->assertOk()->assertJsonPath('data.status', 'prete');
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === '+2290197445566' && str_contains($s['text'], 'retirer au secrétariat')));
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === 'ange@example.org'));
        $this->postJson("/api/stages/admin/attestations-travail/{$id}/remise", ['date' => '2026-11-11'])->assertOk()->assertJsonPath('data.status', 'remise');
        $this->assertSame('002-26', app(\App\Modules\Stages\Services\AttestationService::class)->nextNumber());
    }

    public function test_from_an_existing_intern_file(): void
    {
        $this->at('2026-10-22 10:00');
        $r = $this->postJson('/api/stages/rejoindre', $this->form($this->invite('0191000777'), '2026-03-02', '2026-05-29', ['email' => 'old@example.org']))->assertStatus(201);
        $stage = StageRequest::where('reference', $r->json('data.reference'))->first();
        $lead = $this->lead();

        // Le responsable remplit lui-même : fiche préremplie, à compléter avant de valider
        $id = $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/stagiaires/{$stage->id}/attestation-travail", ['mode' => 'remplir'])
            ->assertStatus(201)->assertJsonPath('data.values.nom', 'ADJOVI')->assertJsonPath('data.values.email', 'old@example.org')->json('data.id');
        $this->postJson("/api/stages/admin/attestations-travail/{$id}/valider")->assertStatus(422);
        $this->putJson("/api/stages/admin/attestations-travail/{$id}", ['diplome' => 'Licence en Génie civil', 'date_debut' => '2026-06-01',
            'date_fin' => '2026-10-15', 'fonctions' => ['Assistante administrative'], 'taches' => array_fill(0, 8, str_repeat('x', 105))])->assertOk();
        // Trop long pour une page : refusé avec une explication
        $this->postJson("/api/stages/admin/attestations-travail/{$id}/valider")->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'une page'));
        $this->putJson("/api/stages/admin/attestations-travail/{$id}", ['taches' => ['le classement des dossiers.']])->assertOk();
        $this->postJson("/api/stages/admin/attestations-travail/{$id}/valider")->assertOk();

        // Ou le lien envoyé à son numéro ; un nouveau lien annule l'ancien
        $this->postJson("/api/stages/admin/stagiaires/{$stage->id}/attestation-travail", ['mode' => 'lien'])->assertStatus(201);
        $first = $this->link('+2290191000777');
        $this->postJson("/api/stages/admin/attestations-travail", ['numero' => '0191000777'])->assertStatus(201);
        $this->getJson("/api/stages/attestation-travail?token={$first}")->assertStatus(410);
        $this->getJson('/api/stages/attestation-travail?token=' . $this->link('+2290191000777'))->assertOk()->assertJsonPath('data.values.nom', null);
    }

    public function test_stage_attestation_can_be_notified_again(): void
    {
        $this->at('2026-10-22 10:00');
        $r = $this->postJson('/api/stages/rejoindre', $this->form($this->invite('0191000888'), '2026-03-02', '2026-05-29'))->assertStatus(201);
        $stage = StageRequest::where('reference', $r->json('data.reference'))->first();
        $a = \App\Modules\Stages\Models\StageAttestation::where('stage_request_id', $stage->id)->first();
        $a->forceFill(['status' => 'imprimee', 'printed_at' => now(), 'content' => ['date_debut' => '2026-03-02', 'date_fin' => '2026-05-29']])->save();
        $this->sent = [];
        $this->actingAs($this->user('secretaire'), 'sanctum')->postJson("/api/stages/admin/attestations/{$a->id}/prevenir")->assertOk();
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === '+2290191000888' && str_contains($s['text'], 'attestation de stage est prête')));
    }

    private function pdfText(string $pdf): string
    {
        $f = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($f, $pdf);
        $txt = (string) shell_exec('pdftotext -layout ' . escapeshellarg($f) . ' - 2>/dev/null');
        @unlink($f);

        return preg_replace('/\s+/u', ' ', $txt);
    }
}
