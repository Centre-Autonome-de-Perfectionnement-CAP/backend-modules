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

/** Module Stages : une même personne n'a pas deux demandes ou stages en même temps (NPI, numéro, e-mail, identité). */
class StagesIdentityTest extends TestCase
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
            'token' => $token, 'civilite' => 'Mlle', 'nom' => 'adjovi', 'prenoms' => 'Carine', 'npi' => (string) random_int(1000000000, 9999999999), 'date_naissance' => date('Y-m-d', strtotime('2002-01-01 +' . random_int(0, 2000) . ' days')),
            'lieu_naissance' => 'Porto-Novo', 'nationalite' => 'Béninoise', 'email' => 'carine@example.org',
            'etablissement_origine' => 'EPAC / UAC', 'filiere' => 'Génie civil', 'cycle' => 'ingenieur', 'annee_etude' => 1, 'theme' => 'Réseau',
            'date_debut' => $start, 'date_fin' => $end, 'password' => 'Stage2026', 'password_confirmation' => 'Stage2026', 'consentement' => '1',
        ];
    }

    private function register(string $phone, array $extra = [], string $start = '2026-09-01', string $end = '2026-12-18')
    {
        return $this->postJson('/api/stages/rejoindre', $this->form($this->invite($phone), $start, $end, $extra));
    }

    private function stage($response): StageRequest
    {
        return StageRequest::where('reference', $response->assertStatus(201)->json('data.reference'))->firstOrFail();
    }

    public function test_npi_is_required_cleaned_and_stored(): void
    {
        $this->at('2026-10-22 10:00');
        $this->register('0191000001', ['npi' => '12 34 56 78 9'])->assertStatus(422)->assertJsonValidationErrors('npi');
        $this->register('0191000001', ['npi' => ''])->assertStatus(422)->assertJsonValidationErrors('npi');

        $stage = $this->stage($this->register('0191000001', ['npi' => '12 34 56 78 90', 'email' => 'a@example.org']));
        $this->assertSame('1234567890', $stage->npi);
    }

    public function test_same_person_is_refused_while_a_stage_runs(): void
    {
        $this->at('2026-10-22 10:00');
        $a = $this->stage($this->register('0191000001', ['npi' => '1111111111', 'email' => 'a@example.org', 'nom' => 'adjovi', 'prenoms' => 'Carine Marie', 'date_naissance' => '2002-04-12']));

        $code = 'STAGE_PERSON_ALREADY_ACTIVE';
        // même NPI
        $this->register('0191000002', ['npi' => '1111111111', 'email' => 'b@example.org'])->assertStatus(409)->assertJsonPath('error_code', $code);
        // même e-mail
        $this->register('0191000003', ['npi' => '3333333333', 'email' => 'A@example.org'])->assertStatus(409)->assertJsonPath('error_code', $code);
        // même date de naissance + même nom + un prénom en commun
        $this->register('0191000004', ['npi' => '4444444444', 'email' => 'd@example.org', 'nom' => 'ADJOVI', 'prenoms' => 'marie', 'date_naissance' => '2002-04-12'])
            ->assertStatus(409)->assertJsonPath('error_code', $code);
        // une autre personne passe, même avec le même nom
        $this->stage($this->register('0191000005', ['npi' => '5555555555', 'email' => 'e@example.org', 'nom' => 'adjovi', 'prenoms' => 'Rose', 'date_naissance' => '2002-04-12']));
        $this->assertSame(2, StageRequest::count());
        $this->assertSame('1111111111', $a->fresh()->npi);
    }

    public function test_finished_stage_does_not_block_a_new_one_and_shows_in_history(): void
    {
        $this->at('2026-10-22 10:00');
        $old = $this->stage($this->register('0191000001', ['npi' => '1111111111', 'email' => 'a@example.org', 'nom' => 'adjovi', 'prenoms' => 'Carine', 'date_naissance' => '2002-04-12'], '2026-03-02', '2026-05-29'));
        $new = $this->stage($this->register('0191000001', ['npi' => '1111111111', 'email' => 'a@example.org', 'nom' => 'adjovi', 'prenoms' => 'Carine', 'date_naissance' => '2002-04-12']));

        $detail = $this->actingAs($this->lead(), 'sanctum')->getJson("/api/stages/admin/demandes/{$new->id}")->assertOk();
        $this->assertSame('1111111111', $detail->json('data.npi'));
        $this->assertSame([$old->id], collect($detail->json('data.autres_stages'))->pluck('id')->all());
        $this->assertFalse($detail->json('data.autres_stages.0.live'));
        $this->assertContains('npi', $detail->json('data.autres_stages.0.why'));

        // Deux stages qui se suivent ne sont pas des doublons
        $this->getJson('/api/stages/admin/doublons')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_duplicates_page_lists_overlapping_stays_only(): void
    {
        $this->at('2026-10-22 10:00');
        $a = $this->stage($this->register('0191000001', ['npi' => '1111111111', 'email' => 'a@example.org', 'nom' => 'agossou']));
        $b = $this->stage($this->register('0191000002', ['npi' => '2222222222', 'email' => 'b@example.org', 'nom' => 'bio']));
        $c = $this->stage($this->register('0191000003', ['npi' => '3333333333', 'email' => 'c@example.org', 'nom' => 'chabi']));
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->getJson('/api/stages/admin/doublons')->assertOk()->assertJsonCount(0, 'data');

        // Doublon d'avant le contrôle : même NPI saisi à la main sur deux dossiers
        $b->forceFill(['npi' => '1111111111'])->save();
        $res = $this->getJson('/api/stages/admin/doublons')->assertOk()->assertJsonCount(1, 'data');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], collect($res->json('data.0.people'))->pluck('id')->all());
        $this->assertSame(['npi'], $res->json('data.0.shared'));
        $this->assertNotContains($c->id, collect($res->json('data.0.people'))->pluck('id')->all());

        // Hors du personnel de stage : refusé
        $this->actingAs($this->user('secretaire'), 'sanctum')->getJson('/api/stages/admin/doublons')->assertStatus(403);
    }

    public function test_intern_gives_npi_once_and_staff_can_correct_it(): void
    {
        $this->at('2026-10-22 10:00');
        $r = $this->register('0191000001', ['npi' => '1111111111', 'email' => 'a@example.org', 'nom' => 'agossou']);
        $a = $this->stage($r);
        $h = ['X-Stagiaire-Token' => $r->json('data.token'), 'Accept' => 'application/json'];
        $other = $this->stage($this->register('0191000002', ['npi' => '2222222222', 'email' => 'b@example.org', 'nom' => 'bio']));

        // Stagiaire inscrit avant le NPI : il lui est demandé une fois
        $a->forceFill(['npi' => null])->save();
        $this->getJson('/api/stagiaire/moi', $h)->assertOk()->assertJsonPath('data.npi_requis', true);
        $this->postJson('/api/stagiaire/npi', ['npi' => '123'], $h)->assertStatus(422);
        // le NPI d'un autre stage en cours est refusé
        $this->postJson('/api/stagiaire/npi', ['npi' => '2222222222'], $h)->assertStatus(409)->assertJsonPath('error_code', 'STAGE_PERSON_ALREADY_ACTIVE');
        $this->postJson('/api/stagiaire/npi', ['npi' => '9999 999 999'], $h)->assertOk();
        $this->assertSame('9999999999', $a->fresh()->npi);
        $this->getJson('/api/stagiaire/moi', $h)->assertOk()->assertJsonPath('data.npi_requis', false);
        $this->postJson('/api/stagiaire/npi', ['npi' => '8888888888'], $h)->assertStatus(409)->assertJsonPath('error_code', 'STAGE_NPI_ALREADY_SET');

        // Stage terminé : rien n'est demandé
        $a->forceFill(['npi' => null, 'date_fin' => '2026-10-01'])->save();
        $this->getJson('/api/stagiaire/moi', $h)->assertOk()->assertJsonPath('data.npi_requis', false);

        // Le responsable corrige : doublon refusé, valeur libre acceptée et tracée
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->patchJson("/api/stages/admin/stagiaires/{$other->id}/npi", ['npi' => '1111111111'])->assertOk();
        $this->patchJson("/api/stages/admin/stagiaires/{$other->id}/npi", ['npi' => '1111111111'])->assertOk(); // inchangé : pas un doublon de lui-même
        $this->patchJson("/api/stages/admin/stagiaires/{$other->id}/npi", ['npi' => 'abc'])->assertStatus(422);
        $this->assertSame('1111111111', $other->fresh()->npi);
        $this->assertContains('npi_updated', $other->fresh()->history()->pluck('action')->all());
        $this->actingAs($this->user('secretaire'), 'sanctum')->patchJson("/api/stages/admin/stagiaires/{$other->id}/npi", ['npi' => '5555555555'])->assertStatus(403);
    }
}
