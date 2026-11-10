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
use App\Modules\Stages\Services\TaskService;
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

/** Module Stages : documents, avis, notation du stage, évaluation des stagiaires. */
class StagesFeedbackTest extends TestCase
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
                $this->test->record('whatsapp', $phone, $message, $at, $fallbackEmail['to'] ?? null);
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

    public function record(string $channel, ?string $to, string $text, ?\DateTimeInterface $at = null, ?string $fallback = null): void
    {
        $this->sent[] = ['channel' => $channel, 'to' => $to, 'text' => $text, 'at' => $at, 'fallback' => $fallback];
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


    public function test_documents_added_by_staff_are_visible_to_interns_and_announced(): void
    {
        [, $h] = $this->intern();
        $sup = $this->user('superviseur');
        $this->at('2026-11-10 10:00');
        $this->actingAs($sup, 'sanctum');
        $this->post('/api/stages/admin/documents', ['title' => 'Modèle de rapport', 'category' => 'modeles',
            'file' => UploadedFile::fake()->create('modele.docx', 40, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')], ['Accept' => 'application/json'])->assertStatus(201);
        $id = $this->postJson('/api/stages/admin/documents', ['title' => 'Cours Laravel', 'category' => 'tutoriels', 'url' => 'laravel.com/docs'])->assertStatus(201)->json('data.id');
        $this->postJson('/api/stages/admin/documents', ['title' => 'Vide', 'category' => 'autre'])->assertStatus(422);

        $items = $this->getJson('/api/stagiaire/documents', $h)->assertOk()->json('data.items');
        $this->assertCount(2, $items);
        $this->assertSame('https://laravel.com/docs', $items[0]['url']);
        $this->getJson("/api/stagiaire/documents/{$items[1]['id']}/fichier", $h)->assertOk()->assertJsonPath('name', 'modele.docx');
        $this->postJson('/api/stagiaire/documents', ['title' => 'x', 'category' => 'autre', 'url' => 'https://x.org'], $h)->assertStatus(405);

        $this->at('2026-11-10 17:45');
        $this->artisan('stages:process-reports --digest')->assertSuccessful();
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'Nouveaux documents (2)')));
        $this->actingAs($sup, 'sanctum')->deleteJson("/api/stages/admin/documents/{$id}")->assertOk();
        $this->assertCount(1, $this->getJson('/api/stagiaire/documents', $h)->json('data.items'));
    }

    public function test_feedback_is_anonymous_by_default_and_encadrement_is_for_leads_only(): void
    {
        [$req, $h] = $this->intern();
        $this->at('2026-11-10 10:00');
        $this->postJson('/api/stagiaire/avis', ['type' => 'plainte', 'subject' => 'encadrement', 'message' => 'Mon superviseur ne répond jamais aux questions.'], $h)->assertStatus(201);
        $this->postJson('/api/stagiaire/avis', ['type' => 'positif', 'subject' => 'locaux', 'message' => 'Très bonne ambiance et du bon matériel.', 'public_ok' => true], $h)->assertStatus(201);
        $this->postJson('/api/stagiaire/avis', ['type' => 'suggestion', 'subject' => 'plateforme', 'message' => 'Ajouter un mode hors connexion au pointage.', 'signed' => true], $h)->assertStatus(201);
        $this->postJson('/api/stagiaire/avis', ['type' => 'suggestion', 'subject' => 'autre', 'message' => 'Un quatrième avis dans la même semaine.'], $h)->assertStatus(429);
        $this->assertSame(2, DB::table('stage_feedbacks')->whereNull('stage_request_id')->count(), 'anonyme : aucun lien enregistré');

        $sup = $this->user('superviseur');
        $this->actingAs($sup, 'sanctum');
        $this->getJson('/api/stages/admin/avis/compteur')->assertJsonPath('data.unread', 2);
        $items = $this->getJson('/api/stages/admin/avis')->assertOk()->json('data.items');
        $this->assertSame(['plateforme', 'locaux'], array_column($items, 'subject'));
        $this->assertSame($req->fullName(), $items[0]['author']);
        $this->assertNull($items[1]['author']);
        $this->getJson('/api/stages/admin/avis/compteur')->assertJsonPath('data.unread', 0);
        $this->postJson("/api/stages/admin/avis/{$items[1]['id']}/publication", ['public' => true])->assertStatus(403);

        $lead = $this->user('responsable-division', 'formation_continue');
        $this->actingAs($lead, 'sanctum');
        $items = $this->getJson('/api/stages/admin/avis')->json('data.items');
        $this->assertCount(3, $items);
        $positive = collect($items)->firstWhere('type', 'positif');
        $this->postJson("/api/stages/admin/avis/{$positive['id']}/publication", ['public' => true])->assertOk();
        $this->postJson("/api/stages/admin/avis/{$items[0]['id']}/publication", ['public' => true])->assertStatus(422);
        Cache::flush();
        $this->getJson('/api/stages/avis-publics')->assertOk()->assertJsonPath('data.quotes.0', 'Très bonne ambiance et du bon matériel.')
            ->assertJsonPath('data.average', null);
    }

    public function test_stage_rating_is_anonymous_once_per_period_and_public_from_ten(): void
    {
        $sup = $this->user('superviseur');
        $this->at('2026-11-10 10:00');
        $scores = ['encadrement' => 2, 'accueil' => 5, 'conditions' => 4, 'organisation' => 4, 'apprentissage' => 5];
        for ($i = 0; $i < 10; $i++) {
            [$req, $h] = $this->intern('2026-11-02', '2027-01-29');
            DB::table('stage_supervisions')->insert(['stage_request_id' => $req->id, 'user_id' => $sup->id, 'created_at' => now()]);
            $state = $this->getJson('/api/stagiaire/notation', $h)->assertOk()->json('data');
            $this->assertSame('2026-11', $state['open'][0]['period']);
            $this->assertSame($sup->id, $state['supervisors'][0]['id']);
            $this->postJson('/api/stagiaire/notation', ['period' => '2026-11', 'scores' => $scores, 'supervisors' => [$sup->id => 3]], $h)->assertStatus(201);
            if ($i === 9) {
                Cache::flush();
                $this->getJson('/api/stages/avis-publics')->assertJsonPath('data.count', 9 + 1);
            }
            if ($i === 0) {
                $this->postJson('/api/stagiaire/notation', ['period' => '2026-11', 'scores' => $scores], $h)->assertStatus(409);
                $this->postJson('/api/stagiaire/notation', ['period' => 'fin', 'scores' => $scores], $h)->assertStatus(422);
                Cache::flush();
                $this->getJson('/api/stages/avis-publics')->assertOk()->assertJsonPath('data', null);
            }
        }
        $this->assertSame(0, DB::table('stage_ratings')->whereNotNull('comment')->count());
        $pub = $this->getJson('/api/stages/avis-publics')->json('data');
        $this->assertSame(4.5, $pub['average']);
        $this->assertNotContains('Encadrement', array_column($pub['criteria'], 'label'));

        $this->actingAs($sup, 'sanctum')->getJson('/api/stages/admin/notation')->assertStatus(403);
        $board = $this->actingAs($this->user('responsable-division', 'formation_continue'), 'sanctum')->getJson('/api/stages/admin/notation')->assertOk()->json('data');
        $this->assertSame(10, $board['count']);
        $this->assertEquals(3, $board['supervisors'][0]['average']);
    }

    /** Messages au personnel (file d'attente) contenant ce texte et adressés à ce numéro. */
    private function staffSent(?string $phone, string $text): int
    {
        return Queue::pushed(SendNotificationJob::class, fn ($j) => ($j->payload['phone'] ?? null) === $phone
            && str_contains(json_encode($j->payload, JSON_UNESCAPED_UNICODE), $text))->count();
    }

    public function test_rounding_of_the_retained_mark(): void
    {
        $r = fn ($v) => \App\Modules\Stages\Services\EvaluationService::retained($v);
        $this->assertSame([13.0, 13.0, 13.0, 13.5, 13.5, 14.0, 14.0, 20.0],
            [$r(13.0), $r(13.01), $r(13.24), $r(13.25), $r(13.5), $r(13.51), $r(13.74), $r(19.9)]);
    }

    public function test_supervisors_rate_interns_and_lead_validates(): void
    {
        [$req, $h] = $this->intern('2026-10-01', '2026-11-20');
        $titular = $this->user('superviseur');
        $other = $this->user('superviseur');
        $lead = $this->user('responsable-division', 'formation_continue');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $req->id, 'user_id' => $titular->id, 'created_at' => now()]);

        // 10 jours ouvrés avant la fin : ouverture, titulaire invité, stagiaire invité à noter son stage
        $this->at('2026-11-05 09:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->assertSame(0, DB::table('stage_evaluation_results')->count());
        $this->at('2026-11-06 09:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->assertSame(1, DB::table('stage_evaluation_results')->count());
        $this->assertSame(1, $this->staffSent($titular->phone, 'Note de stage à donner'));
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === $req->whatsapp && str_contains($s['text'], 'Noter mon stage')));
        $this->getJson('/api/stagiaire/notation', $h)->assertJsonPath('data.open.1.period', 'fin');

        // Rappel aux titulaires deux jours ouvrés plus tard
        $this->at('2026-11-10 09:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->assertSame(1, $this->staffSent($titular->phone, 'Rappel : note de stage'));

        $scores = ['assiduite' => 3.5, 'qualite' => 5, 'autonomie' => 3, 'comportement' => 3, 'progression' => 2.5]; // 17
        $this->actingAs($other, 'sanctum');
        $list = $this->getJson('/api/stages/admin/evaluations')->assertOk()->json('data');
        $this->assertFalse($list['interns'][0]['mine']);
        $this->postJson("/api/stages/admin/evaluations/{$req->id}", ['scores' => ['assiduite' => 4.2] + $scores, 'comment' => 'Bon travail dans l’ensemble.'])->assertStatus(422);
        $this->postJson("/api/stages/admin/evaluations/{$req->id}", ['scores' => $scores])->assertStatus(422);
        $this->postJson("/api/stages/admin/evaluations/{$req->id}", ['scores' => ['qualite' => 3] + $scores, 'comment' => 'Correct, peut mieux faire.'])->assertOk(); // 15
        $this->actingAs($titular, 'sanctum');
        $this->assertTrue($this->getJson('/api/stages/admin/evaluations')->json('data.interns.0.mine'));
        $this->postJson("/api/stages/admin/evaluations/{$req->id}", ['scores' => $scores, 'comment' => 'Très sérieux et ponctuel.'])->assertOk(); // 17
        $this->assertSame(1, $this->staffSent($lead->phone, 'Note de stage à valider'));
        $this->getJson('/api/stages/admin/evaluations/suivi')->assertStatus(403);

        // Responsable : détail, renvoi, validation ; 0,6 × 17 + 0,4 × 15 = 16,20 → 16,00
        $this->actingAs($lead, 'sanctum');
        $item = $this->getJson('/api/stages/admin/evaluations/suivi')->assertOk()->json('data.items.0');
        $this->assertEquals(16.2, $item['preview']['average']);
        $this->assertEquals(16.0, $item['preview']['final']);
        $this->postJson("/api/stages/admin/evaluations/{$req->id}/renvoi", ['user_id' => $other->id, 'note' => 'Précisez le commentaire.'])->assertOk();
        $this->postJson("/api/stages/admin/evaluations/{$req->id}/validation", ['appreciation' => 'Stage réussi, bonne progression.'])->assertStatus(409);
        $this->actingAs($other, 'sanctum')->postJson("/api/stages/admin/evaluations/{$req->id}", ['scores' => ['qualite' => 4] + $scores, 'comment' => 'Correct, quelques retards de remise.'])->assertOk(); // 16
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/evaluations/{$req->id}/validation", ['appreciation' => 'Stage réussi, bonne progression.'])
            ->assertOk()->assertJsonPath('data.final', 17); // 0,6 × 17 + 0,4 × 16 = 16,60 → 17,00
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === $req->whatsapp && str_contains($s['text'], '17,00/20')));
        $this->postJson("/api/stages/admin/evaluations/{$req->id}", ['scores' => $scores, 'comment' => 'Trop tard pour noter.'])->assertStatus(409);

        // Stagiaire : sa note, sans noms ni commentaires, et son bulletin
        $mine = $this->getJson('/api/stagiaire/evaluation', $h)->assertOk()->json('data');
        $this->assertEquals(17, $mine['final']);
        $this->assertStringNotContainsString('sérieux', json_encode($mine));
        $this->getJson('/api/stagiaire/evaluation/pdf', $h)->assertOk()->assertJsonPath('mime', 'application/pdf');
    }
}
