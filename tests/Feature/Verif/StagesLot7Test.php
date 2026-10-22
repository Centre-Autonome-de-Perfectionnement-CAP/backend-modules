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

/** Module Stages - Lot 7 : stagiaires arrivés avant le système (invitation par numéro). */
class StagesLot7Test extends TestCase
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

    private function lead(): User
    {
        return $this->user('responsable-division', 'formation_continue');
    }

    private function invite(string $phone = '0197112233'): string
    {
        $this->actingAs($this->lead(), 'sanctum')
            ->postJson('/api/stages/admin/invitations-stagiaires', ['numeros' => $phone])->assertOk()->assertJsonCount(1, 'data.invited');
        $wa = collect($this->sent)->last(fn ($s) => str_contains($s['text'], '/stage/rejoindre?token='));
        $this->assertNotNull($wa);
        preg_match('/token=([A-Za-z0-9]{48})/', $wa['text'], $m);

        return $m[1];
    }

    private function form(string $token, string $start, string $end, array $extra = []): array
    {
        return $extra + [
            'token' => $token, 'civilite' => 'Mlle', 'nom' => 'adjovi', 'prenoms' => 'Carine', 'date_naissance' => '2002-04-12',
            'lieu_naissance' => 'Porto-Novo', 'nationalite' => 'Béninoise', 'email' => 'carine@example.org',
            'etablissement_origine' => 'EPAC / UAC', 'filiere_niveau' => 'Licence 3 Informatique', 'theme' => 'Réseau',
            'date_debut' => $start, 'date_fin' => $end, 'password' => 'Stage2026', 'password_confirmation' => 'Stage2026', 'consentement' => '1',
        ];
    }

    public function test_finished_stage_registered_with_declared_dates(): void
    {
        $this->at('2026-10-22 10:00');
        $token = $this->invite();

        $this->getJson("/api/stages/rejoindre?token={$token}")->assertOk()->assertJsonPath('data.phone', '••••••2233');
        // Un stage à venir passe par la demande normale
        $this->postJson('/api/stages/rejoindre', $this->form($token, '2026-11-02', '2027-01-31'))->assertStatus(422);

        $r = $this->post('/api/stages/rejoindre', $this->form($token, '2026-03-02', '2026-05-29', [
            'note_papier' => UploadedFile::fake()->create('note.pdf', 80, 'application/pdf'),
        ]), ['Accept' => 'application/json'])->assertStatus(201)->assertJsonStructure(['data' => ['token', 'reference']]);
        $h = ['X-Stagiaire-Token' => $r->json('data.token'), 'Accept' => 'application/json'];

        $req = StageRequest::where('reference', $r->json('data.reference'))->first();
        $this->assertSame(['invitation', 'accepted', '2026-10-22', '+2290197112233', 'ADJOVI'],
            [$req->origin, $req->status, $req->attendance_from->toDateString(), $req->whatsapp, $req->nom]);
        $this->assertArrayHasKey('note_papier', $req->files);

        // Note de service datée du début déclaré (cachet compris), date réelle gardée par le système
        $note = $req->notes()->first();
        $issued = CarbonImmutable::parse($note->issued_at)->setTimezone(BusinessHours::TZ);
        $this->assertSame('2026-03-02 08:30', $issued->format('Y-m-d H:i'));
        $this->assertStringStartsWith('NS-STG-2026-', $note->number);
        $this->assertSame('2026-10-22', CarbonImmutable::parse($note->recorded_at)->setTimezone(BusinessHours::TZ)->toDateString());
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'Stage enregistré')));

        // Lien à usage unique
        $this->postJson('/api/stages/rejoindre', $this->form($token, '2026-03-02', '2026-05-29'))->assertStatus(410);

        // Stage terminé : seul le rapport final est dû, sans retard ; pas d'absences
        $plan = app(ReportService::class)->plan($req->fresh());
        $this->assertSame(['FINAL'], array_column($plan, 'key'));
        app(StageSettingsService::class)->update(['reglement_version' => 1], null);
        $this->postJson('/api/stagiaire/reglement', ['version' => app(StageSettingsService::class)->get('reglement_version')], $h)->assertOk();
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => UploadedFile::fake()->create('r.pdf', 300, 'application/pdf')], $h)
            ->assertStatus(201)->assertJsonPath('data.late', false)->json('data.id');
        $att = app(\App\Modules\Stages\Services\AttestationService::class);
        $this->assertFalse($att->eligibility($req->fresh())['eligible']);
        $this->actingAs($this->lead(), 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])->assertOk();
        $this->assertTrue($att->eligibility($req->fresh())['eligible']);

        // Attestation préparée, datée par défaut de la fin déclarée
        $a = \App\Modules\Stages\Models\StageAttestation::where('stage_request_id', $req->id)->first();
        $this->assertNotNull($a);
        $this->assertSame('2026-05-29', $att->present($a->fresh('stage'))['default_document_date']);
    }

    public function test_ongoing_stage_counts_from_registration_only(): void
    {
        $this->at('2026-10-22 10:00');
        $token = $this->invite('0196554433');
        $r = $this->postJson('/api/stages/rejoindre', $this->form($token, '2026-09-01', '2026-12-18'))->assertStatus(201);
        $req = StageRequest::where('reference', $r->json('data.reference'))->first();

        $this->at('2026-10-26 18:30');
        $report = app(\App\Modules\Stages\Services\AttendanceService::class)->report($req->fresh());
        $this->assertSame('2026-10-22', end($report['days'])['day']); // rien avant l'entrée dans le système
        $plan = app(ReportService::class)->plan($req->fresh());
        $this->assertGreaterThanOrEqual('2026-10-22', collect($plan)->where('kind', '!=', 'final')->min('end'));

        // Le même numéro ne peut pas être réinvité tant que le stage court
        $this->actingAs($this->lead(), 'sanctum')
            ->postJson('/api/stages/admin/invitations-stagiaires', ['numeros' => '0196554433'])->assertOk()->assertJsonCount(1, 'data.already_intern');
    }

    public function test_only_lead_invites_and_links_expire(): void
    {
        $this->actingAs($this->user('secretaire'), 'sanctum')
            ->postJson('/api/stages/admin/invitations-stagiaires', ['numeros' => '0197112233'])->assertForbidden();
        $this->at('2026-10-22 10:00');
        $token = $this->invite();
        $list = $this->getJson('/api/stages/admin/invitations-stagiaires')->assertOk()->json('data');
        $this->assertSame('pending', $list[0]['status']);
        $this->at('2026-10-30 10:00');
        $this->getJson("/api/stages/rejoindre?token={$token}")->assertStatus(410);
    }
}
