<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\StageChatService;
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

/** Module Stages - Lot 9 : messagerie (discussions, rappels groupés, effacement à 7 jours). */
class StagesLot9Test extends TestCase
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

    public function test_group_is_private_to_supervisor_and_interns_general_open_to_all(): void
    {
        $this->at('2026-11-20 09:00');
        [$a, $ha] = $this->intern();
        [$b, $hb] = $this->intern();
        $sup = $this->user('superviseur');
        $other = $this->user('superviseur');
        $lead = $this->lead();
        $this->supervise($a, $sup);

        $ch = "groupe-{$sup->id}";
        $this->getJson('/api/stagiaire/messages', $ha)->assertOk()->assertJsonPath('data.channels.0.key', $ch)
            ->assertJsonPath('data.channels.0.title', 'Mon groupe de stage')->assertJsonPath('data.channels.1.key', 'general');
        $this->getJson('/api/stagiaire/messages', $hb)->assertOk()->assertJsonCount(1, 'data.channels');
        $this->getJson("/api/stagiaire/messages/{$ch}", $hb)->assertStatus(403);
        $this->actingAs($other, 'sanctum')->getJson("/api/stages/admin/messages/{$ch}")->assertStatus(403);
        $this->actingAs($lead, 'sanctum')->getJson("/api/stages/admin/messages/{$ch}")->assertStatus(403);

        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/messages/{$ch}", ['body' => '<b>Bonjour</b> à tous'])->assertOk();
        $this->postJson('/api/stagiaire/messages/general', ['body' => 'Salut tout le monde'], $hb)->assertOk();

        $this->getJson('/api/stagiaire/messages', $ha)->assertJsonPath('data.unread', 2);
        $r = $this->getJson("/api/stagiaire/messages/{$ch}", $ha)->assertOk();
        $this->assertSame('Bonjour à tous', $r->json('data.messages.0.body'), 'texte seul');
        $this->assertSame('Superviseur', $r->json('data.messages.0.sender.role'));
        $this->getJson('/api/stagiaire/messages', $ha)->assertJsonPath('data.unread', 1);

        $this->actingAs($sup, 'sanctum')->getJson("/api/stages/admin/messages/{$ch}")->assertJsonPath('data.seen.count', 1)
            ->assertJsonPath('data.messages.0.mine', true);
        $this->actingAs($lead, 'sanctum')->getJson('/api/stages/admin/messages/general')->assertOk()
            ->assertJsonPath('data.messages.0.body', 'Salut tout le monde');
    }

    public function test_one_reminder_per_burst_quiet_hours_and_daily_cap(): void
    {
        $this->at('2026-11-20 09:00');
        [$a, $ha] = $this->intern();
        $sup = $this->user('superviseur');
        $this->supervise($a, $sup);
        $ch = "groupe-{$sup->id}";
        foreach (['Bonjour à tous', 'J\'espère que vous allez bien', 'En fait…', 'La réunion est jeudi', 'Salle B'] as $m) {
            $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/messages/{$ch}", ['body' => $m])->assertOk();
        }
        $chat = app(StageChatService::class);
        $toA = fn () => collect($this->sent)->filter(fn ($s) => $s['to'] === $a->whatsapp)->values();

        $this->at('2026-11-20 09:05'); $chat->tick();
        $this->assertCount(0, $toA(), 'rafale en cours : on attend 10 minutes');
        $this->at('2026-11-20 09:12'); $chat->tick();
        $this->at('2026-11-20 09:30'); $chat->tick();
        $this->assertCount(1, $toA(), 'un seul rappel pour la rafale');
        $this->assertStringContainsString('5 nouveaux messages', $toA()[0]['text']);

        // Lu, puis nouvelle rafale : 2e rappel ; une 3e rafale le même jour : rien
        $this->getJson("/api/stagiaire/messages/{$ch}", $ha)->assertOk();
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/messages/{$ch}", ['body' => 'Rappel : rapport vendredi'])->assertOk();
        $this->at('2026-11-20 10:00'); $chat->tick();
        $this->assertCount(2, $toA());
        $this->at('2026-11-20 10:01');
        $this->getJson("/api/stagiaire/messages/{$ch}", $ha)->assertOk();
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/messages/{$ch}", ['body' => 'Encore un point'])->assertOk();
        $this->at('2026-11-20 11:00'); $chat->tick();
        $this->assertCount(2, $toA(), 'au plus 2 rappels par jour');

        // La nuit : rien ; le lendemain matin : rappel
        $this->at('2026-11-20 22:00'); $chat->tick();
        $this->assertCount(2, $toA());
        $this->at('2026-11-21 07:10'); $chat->tick();
        $this->assertCount(3, $toA());
    }

    public function test_messages_are_deleted_after_seven_days_and_ended_intern_loses_access(): void
    {
        $this->at('2026-11-10 09:00');
        [$a, $ha] = $this->intern();
        $this->postJson('/api/stagiaire/messages/general', ['body' => 'Ancien message'], $ha)->assertOk();
        $this->at('2026-11-18 09:00');
        app(StageChatService::class)->tick();
        $this->assertSame(0, DB::table('stage_chat_messages')->count());

        DB::table('stage_requests')->where('id', $a->id)->update(['status' => 'desiste']);
        $this->getJson('/api/stagiaire/messages', $ha)->assertStatus(401);
    }
}
