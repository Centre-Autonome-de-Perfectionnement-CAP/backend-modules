<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageAttendance;
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

/**
 * Module Stages - Lot 3 : activation le premier jour, espace stagiaire,
 * empreinte (WebAuthn, vraie cryptographie), pointage géolocalisé,
 * assiduité, demandes du stagiaire.
 */
class StagesLot3Test extends TestCase
{
    use InternDevice;
    use DatabaseTransactions;

    private const ORIGIN = 'https://test.cap-epac.bj';
    private const PLACE = ['lat' => 6.4146, 'lng' => 2.3412];

    /** @var array<int, array{channel: string, to: ?string, text: string}> */
    private array $sent = [];
    private $key;            // clé privée du « téléphone » de test
    private string $credId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useInternDevice();
        Queue::fake();
        Storage::fake('local');
        Cache::flush();
        DB::table('stage_settings')->delete();
        config(['app.frontend_url' => self::ORIGIN]);
        $test = $this;
        $this->app->instance(Notifier::class, new class($test) extends Notifier {
            public function __construct(private $test) {}
            public function whatsapp(?string $phone, string $message, string $context, string $module, bool $bulk = false): void
            {
                $this->test->record('whatsapp', $phone, $message);
            }
            public function email(?string $to, string $subject, string $view, array $vars = [], array $attachments = [], ?string $toName = null, array $cc = [], bool $bulk = false): void
            {
                $this->test->record('email', $to, $subject . ' ' . ($vars['ctaUrl'] ?? ''));
            }
        });
        $this->key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->credId = random_bytes(32);
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

    // ── Outils ──────────────────────────────────────────────────────────

    /** Fixe l'heure du Bénin. */
    private function at(string $local): void
    {
        $t = CarbonImmutable::parse($local, BusinessHours::TZ)->utc();
        Carbon::setTestNow($t);
        CarbonImmutable::setTestNow($t);
    }

    private function setPlace(): void
    {
        app(StageSettingsService::class)->update(['lieu_position' => self::PLACE, 'lieu_rayon' => 50], null);
    }

    private function pos(float $dLat = 0.0): array
    {
        return ['lat' => self::PLACE['lat'] + $dLat, 'lng' => self::PLACE['lng'], 'accuracy' => 12];
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

    private function intern(string $start, string $end): StageRequest
    {
        return StageRequest::create([
            'reference' => 'STG-' . strtoupper(Str::random(6)), 'status' => 'accepted', 'civilite' => 'M.',
            'nom' => 'ZINSOU', 'prenoms' => 'Paul', 'date_naissance' => '2002-01-01', 'lieu_naissance' => 'Cotonou',
            'nationalite' => 'Béninoise', 'etablissement_origine' => 'UAC', 'filiere_niveau' => 'L3',
            'duree_souhaitee' => '3_mois', 'email' => uniqid() . '@example.org', 'whatsapp' => '+2290196' . random_int(100000, 999999),
            'files' => [], 'consent_at' => now(), 'service' => 'Service Informatique', 'lieu' => 'CAP-EPAC',
            'date_debut' => $start, 'date_fin' => $end, 'accepted_at' => now(),
            'horaires' => ['arrivee' => '08:00', 'pause_debut' => '13:00', 'pause_fin' => '15:00', 'sortie' => '18:00'],
        ]);
    }

    private function linkToken(): string
    {
        $msg = collect($this->sent)->last(fn ($s) => str_contains($s['text'], '/activer?token='));
        preg_match('/token=([A-Za-z0-9]{48})/', $msg['text'], $m);

        return $m[1];
    }

    /** Stagiaire activé, règlement accepté, empreinte enregistrée. */
    private function readyIntern(string $mondayLocal = '2026-11-02'): array
    {
        $this->setPlace();
        $req = $this->intern($mondayLocal, CarbonImmutable::parse($mondayLocal)->addMonths(2)->toDateString());
        $this->at("{$mondayLocal} 10:05");
        $this->artisan('stages:process-activations')->assertSuccessful();
        $token = $this->activate($this->linkToken())->assertStatus(201)->json('data.token');

        return [$req->fresh(), ['X-Stagiaire-Token' => $token]];
    }

    // ── Mini authentificateur WebAuthn (ES256, attestation « none ») ────

    private function cbor(mixed $v): string
    {
        $head = function (int $major, int $n): string {
            if ($n < 24) return chr(($major << 5) | $n);
            if ($n < 256) return chr(($major << 5) | 24) . chr($n);
            return chr(($major << 5) | 25) . pack('n', $n);
        };
        if (is_int($v)) return $v >= 0 ? $head(0, $v) : $head(1, -1 - $v);
        if (is_array($v) && array_key_exists('__bytes', $v)) return $head(2, strlen($v['__bytes'])) . $v['__bytes'];
        if (is_string($v)) return $head(3, strlen($v)) . $v;
        $out = $head(5, count($v));
        foreach ($v as $k => $val) $out .= $this->cbor($k) . $this->cbor($val);
        return $out;
    }

    private function b64u(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    /**
     * Activation complète sur le téléphone de stage : règlement accepté,
     * téléphone enregistré (empreinte), mot de passe, présence.
     */
    private function activate(string $token, ?array $pos = null, array $headers = []): \Illuminate\Testing\TestResponse
    {
        $info = $this->getJson("/api/stagiaire/activation?token={$token}", $headers);
        $options = $this->postJson('/api/stagiaire/activation/appareil', ['token' => $token], $headers);
        if ($info->status() !== 200 || $options->status() !== 200) {
            return $options;
        }

        return $this->postJson('/api/stagiaire/activation', [
            'token' => $token, 'position' => $pos ?? $this->pos(), 'password' => 'Stage2026', 'password_confirmation' => 'Stage2026',
            'reglement_version' => $info->json('data.reglement.version'),
            'webauthn' => $this->attestation($options->json('data.publicKey')), 'label' => 'Téléphone de test',
        ], $headers);
    }

    private function enroll(array $h): void
    {
        $opts = $this->postJson('/api/stagiaire/empreinte/options', [], $h)->assertOk()->json('data.publicKey');
        $this->postJson('/api/stagiaire/empreinte', $this->attestation($opts) + ['label' => 'Téléphone de test'], $h)->assertOk();
    }

    /** Réponse d'un téléphone qui crée sa clé (ES256, attestation « none »). */
    private function attestation(array $opts): array
    {
        $this->assertSame('test.cap-epac.bj', $opts['rp']['id']);
        $d = openssl_pkey_get_details($this->key)['ec'];
        // Coordonnées sur 32 octets (OpenSSL omet les zéros de tête : échec aléatoire sinon)
        $pad = fn (string $c) => str_pad($c, 32, "\0", STR_PAD_LEFT);
        $cose = $this->cbor([1 => 2, 3 => -7, -1 => 1, -2 => ['__bytes' => $pad($d['x'])], -3 => ['__bytes' => $pad($d['y'])]]);
        $authData = hash('sha256', 'test.cap-epac.bj', true) . chr(0x45) . pack('N', 0)
            . str_repeat("\0", 16) . pack('n', strlen($this->credId)) . $this->credId . $cose;
        $att = $this->cbor(['fmt' => 'none', 'attStmt' => [], 'authData' => ['__bytes' => $authData]]);
        $client = json_encode(['type' => 'webauthn.create', 'challenge' => $opts['challenge'], 'origin' => self::ORIGIN]);

        return ['clientDataJSON' => $this->b64u($client), 'attestationObject' => $this->b64u($att)];
    }

    private function assertion(array $h, int $counter): array
    {
        $opts = $this->postJson('/api/stagiaire/pointage/options', [], $h)->assertOk()->json('data.publicKey');
        $authData = hash('sha256', 'test.cap-epac.bj', true) . chr(0x05) . pack('N', $counter);
        $client = json_encode(['type' => 'webauthn.get', 'challenge' => $opts['challenge'], 'origin' => self::ORIGIN]);
        openssl_sign($authData . hash('sha256', $client, true), $sig, $this->key, OPENSSL_ALGO_SHA256);

        return [
            'id' => $this->b64u($this->credId), 'clientDataJSON' => $this->b64u($client),
            'authenticatorData' => $this->b64u($authData), 'signature' => $this->b64u($sig),
        ];
    }

    private function punch(array $h, string $kind, int $counter, ?array $pos = null)
    {
        return $this->postJson('/api/stagiaire/pointage', [
            'kind' => $kind, 'position' => $pos ?? $this->pos(), 'webauthn' => $this->assertion($h, $counter),
        ], $h);
    }

    // ── Tests ───────────────────────────────────────────────────────────

    public function test_activation_link_reminders_and_withdrawal(): void
    {
        $req = $this->intern('2026-11-02', '2027-01-31');

        $this->at('2026-11-02 09:30');
        $this->artisan('stages:process-activations')->assertSuccessful();
        $this->assertNull($req->fresh()->activation_sent_at, 'pas avant arrivée + 2 h');

        $this->at('2026-11-02 10:01');
        $this->artisan('stages:process-activations')->assertSuccessful();
        $this->assertNotNull($req->fresh()->activation_sent_at);
        $first = $this->linkToken();
        $this->artisan('stages:process-activations')->assertSuccessful();
        $this->assertSame(0, $req->fresh()->activation_reminders, 'un seul envoi par jour');

        $this->at('2026-11-03 10:30');
        $this->artisan('stages:process-activations')->assertSuccessful();
        $this->assertSame(1, $req->fresh()->activation_reminders);
        $this->assertNotSame($first, $this->linkToken());
        $this->getJson("/api/stagiaire/activation?token={$first}")->assertStatus(410);

        $this->at('2026-11-05 10:30');
        $this->artisan('stages:process-activations')->assertSuccessful();
        $this->assertSame('withdrawn', $req->fresh()->status);
    }

    public function test_no_presence_without_configured_place(): void
    {
        // Lieu non relevé : impossible de confirmer sa présence depuis n'importe où (chez soi…).
        $req = $this->intern('2026-11-02', '2027-01-31');
        $this->at('2026-11-02 10:30');
        $this->artisan('stages:process-activations')->assertSuccessful();
        $token = $this->linkToken();
        $this->getJson("/api/stagiaire/activation?token={$token}")->assertOk()->assertJsonPath('data.checks_position', false);
        $this->activate($token, $this->pos(0.3))->assertStatus(422)->assertJsonPath('error_code', 'STAGE_PUNCH_POSITION');
        $this->assertFalse(DB::table('stage_intern_accounts')->where('stage_request_id', $req->id)->exists());
        $this->assertNull($req->fresh()->activated_at);
        $this->assertSame(0, StageAttendance::where('stage_request_id', $req->id)->count());
    }

    public function test_activation_checks_position_and_records_real_start(): void
    {
        $this->setPlace();
        $req = $this->intern('2026-11-02', '2027-01-31');
        $this->at('2026-11-04 10:30'); // deux jours de retard
        $this->artisan('stages:process-activations')->assertSuccessful();
        $token = $this->linkToken();

        $this->getJson("/api/stagiaire/activation?token={$token}")->assertOk()->assertJsonPath('data.checks_position', true);
        $this->activate($token, $this->pos(0.01))->assertStatus(422)->assertJsonPath('error_code', 'STAGE_PUNCH_POSITION');
        $this->assertNull($req->fresh()->activated_at);
        $this->assertSame(0, StageAttendance::where('stage_request_id', $req->id)->count());

        // Sans téléphone enregistré : refusé
        $this->postJson('/api/stagiaire/activation', [
            'token' => $token, 'position' => $this->pos(0.0002), 'password' => 'Stage2026', 'password_confirmation' => 'Stage2026',
            'reglement_version' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors('webauthn');
        $this->activate($token, $this->pos(0.0002))->assertStatus(201)->assertJsonStructure(['data' => ['token']]);

        $req->refresh();
        $this->assertSame('2026-11-04', $req->date_debut_reelle->toDateString());
        $arr = StageAttendance::where('stage_request_id', $req->id)->first();
        $this->assertSame(['arrivee', 'activation', 0], [$arr->kind, $arr->source, $arr->late_minutes]);
        $this->activate($token)->assertStatus(410);

        // Le responsable voit le décalage et la fin proposée (+2 jours)
        $this->actingAs($this->user('responsable-division', 'formation_continue'), 'sanctum');
        $p = $this->getJson("/api/stages/admin/stagiaires/{$req->id}/presence")->assertOk()->json('data');
        $this->assertSame('2027-02-02', $p['shift']['suggested_end']);
        $this->assertFalse($p['shift']['applied']);
    }

    public function test_login_reglement_and_isolation_from_staff(): void
    {
        $this->setPlace();
        $req = $this->intern('2026-11-02', '2027-01-31');
        $this->at('2026-11-02 10:05');
        $this->artisan('stages:process-activations');
        $this->activate($this->linkToken())->assertStatus(201);

        $this->postJson('/api/stagiaire/connexion', ['identifiant' => $req->reference, 'password' => 'mauvais'])->assertStatus(422);
        $token = $this->postJson('/api/stagiaire/connexion', ['identifiant' => strtolower($req->reference), 'password' => 'Stage2026'])->assertOk()->json('data.token');
        $this->postJson('/api/stagiaire/connexion', ['identifiant' => $req->email, 'password' => 'Stage2026'])->assertOk();
        $h = ['X-Stagiaire-Token' => $token];

        // Règlement accepté et téléphone enregistré dès l'activation
        $me = $this->getJson('/api/stagiaire/moi', $h)->assertOk()->json('data');
        $this->assertTrue($me['reglement']['accepted']);
        $this->assertTrue($me['webauthn']['enrolled']);

        // Règlement modifié → nouvelle acceptation exigée
        app(StageSettingsService::class)->update(['reglement_text' => str_repeat('Nouveau règlement intérieur du CAP. ', 4)], null);
        $me = $this->getJson('/api/stagiaire/moi', $h)->json('data');
        $this->assertFalse($me['reglement']['accepted']);
        $this->postJson('/api/stagiaire/pointage/options', [], $h)->assertStatus(403)->assertJsonPath('error_code', 'STAGE_REGLEMENT_REQUIRED');
        $this->postJson('/api/stagiaire/reglement', ['version' => 99], $h)->assertStatus(409);
        $this->postJson('/api/stagiaire/reglement', ['version' => $me['reglement']['version']], $h)->assertOk();

        // Isolation : jeton stagiaire refusé côté progiciel, et inversement
        $this->getJson('/api/stages/admin/suivi', ['Authorization' => "Bearer {$token}"])->assertStatus(401);
        $staff = $this->user('admin')->createToken('t')->plainTextToken;
        $this->getJson('/api/stagiaire/moi', ['X-Stagiaire-Token' => $staff])->assertStatus(401);

        $this->postJson('/api/stagiaire/deconnexion', [], $h)->assertOk();
        $this->getJson('/api/stagiaire/moi', $h)->assertStatus(401);
    }

    public function test_fingerprint_punch_order_lateness_and_position(): void
    {
        [$req, $h] = $this->readyIntern('2026-11-02');

        // Mardi : arrivée en retard (8h40), pause, retour, sortie anticipée
        $this->at('2026-11-03 06:30');
        $this->punch($h, 'arrivee', 1)->assertStatus(422)->assertJsonPath('error_code', 'STAGE_PUNCH_NOT_ALLOWED');
        $this->at('2026-11-03 08:40');
        $this->punch($h, 'arrivee', 2, $this->pos(0.01))->assertStatus(422)->assertJsonPath('error_code', 'STAGE_PUNCH_POSITION');
        $this->assertSame(1, DB::table('stage_attendance_failures')->where('stage_request_id', $req->id)->where('reason', 'out_of_zone')->count());
        $this->punch($h, 'arrivee', 3)->assertStatus(201);
        $this->punch($h, 'pause_fin', 4)->assertStatus(422);
        $this->at('2026-11-03 12:50');
        $this->punch($h, 'pause_debut', 5)->assertStatus(201);
        $this->at('2026-11-03 15:05');
        $this->punch($h, 'pause_fin', 6)->assertStatus(201);
        $this->at('2026-11-03 17:00');
        $res = $this->punch($h, 'sortie', 7)->assertStatus(201);
        $this->assertStringContainsString('sortie anticipée', $res->json('message'));

        $rows = StageAttendance::where('stage_request_id', $req->id)->where('day', '2026-11-03')->get()->keyBy('kind');
        $this->assertSame(40, $rows['arrivee']->late_minutes);
        $this->assertSame(0, $rows['pause_fin']->late_minutes);
        $this->assertTrue($rows['sortie']->early);
        $this->assertTrue($rows['arrivee']->position_checked);
        $this->assertNotNull($rows['arrivee']->distance_m);

        // Mauvaise signature : refusée
        $this->at('2026-11-04 08:00');
        $a = $this->assertion($h, 8);
        $a['signature'] = $this->b64u(random_bytes(70));
        $this->postJson('/api/stagiaire/pointage', ['kind' => 'arrivee', 'position' => $this->pos(), 'webauthn' => $a], $h)
            ->assertStatus(422)->assertJsonPath('error_code', 'STAGE_WEBAUTHN_FAILED');

        // Samedi : refusé
        $this->at('2026-11-07 09:00');
        $this->punch($h, 'arrivee', 9)->assertStatus(422);
    }

    public function test_attendance_report_and_permissions(): void
    {
        [$req, $h] = $this->readyIntern('2026-11-02');
        // lundi : activation (arrivée) + sortie → présent
        $this->at('2026-11-02 18:00');
        $this->punch($h, 'sortie', 1)->assertStatus(201);
        // mardi : matinée seulement → demi-journée
        $this->at('2026-11-03 08:00'); $this->punch($h, 'arrivee', 2)->assertStatus(201);
        $this->at('2026-11-03 13:00'); $this->punch($h, 'pause_debut', 3)->assertStatus(201);
        // mercredi : absent, justifié par une permission ; jeudi : absent
        $this->at('2026-11-06 09:00');
        $sup  = $this->user('superviseur');
        $lead = $this->user('responsable-division', 'formation_continue');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $req->id, 'user_id' => $sup->id]);
        $this->postJson('/api/stagiaire/demandes', ['kind' => 'permission', 'type' => 'maladie', 'date_from' => '2026-10-26', 'motif' => 'Fièvre'], $h)
            ->assertStatus(422)->assertJsonPath('error_code', 'STAGE_PERM_TOO_LATE');
        $permId = $this->post('/api/stagiaire/demandes', [
            'kind' => 'permission', 'type' => 'maladie', 'date_from' => '2026-11-04', 'date_to' => '2026-11-04', 'motif' => 'Fièvre',
            'piece' => UploadedFile::fake()->create('certificat.pdf', 50, 'application/pdf'),
        ], $h + ['Accept' => 'application/json'])->assertStatus(201)->json('data.id');

        // Superviseur du stagiaire et responsable prévenus de la demande
        foreach ([$sup->phone, $lead->phone] as $phone) {
            Queue::assertPushed(SendNotificationJob::class, fn ($j) => $j->channel === 'whatsapp'
                && $j->payload['phone'] === $phone && str_contains($j->payload['message'], 'Demande de permission'));
        }

        $this->actingAs($sup, 'sanctum');
        $this->getJson('/api/stages/admin/demandes-stagiaires')->assertOk()->assertJsonPath('pending', 1);
        $this->postJson("/api/stages/admin/demandes-stagiaires/{$permId}/decision", ['decision' => 'rejected'])->assertStatus(422);
        $this->postJson("/api/stages/admin/demandes-stagiaires/{$permId}/decision", ['decision' => 'approved'])->assertOk();
        $this->postJson("/api/stages/admin/demandes-stagiaires/{$permId}/decision", ['decision' => 'approved'])->assertStatus(409);
        $this->getJson("/api/stages/admin/demandes-stagiaires/{$permId}/piece")->assertOk()->assertJsonStructure(['content']);

        $this->at('2026-11-06 19:00');
        $s = $this->getJson('/api/stagiaire/presences', $h)->assertOk()->json('data.summary');
        // lun présent, mar demi, mer justifié, jeu absent, ven (aujourd'hui, sans pointage) absent
        $this->assertSame(['jours_ouvres' => 4, 'presents' => 1, 'demi_journees' => 1, 'absences' => 2, 'justifies' => 1],
            array_intersect_key($s, array_flip(['jours_ouvres', 'presents', 'demi_journees', 'absences', 'justifies'])));
        $this->assertEquals(37.5, $s['taux']);
    }

    public function test_prolongation_request_issues_rectificative_note(): void
    {
        [$req, $h] = $this->readyIntern('2026-11-02');
        // note initiale existante
        DB::table('stage_notes')->insert(['stage_request_id' => $req->id, 'number' => 'NS-STG-2026-' . random_int(1000, 9999), 'kind' => 'initiale', 'version' => 1, 'snapshot' => '{}', 'issued_at' => now()]);

        $this->at('2026-11-10 09:00');
        $this->postJson('/api/stagiaire/demandes', ['kind' => 'prolongation', 'new_date_fin' => '2026-12-01', 'motif' => 'x'], $h)->assertStatus(422);
        $id = $this->postJson('/api/stagiaire/demandes', ['kind' => 'prolongation', 'new_date_fin' => '2027-02-28', 'motif' => 'Projet à finir'], $h)
            ->assertStatus(201)->json('data.id');
        $this->postJson('/api/stagiaire/demandes', ['kind' => 'raccourcissement', 'new_date_fin' => '2026-12-20', 'motif' => 'x x'], $h)->assertStatus(409);

        $this->actingAs($this->user('responsable-division', 'formation_continue'), 'sanctum')
            ->postJson("/api/stages/admin/demandes-stagiaires/{$id}/decision", ['decision' => 'approved'])->assertOk();
        $req->refresh();
        $this->assertSame('2027-02-28', $req->date_fin->toDateString());
        $this->assertSame('rectificative', $req->notes()->reorder('version', 'desc')->first()->kind);
    }

    public function test_space_opens_only_on_the_stage_phone(): void
    {
        [$req, $h] = $this->readyIntern('2026-11-02');
        $other = ['X-Stagiaire-Appareil' => 'telephone-d-un-camarade-0123456789abcd'];
        $login = ['identifiant' => $req->reference, 'password' => 'Stage2026'];

        // Connexion depuis un autre téléphone : refusée, tracée, stagiaire prévenu
        $this->postJson('/api/stagiaire/connexion', $login, $other)->assertStatus(403)->assertJsonPath('error_code', 'STAGE_DEVICE_OTHER');
        $this->assertTrue(DB::table('stage_request_history')->where('stage_request_id', $req->id)->where('action', 'device_refused')->exists());
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === $req->whatsapp && str_contains($m['text'], 'Connexion refusée')));
        // Mot de passe oublié depuis cet autre téléphone : refusé aussi
        $this->postJson('/api/stagiaire/mot-de-passe/code', ['identifiant' => $req->reference])->assertOk();
        // Session copiée sur un autre téléphone : inutilisable
        $this->getJson('/api/stagiaire/moi', $h + $other)->assertStatus(401);
        // Sans identifiant d'appareil : demande de mise à jour
        $this->postJson('/api/stagiaire/connexion', $login, ['X-Stagiaire-Appareil' => ''])->assertStatus(422)->assertJsonPath('error_code', 'STAGE_DEVICE_MISSING');
        // Sur le téléphone de stage : ouvert
        $this->postJson('/api/stagiaire/connexion', $login)->assertOk();
        $this->getJson('/api/stagiaire/moi', $h)->assertOk();
    }

    public function test_one_phone_serves_one_intern_only(): void
    {
        $this->readyIntern('2026-11-02');
        // Un camarade active son compte sur le même téléphone : refusé
        $second = $this->intern('2026-11-02', '2027-01-31');
        $this->artisan('stages:process-activations')->assertSuccessful();
        $token = collect($this->sent)->last(fn ($m) => $m['to'] === $second->whatsapp && str_contains($m['text'], '/activer?token='));
        preg_match('/token=([A-Za-z0-9]{48})/', $token['text'], $m);
        $this->activate($m[1])->assertStatus(409)->assertJsonPath('error_code', 'STAGE_DEVICE_TAKEN');
        $this->assertNull($second->fresh()->activated_at);
        // Sur son propre téléphone : accepté
        $this->credId = random_bytes(32);
        $this->activate($m[1], null, ['X-Stagiaire-Appareil' => 'telephone-du-second-stagiaire-0123456789'])->assertStatus(201);
    }

    public function test_device_reset_requires_new_enrolment(): void
    {
        [$req, $h] = $this->readyIntern('2026-11-02');
        $this->actingAs($this->user('superviseur'), 'sanctum')
            ->postJson("/api/stages/admin/stagiaires/{$req->id}/appareil/reinitialiser")->assertOk();
        $this->at('2026-11-03 08:00');
        // Sessions fermées ; connexion depuis le nouveau téléphone, à enregistrer avant de pointer
        $this->getJson('/api/stagiaire/moi', $h)->assertStatus(401);
        $new = ['X-Stagiaire-Appareil' => 'nouveau-telephone-de-stage-0123456789'];
        $h = $new + ['X-Stagiaire-Token' => $this->postJson('/api/stagiaire/connexion', ['identifiant' => $req->reference, 'password' => 'Stage2026'], $new)
            ->assertOk()->json('data.token')];
        $this->postJson('/api/stagiaire/pointage/options', [], $h)->assertStatus(409)->assertJsonPath('error_code', 'STAGE_WEBAUTHN_MISSING');
        $this->assertFalse($this->getJson('/api/stagiaire/moi', $h)->json('data.webauthn.enrolled'));
        $this->key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->credId = random_bytes(32); // nouveau téléphone
        $this->enroll($h);
        $this->punch($h, 'arrivee', 1)->assertStatus(201);
        // L'ancien téléphone n'ouvre plus l'espace
        $this->postJson('/api/stagiaire/connexion', ['identifiant' => $req->reference, 'password' => 'Stage2026'])->assertStatus(403);
    }
}
