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

/** Module Stages - Lot 4 : rapports périodiques et final, espace tâches. */
class StagesLot4Test extends TestCase
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

        // Rapports ouverts à tous : un autre stagiaire le lit, sans pouvoir y déposer
        [, $other] = $this->intern();
        $this->getJson("/api/stagiaire/rapports/{$id}", $other)->assertOk()->assertJsonPath('data.mine', false);
        $this->getJson('/api/stagiaire/rapports/999999', $other)->assertStatus(404);
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
        $this->getJson("/api/stages/admin/rapports/{$id}/versions/1?from=0&size=1024")->assertOk()
            ->assertJsonStructure(['name', 'mime', 'total', 'from', 'content'])->assertJsonPath('mime', 'application/pdf');
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

        // Nouvelle version → de nouveau à examiner : le superviseur titulaire et le responsable qui a demandé
        // la correction sont prévenus, une seule fois chacun ; le stagiaire répond
        Queue::fake();
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $h)->assertStatus(201);
        $corrected = fn (?string $phone) => Queue::pushed(SendNotificationJob::class, fn ($j) => ($j->payload['phone'] ?? null) === $phone
            && str_contains(json_encode($j->payload, JSON_UNESCAPED_UNICODE), 'Rapport corrigé'))->count();
        $this->assertSame([1, 1], [$corrected($sup->phone), $corrected($lead->phone)]);
        $this->postJson("/api/stagiaire/rapports/{$id}/commentaires", ['body' => 'Schéma ajouté page 4'], $h)->assertOk();
        $d = $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])
            ->assertOk()->json('data');
        $this->assertSame('approved', $d['status']);
        $this->assertSame(['superviseur', 'responsable', 'responsable'], array_column($d['reviews'], 'role'));
        $this->assertSame(['staff', 'stagiaire'], array_column($d['comments'], 'author'));
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'Rapport validé')));
    }

    public function test_pair_report_shared_between_binome(): void
    {
        [$a, $ha] = $this->intern();
        [$b, $hb] = $this->intern();
        $b->update(['nom' => 'DOSSOU', 'prenoms' => 'Ines']);
        $team = DB::table('stage_teams')->insertGetId(['name' => 'Binôme test', 'created_at' => now(), 'updated_at' => now()]);
        $a->update(['team_id' => $team]);
        $b->update(['team_id' => $team]);
        $lead = $this->lead();
        $sup = $this->user('superviseur');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $a->id, 'user_id' => $sup->id, 'created_at' => now()]);
        DB::table('stage_supervisions')->insert(['stage_request_id' => $b->id, 'user_id' => $sup->id, 'created_at' => now()]);

        $this->at('2026-11-06 16:00');
        $this->assertStringContainsString('Ines', (string) $this->getJson('/api/stagiaire/rapports', $ha)->json('data.binome.name'));

        // Dépôt pour le binôme (case cochée par défaut)
        $idA = $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $ha)
            ->assertStatus(201)->json('data.id');
        $twin = StageReport::where('stage_request_id', $b->id)->where('period_key', 'S2026-W45')->first();
        $this->assertNotNull($twin);
        $this->assertSame([$twin->id, $idA], [StageReport::find($idA)->pair_report_id, $twin->pair_report_id]);
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'Rapport déposé pour votre binôme')));
        $sideB = $this->getJson("/api/stagiaire/rapports/{$twin->id}", $hb)->assertOk()->json('data');
        $this->assertSame(1, $sideB['current_version']);
        $this->assertNotNull($sideB['binome']['deposited_by']);
        $this->assertNotNull($sideB['versions'][0]['by']);
        $this->getJson("/api/stagiaire/rapports/{$twin->id}/versions/1", $hb)->assertOk()->assertJsonStructure(['content']);

        // Le personnel voit une seule ligne ; commentaire et décision valent pour les deux
        $this->actingAs($sup, 'sanctum');
        $list = $this->getJson('/api/stages/admin/rapports?mine=1')->assertOk();
        $this->assertCount(1, $list->json('data'));
        $this->assertSame(1, $list->json('to_review'));
        $this->postJson("/api/stages/admin/rapports/{$idA}/commentaires", ['body' => 'Ajoutez le planning'])->assertOk();
        $this->postJson("/api/stages/admin/rapports/{$idA}/decision", ['decision' => 'rejected', 'comment' => 'Trop court'])->assertOk();
        $this->assertSame('supervisor_rejected', $twin->fresh()->status);
        $this->assertSame(['Ajoutez le planning'], array_column($this->getJson("/api/stagiaire/rapports/{$twin->id}", $hb)->json('data.comments'), 'body'));

        // Le binôme dépose la correction : elle vaut pour les deux
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $hb)->assertStatus(201);
        $this->assertSame([2, 'submitted'], [StageReport::find($idA)->current_version, StageReport::find($idA)->status]);
        $this->postJson("/api/stagiaire/rapports/{$twin->id}/commentaires", ['body' => 'Planning ajouté'], $hb)->assertOk();
        $c = $this->getJson("/api/stagiaire/rapports/{$idA}", $ha)->json('data.comments');
        $this->assertSame(['DOSSOU Ines', false], [end($c)['name'], end($c)['mine']]);
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$twin->id}/decision", ['decision' => 'approved'])->assertOk();
        $this->assertSame(['approved', 2], [StageReport::find($idA)->status, StageReport::find($idA)->official_version]);
        // Validé : les versions précédentes (et leurs fichiers) sont supprimées, des deux côtés
        $this->assertSame([[2], [2]], [
            StageReportVersion::where('report_id', $idA)->pluck('version')->all(),
            StageReportVersion::where('report_id', $twin->id)->pluck('version')->all(),
        ]);
        // v1 (déposée par Koffi) effacée ; v2 (déposée par Ines) gardée, commune aux deux rapports
        $this->assertSame([0, 1], [count(Storage::disk('local')->allFiles("stages/{$a->reference}/rapports")),
            count(Storage::disk('local')->allFiles("stages/{$b->reference}/rapports"))]);
        $this->getJson("/api/stagiaire/rapports/{$idA}/versions/1", $ha)->assertStatus(404);
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $ha)
            ->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_LOCKED');

        // Le binôme a déjà déposé le sien : pas de dépôt commun, sauf case décochée
        $this->at('2026-11-13 16:00');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W46', 'fichier' => $this->pdf(), 'pour_binome' => '0'], $hb)
            ->assertStatus(201)->assertJsonPath('data.binome', null);
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W46', 'fichier' => $this->pdf()], $ha)
            ->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REPORT_PAIR_EXISTS');
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W46', 'fichier' => $this->pdf(), 'pour_binome' => '0'], $ha)
            ->assertStatus(201)->assertJsonPath('data.binome', null);

        // Binôme dissous : chacun reprend son rapport, sans lien actif
        $b->update(['team_id' => null]);
        $this->assertNull($this->getJson("/api/stagiaire/rapports/{$idA}", $ha)->json('data.binome'));
    }

    public function test_reports_open_to_all_comments_grouped_and_period_announced(): void
    {
        [$a, $ha] = $this->intern();
        [$b, $hb] = $this->intern();
        $b->update(['nom' => 'DOSSOU', 'prenoms' => 'Ines']);
        [$c] = $this->intern();
        $sup = $this->user('superviseur');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $a->id, 'user_id' => $sup->id, 'created_at' => now()]);
        $lead = $this->lead();

        // Premier dépôt de la période : annonce à tous (une seule fois), pas à l'auteur
        $this->at('2026-11-06 16:00');
        Queue::fake();
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $ha)->assertStatus(201)->json('data.id');
        $notice = fn () => collect($this->sent)->filter(fn ($s) => str_contains($s['text'], 'Période de consultation des rapports'));
        $this->assertEqualsCanonicalizing([$b->whatsapp, $c->whatsapp], $notice()->pluck('to')->all());
        $this->assertStringContainsString('de la semaine du 2 novembre', $notice()->first()['text']);
        $this->assertSame($b->email, $notice()->firstWhere('to', $b->whatsapp)['fallback'], 'email de secours');
        Queue::assertPushed(SendNotificationJob::class, fn ($job) => str_contains(json_encode($job->payload, JSON_UNESCAPED_UNICODE), 'Période de consultation'));
        $this->post('/api/stagiaire/rapports', ['period_key' => 'S2026-W45', 'fichier' => $this->pdf()], $hb)->assertStatus(201);
        $this->assertCount(2, $notice(), 'période déjà annoncée');

        // Rapports de tous : une puce par stagiaire, les rapports de la personne choisie
        $board = $this->getJson('/api/stagiaire/rapports/tous', $hb)->assertOk()->json('data');
        $this->assertSame(1, collect($board['people'])->firstWhere('id', $a->id)['reports']);
        $this->assertSame([$id], array_column($this->getJson("/api/stagiaire/rapports/tous?stagiaire={$a->id}", $hb)->json('data.reports'), 'id'));

        // Un autre stagiaire commente : rien tout de suite, un message par rafale de 10 minutes
        $this->sent = [];
        $this->postJson("/api/stagiaire/rapports/{$id}/commentaires", ['body' => 'Bon travail sur la sauvegarde'], $hb)->assertOk();
        $cid = $this->postJson("/api/stagiaire/rapports/{$id}/commentaires", ['body' => 'Et la partie réseau ?'], $hb)->assertOk()->json('data.comments.1.id');
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/commentaires", ['body' => 'Ajoutez le schéma'])->assertOk();
        $reports = app(ReportService::class);
        $this->at('2026-11-06 16:05');
        $this->assertSame(0, $reports->flushComments());
        $this->assertSame([], $this->sent);
        $this->at('2026-11-06 16:11');
        $this->artisan('stages:process-reports --comments')->assertSuccessful();
        $toA = collect($this->sent)->where('to', $a->whatsapp)->values();
        $this->assertCount(1, $toA);
        $this->assertStringContainsString('3 nouveaux commentaires', $toA[0]['text']);
        $this->assertStringContainsString('DOSSOU Ines : Bon travail', $toA[0]['text']);
        $this->assertSame(0, DB::table('stage_digest_items')->count());

        // L'auteur répond : le stagiaire qui avait commenté est prévenu (pas l'auteur lui-même)
        $this->sent = [];
        $this->postJson("/api/stagiaire/rapports/{$id}/commentaires", ['body' => 'Merci, je complète la partie réseau'], $ha)->assertOk();
        $this->at('2026-11-06 16:25');
        $this->artisan('stages:process-reports --comments')->assertSuccessful();
        $this->assertSame([$b->whatsapp], collect($this->sent)->filter(fn ($s) => str_contains($s['text'], 'Nouvelle réponse sur un rapport'))->pluck('to')->values()->all());
        $this->assertFalse(collect($this->sent)->contains('to', $a->whatsapp));

        // Côté auteur du commentaire : « mine » et suppression ; pas les autres
        $d = $this->getJson("/api/stagiaire/rapports/{$id}", $hb)->assertOk()->json('data');
        $this->assertSame([false, true, true, false], [$d['mine'], $d['comments'][0]['mine'], $d['comments'][0]['can_delete'], $d['comments'][2]['can_delete']]);
        $this->deleteJson("/api/stagiaire/rapports/{$id}/commentaires/{$cid}", [], $ha)->assertStatus(403);
        $this->deleteJson("/api/stagiaire/rapports/{$id}/commentaires/{$cid}", [], $hb)->assertOk();
        $first = $d['comments'][0]['id'];
        $this->actingAs($lead, 'sanctum')->deleteJson("/api/stages/admin/rapports/{$id}/commentaires/{$first}")->assertOk();
        $this->assertSame(['Ajoutez le schéma', 'Merci, je complète la partie réseau'], array_column($this->getJson("/api/stagiaire/rapports/{$id}", $ha)->json('data.comments'), 'body'));

        // La nuit, les messages aux stagiaires attendent 7h
        $this->at('2026-11-06 22:30');
        $this->sent = [];
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])->assertOk();
        $msg = collect($this->sent)->firstWhere('to', $a->whatsapp);
        $this->assertSame('2026-11-07 07:00', CarbonImmutable::instance($msg['at'])->setTimezone(BusinessHours::TZ)->format('Y-m-d H:i'));
        $this->assertSame($a->email, $msg['fallback']);
        $this->assertFalse(collect($this->sent)->contains('channel', 'email'), 'WhatsApp seul, email en secours');
    }

    public function test_tasks_submit_review_publish_and_remind(): void
    {
        [$a, $ha] = $this->intern();
        [$b, $hb] = $this->intern();
        [$c, $hc] = $this->intern();
        $b->update(['nom' => 'DOSSOU', 'prenoms' => 'Ines']);
        $team = DB::table('stage_teams')->insertGetId(['name' => 'Binôme', 'created_at' => now(), 'updated_at' => now()]);
        $a->update(['team_id' => $team]);
        $b->update(['team_id' => $team]);
        $sup = $this->user('superviseur');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $a->id, 'user_id' => $sup->id, 'created_at' => now()]);
        $this->at('2026-11-09 10:00');

        // Le superviseur confie une tâche au binôme ; les deux sont prévenus
        $this->actingAs($sup, 'sanctum');
        $this->assertSame('DOSSOU Ines', collect($this->getJson('/api/stages/admin/taches')->assertOk()->json('data.interns'))->firstWhere('id', $a->id)['binome']);
        $this->postJson('/api/stages/admin/taches', ['title' => 'Hier', 'stage_request_id' => $a->id, 'due_date' => '2026-11-01'])->assertStatus(422);
        $this->postJson('/api/stages/admin/taches', ['title' => 'Ce matin', 'stage_request_id' => $a->id, 'due_date' => '2026-11-09', 'due_time' => '08:00'])->assertStatus(422);
        $d = $this->postJson('/api/stages/admin/taches', ['title' => 'Ce soir', 'stage_request_id' => $a->id, 'due_date' => '2026-11-09'])->assertStatus(201)->json('data');
        $this->assertSame('18:00', $d['due_time'], 'heure par défaut');
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'lundi 9 novembre à 18h00')));
        $this->deleteJson("/api/stages/admin/taches/{$d['id']}")->assertOk();
        $this->sent = [];
        $id = $this->postJson('/api/stages/admin/taches', ['title' => 'Maquette de la page d\'accueil', 'instructions' => 'Version téléphone et ordinateur.',
            'stage_request_id' => $a->id, 'pour_binome' => true, 'due_date' => '2026-11-13', 'due_time' => '10:30'])->assertStatus(201)->json('data.id');
        $this->assertSame(2, collect($this->sent)->filter(fn ($s) => str_contains($s['text'], 'Nouvelle tâche'))->count());

        // Visible du binôme, pas d'un autre stagiaire
        $this->assertSame([$id], array_column($this->getJson('/api/stagiaire/taches', $hb)->json('data.tasks'), 'id'));
        $this->getJson("/api/stagiaire/taches/{$id}", $hc)->assertStatus(404);

        // Remise : fichier non accepté, lien invalide, puis remise correcte
        $this->post("/api/stagiaire/taches/{$id}/remise", ['files' => [UploadedFile::fake()->create('virus.exe', 10)]], $ha)->assertStatus(422);
        $this->post("/api/stagiaire/taches/{$id}/remise", ['links' => ['javascript:alert(1)']], $ha)->assertStatus(422);
        $this->post("/api/stagiaire/taches/{$id}/remise", ['files' => [UploadedFile::fake()->create('trop.pdf', 21000, 'application/pdf')]], $ha)->assertStatus(422);
        $d = $this->post("/api/stagiaire/taches/{$id}/remise", ['files' => [$this->pdf()], 'links' => ['https://figma.com/x'], 'message' => 'Voici la v1'], $ha)
            ->assertOk()->json('data');
        $this->assertSame(['submitted', 1, 2], [$d['status'], $d['version'], count($d['items'])]);
        $first = $d['items'][0]['id'];
        $this->assertCount(1, Storage::disk('local')->allFiles("stages/taches/{$id}"));
        $this->getJson("/api/stagiaire/taches/{$id}/fichiers/{$first}?from=0&size=10", $hb)->assertOk()->assertJsonStructure(['name', 'total', 'content']);

        // À revoir (commentaire obligatoire), puis nouvelle remise : seule la dernière est gardée
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/taches/{$id}/decision", ['decision' => 'revision'])->assertStatus(422);
        $this->postJson("/api/stages/admin/taches/{$id}/publication", ['public' => true])->assertStatus(409);
        $this->postJson("/api/stages/admin/taches/{$id}/decision", ['decision' => 'revision', 'comment' => 'Revoir le menu'])->assertOk();
        $this->assertSame('revision', $this->getJson("/api/stagiaire/taches/{$id}", $hb)->json('data.status'));
        $d = $this->post("/api/stagiaire/taches/{$id}/remise", ['files' => [$this->pdf()]], $hb)->assertOk()->json('data');
        $this->assertSame([2, 1], [$d['version'], count($d['items'])]);
        $this->assertCount(1, Storage::disk('local')->allFiles("stages/taches/{$id}"));
        $this->getJson("/api/stagiaire/taches/{$id}/fichiers/{$first}", $ha)->assertStatus(404);

        // Validé puis rendu public : visible de tous, annoncé une fois le soir
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/taches/{$id}/decision", ['decision' => 'validated'])->assertOk();
        $this->post("/api/stagiaire/taches/{$id}/remise", ['files' => [$this->pdf()]], $ha)->assertStatus(409);
        $this->postJson("/api/stages/admin/taches/{$id}/publication", ['public' => true])->assertOk()->assertJsonPath('data.published', true);
        $this->assertSame([$id], array_column($this->getJson('/api/stagiaire/taches/publies', $hc)->json('data'), 'id'));
        $this->getJson("/api/stagiaire/taches/{$id}", $hc)->assertOk()->assertJsonPath('data.can_submit', false);
        $this->sent = [];
        $this->at('2026-11-09 17:45');
        $this->artisan('stages:process-reports --digest')->assertSuccessful();
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === $c->whatsapp && str_contains($s['text'], 'Nouveau travail publié')));
        $this->assertSame(0, app(TaskService::class)->announcePublished(), 'annoncé une seule fois');

        // Tâche créée par le stagiaire : supprimable avant remise ; rappel la veille de l'échéance
        $own = $this->postJson('/api/stagiaire/taches', ['title' => 'Lire la doc Sanctum', 'due_date' => '2026-11-11'], $hc)->assertStatus(201)->json('data');
        $this->assertSame([true, 'intern'], [$own['can_delete'], $own['created_by']['type']]);
        $this->postJson('/api/stagiaire/taches', ['title' => 'x', 'stage_request_id' => $a->id], $hc)->assertStatus(422);
        $this->deleteJson("/api/stagiaire/taches/{$id}", [], $ha)->assertStatus(403);
        $this->sent = [];
        $this->at('2026-11-10 09:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === $c->whatsapp && str_contains($s['text'], 'Tâche à rendre demain')));
        $this->assertSame(0, app(TaskService::class)->remindDue(CarbonImmutable::parse('2026-11-10')), 'un seul rappel');
        $this->deleteJson("/api/stagiaire/taches/{$own['id']}", [], $hc)->assertOk();
    }

    public function test_task_invitations(): void
    {
        [$a, $ha] = $this->intern();
        [$w, $hw] = $this->intern();
        [$v, $hv] = $this->intern();
        [$o, $ho] = $this->intern();
        $sup = $this->user('superviseur');
        $other = $this->user('superviseur');
        $guest = $this->user('superviseur');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $a->id, 'user_id' => $sup->id, 'created_at' => now()]);
        $this->at('2026-11-09 10:00');
        $this->actingAs($sup, 'sanctum');
        $id = $this->postJson('/api/stages/admin/taches', ['title' => 'API des stocks', 'stage_request_id' => $a->id])->assertStatus(201)->json('data.id');

        // Ses stagiaires d'abord
        $list = $this->getJson('/api/stages/admin/taches/invitables')->assertOk()->json('data');
        $this->assertSame([$a->id, true], [$list['interns'][0]['id'], $list['interns'][0]['mine']]);
        $this->assertFalse(collect($list['staff'])->contains('id', $sup->id));

        // Le superviseur invite : un stagiaire pour travailler (compte pour lui), un pour voir, un collègue pour voir
        $this->postJson("/api/stages/admin/taches/{$id}/invitations", ['type' => 'intern', 'id' => $w->id, 'role' => 'worker', 'counts' => true])->assertOk();
        $this->postJson("/api/stages/admin/taches/{$id}/invitations", ['type' => 'intern', 'id' => $v->id, 'role' => 'viewer'])->assertOk();
        $this->postJson("/api/stages/admin/taches/{$id}/invitations", ['type' => 'intern', 'id' => $a->id, 'role' => 'viewer'])->assertStatus(409);
        $d = $this->postJson("/api/stages/admin/taches/{$id}/invitations", ['type' => 'staff', 'id' => $guest->id])->assertOk()->json('data');
        $this->assertCount(3, $d['invited']);
        $this->assertSame([$a->id, $w->id], array_column($d['members'], 'id'), 'auteurs : titulaire et invité qui compte');
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === $v->whatsapp && str_contains($s['text'], 'Invitation sur une tâche')));

        // Invités : l'un remet, l'autre consulte seulement ; un stagiaire extérieur ne voit rien
        $this->getJson("/api/stagiaire/taches/{$id}", $hv)->assertOk()->assertJsonPath('data.can_submit', false)->assertJsonPath('data.my_role', 'viewer');
        $this->post("/api/stagiaire/taches/{$id}/remise", ['links' => ['https://github.com/x']], $hv)->assertStatus(403);
        $this->getJson("/api/stagiaire/taches/{$id}", $ho)->assertStatus(404);
        $this->post("/api/stagiaire/taches/{$id}/remise", ['links' => ['https://github.com/x']], $hw)->assertOk();
        $this->assertContains($id, array_column($this->getJson('/api/stagiaire/taches', $hw)->json('data.tasks'), 'id'));

        // Personnel invité pour voir : la tâche est dans « mes » tâches, sans pouvoir la valider ; un autre superviseur non plus
        $this->actingAs($guest, 'sanctum');
        $this->assertSame([$id], array_column($this->getJson('/api/stages/admin/taches?mine=1')->json('data.tasks'), 'id'));
        $this->getJson("/api/stages/admin/taches/{$id}")->assertOk()->assertJsonPath('data.can_review', false);
        $this->postJson("/api/stages/admin/taches/{$id}/decision", ['decision' => 'validated'])->assertStatus(403);
        $this->actingAs($other, 'sanctum')->postJson("/api/stages/admin/taches/{$id}/invitations", ['type' => 'staff', 'id' => $other->id, 'full_power' => true])->assertStatus(403);

        // Plein pouvoir accordé : il valide
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/taches/{$id}/invitations", ['type' => 'staff', 'id' => $guest->id, 'full_power' => true])->assertOk();
        $this->actingAs($guest, 'sanctum')->postJson("/api/stages/admin/taches/{$id}/decision", ['decision' => 'validated'])->assertOk();

        // Le titulaire invite aussi, sans pouvoir faire compter le travail ; il retire une invitation
        $d = $this->postJson("/api/stagiaire/taches/{$id}/invitations", ['type' => 'intern', 'id' => $o->id, 'role' => 'worker', 'counts' => true], $ha)->assertOk()->json('data');
        $this->assertFalse(collect($d['invited'])->firstWhere('id', $o->id)['counts']);
        $this->assertNotContains($o->id, array_column($d['members'], 'id'));
        $this->postJson("/api/stagiaire/taches/{$id}/invitations", ['type' => 'intern', 'id' => $a->id], $hw)->assertStatus(403);
        $this->deleteJson("/api/stagiaire/taches/{$id}/invitations/intern/{$v->id}", [], $ha)->assertOk();
        $this->getJson("/api/stagiaire/taches/{$id}", $hv)->assertStatus(404);
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
            'token_hash' => hash('sha256', $token), 'device_hash' => $this->deviceHash(), 'expires_at' => now()->addYear(), 'created_at' => now(),
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

        // Toujours pas déposé le jour ouvré suivant : un rappel de retard, une seule fois
        $this->at('2026-11-11 08:00');
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->artisan('stages:process-reports')->assertSuccessful();
        $this->assertSame(1, collect($this->sent)->where('channel', 'whatsapp')->filter(fn ($s) => str_contains($s['text'], 'Rapport en retard'))->count());

        // Fréquence journalière : récapitulatif du soir au responsable
        app(StageSettingsService::class)->update(['report_frequency' => 'journalier'], null);
        [, $h2] = $this->intern('2026-11-09', '2026-12-31');
        $this->at('2026-11-10 12:00');
        Queue::fake();
        $this->post('/api/stagiaire/rapports', ['period_key' => 'J2026-11-10', 'fichier' => $this->pdf()], $h2)->assertStatus(201);
        // Pas de message au superviseur par dépôt journalier (seule l'annonce de la période part)
        Queue::assertNotPushed(SendNotificationJob::class, fn ($job) => str_contains(json_encode($job->payload, JSON_UNESCAPED_UNICODE), 'Rapport déposé'));
        $this->at('2026-11-10 17:45');
        $this->artisan('stages:process-reports --digest')->assertSuccessful();
        Queue::assertPushed(SendNotificationJob::class, fn ($j) => str_contains(json_encode($j->payload, JSON_UNESCAPED_UNICODE), 'Rapports journaliers'));
    }
}
