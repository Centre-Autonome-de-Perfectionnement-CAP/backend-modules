<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
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

/** Module Stages : le rapport de fin de stage s'ouvre aux 2/3 du stage, quelle que soit sa durée. */
class StagesFinalReportOpeningTest extends TestCase
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

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('rapport.pdf', 300, 'application/pdf');
    }

    public function test_opens_at_two_thirds_of_the_stay_whatever_its_length(): void
    {
        $opens = fn (string $start, string $end) => ReportPlan::finalOpens(CarbonImmutable::parse($start), CarbonImmutable::parse($end))->toDateString();

        $this->assertSame('2026-11-02', $opens('2026-11-02', '2026-11-02'), '1 jour : le jour même');
        $this->assertSame('2026-11-03', $opens('2026-11-02', '2026-11-03'), '2 jours : le 2e');
        $this->assertSame('2026-11-20', $opens('2026-11-02', '2026-12-01'), '22 jours ouvrés (1 mois) : le 15e');
        $this->assertSame('2026-12-31', $opens('2026-11-02', '2027-01-29'), '65 jours ouvrés (3 mois) : le 44e');
        $this->assertSame('2027-03-02', $opens('2026-11-02', '2027-04-30'), '130 jours ouvrés (6 mois) : le 87e');
        // Les week-ends ne comptent pas
        $this->assertSame('2026-11-12', $opens('2026-11-07', '2026-11-13'), 'stage commençant un week-end : 5 jours ouvrés, le 4e');
        // Le rapport reste attendu au plus tard le dernier jour
        $plan = ReportPlan::periods(CarbonImmutable::parse('2026-11-02'), CarbonImmutable::parse('2026-11-03'), 'hebdomadaire', 2);
        $final = end($plan);
        $this->assertSame(['FINAL', '2026-11-03', '2026-11-03'], [$final['key'], $final['opens'], $final['closes']]);
    }

    public function test_opening_is_announced_once_to_supervisors_and_the_intern(): void
    {
        [$req] = $this->intern();
        $sup = $this->user('superviseur');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $req->id, 'user_id' => $sup->id, 'created_at' => now()]);
        $this->user('responsable-division', 'formation_continue');

        $this->at('2026-12-10 08:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $announced = fn () => collect($this->sent)->filter(fn ($s) => str_contains($s['text'], 'Rapport de fin de stage ouvert'))->count();
        $this->assertSame(0, $announced(), 'veille de l\'ouverture');

        $this->at('2026-12-11 08:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $first = $announced();
        $this->assertGreaterThan(0, $first, 'encadrants prévenus à l\'ouverture');
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === $req->whatsapp && str_contains($s['text'], 'Rapport de fin de stage à déposer')));

        // Une seule fois
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->assertSame($first, $announced());
    }

    public function test_once_open_it_stays_open_after_a_prolongation(): void
    {
        [$req, $h] = $this->intern();
        $this->user('responsable-division', 'formation_continue');

        $this->at('2026-12-11 08:00');
        $this->artisan('stages:process-reports')->assertSuccessful();

        // Prolongation de deux mois : les 2/3 se décalent, l'espace ouvert ne se referme pas
        $req->update(['date_fin' => '2027-02-26']);
        $this->at('2026-12-14 10:00');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(201);
    }

    public function test_not_open_before_two_thirds(): void
    {
        [, $h] = $this->intern();
        $this->at('2026-12-10 10:00');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)
            ->assertStatus(422)->assertJsonPath('error_code', 'STAGE_REPORT_NOT_OPEN');
        $this->at('2026-12-11 10:00');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf()], $h)->assertStatus(201);
    }
}
