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

/** Module Stages - Lot 10 : stages terminés, binôme de stagiaires terminés, rapport final commun, invitation en binôme. */
class StagesLot10Test extends TestCase
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

    /** Stagiaire invité, inscrit avec les dates données : [stage, en-têtes de son espace]. */
    private function registered(string $phone, string $email, string $start = '2026-03-02', string $end = '2026-05-29', string $nom = 'adjovi'): array
    {
        $r = $this->postJson('/api/stages/rejoindre', $this->form($this->invite($phone), $start, $end, ['email' => $email, 'nom' => $nom]))->assertStatus(201);

        return [StageRequest::where('reference', $r->json('data.reference'))->first(), ['X-Stagiaire-Token' => $r->json('data.token'), 'Accept' => 'application/json']];
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('memoire.pdf', 300, 'application/pdf');
    }

    public function test_finished_stage_has_no_end_button_nor_date_change(): void
    {
        $this->at('2026-10-22 10:00');
        [$a, $ha] = $this->registered('0191000101', 'a@example.org');
        $lead = $this->lead();

        $this->assertFalse(app(\App\Modules\Stages\Services\StageEndService::class)->state($a->fresh(), $lead)['can_end']);
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/demandes/{$a->id}/fin", ['type' => 'revocation', 'motif_type' => 'autre', 'motif' => 'Essai'])
            ->assertStatus(409);
        $this->postJson('/api/stagiaire/fin-de-stage', ['password' => 'Stage2026'], $ha)->assertStatus(409);
        $this->postJson('/api/stagiaire/demandes', ['kind' => 'prolongation', 'new_date_fin' => '2026-12-31', 'motif' => 'Rattrapage'], $ha)
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'terminé'));
    }

    public function test_finished_interns_share_one_final_report(): void
    {
        $this->at('2026-10-22 10:00');
        [$a, $ha] = $this->registered('0191000201', 'a2@example.org', nom: 'agossou');
        [$b, $hb] = $this->registered('0191000202', 'b2@example.org', '2026-03-09', '2026-06-05', 'bio');
        [$c] = $this->registered('0191000203', 'c2@example.org', '2026-09-01', '2026-12-18', 'chabi');
        $lead = $this->lead();

        // Terminés proposés parmi les stagiaires sans binôme ; jamais avec un stagiaire en cours
        $free = collect($this->actingAs($lead, 'sanctum')->getJson('/api/stages/admin/equipes')->assertOk()->json('data.candidates'))->keyBy('reference');
        $this->assertSame([true, true, false], [$free[$a->reference]['termine'], $free[$b->reference]['termine'], $free[$c->reference]['termine']]);
        $this->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id, $c->id]])->assertStatus(422);
        $this->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id, $b->id], 'final_mode' => 'commun'])->assertStatus(201);
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === '+2290191000202' && str_contains($m['text'], 'Rapport final commun')));
        $team = $this->getJson('/api/stages/admin/equipes')->json('data.teams.0');
        $this->assertSame(['commun', false], [$team['final_mode'], $team['final_locked']]);
        // Les stagiaires ne proposent pas de binôme pour un stage terminé
        $this->getJson('/api/stagiaire/binome', $hb)->assertOk()->assertJsonPath('data.can_invite', false)->assertJsonPath('data.team.final_mode', 'commun');

        // A dépose le mémoire (fiche de fin de formation) : il vaut pour B
        $this->getJson('/api/stagiaire/rapports', $ha)->assertOk()->assertJsonPath('data.binome.final_commun', true);
        $id = $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf(), 'pour_binome' => '0',
            'fin_formation' => '1', 'theme' => 'Supervision d\'un réseau', 'resume' => str_repeat('Résumé du mémoire commun. ', 3),
            'consentement_publication' => '1'], $ha)->assertStatus(201)->json('data.id');
        $twin = StageReport::where('stage_request_id', $b->id)->where('period_key', 'FINAL')->first();
        $this->assertNotNull($twin);
        $this->assertSame($id, $twin->pair_report_id);
        $this->assertNotNull($this->getJson('/api/stagiaire/rapports', $hb)->json('data.bibliotheque'));

        // Une seule fiche, deux auteurs
        $work = \App\Modules\Bibliotheque\Models\LibraryWork::with('people')->where('source_type', 'stage_report')->get();
        $this->assertCount(1, $work);
        $this->assertSame(2, $work[0]->people->where('role', 'auteur')->count());

        // Plus de changement de type une fois déposé
        $this->actingAs($lead, 'sanctum')->patchJson("/api/stages/admin/equipes/{$team['id']}/rapport-final", ['final_mode' => 'individuel'])->assertStatus(409);

        // Invités, stage terminé : aucune condition d'assiduité, seul le rapport final manque
        $att = app(\App\Modules\Stages\Services\AttestationService::class);
        $e = $att->eligibility($a->fresh());
        $this->assertSame([false, true, false, ['Rapport final pas encore validé.']], [$e['attendance_required'], $e['attendance_ok'], $e['eligible'], $e['missing']]);

        // Une validation pour les deux, attestation possible pour chacun
        $this->postJson("/api/stages/admin/rapports/{$id}/decision", ['decision' => 'approved'])->assertOk();
        $this->assertTrue($att->eligibility($a->fresh())['eligible']);
        $this->assertTrue($att->eligibility($b->fresh())['eligible']);
        // Proposé une seule fois aux travaux à publier
        $work[0]->delete();
        $offered = collect(app(\App\Modules\Bibliotheque\Services\LibraryService::class)->candidates()['reports'])->whereIn('reference', [$a->reference, $b->reference]);
        $this->assertCount(1, $offered);
        $this->assertStringContainsString(' et ', $offered->first()['authors']);
    }

    public function test_individual_final_report_stays_individual(): void
    {
        $this->at('2026-10-22 10:00');
        [$a, $ha] = $this->registered('0191000301', 'a3@example.org', nom: 'agossou');
        [$b] = $this->registered('0191000302', 'b3@example.org', nom: 'bio');
        $lead = $this->lead();
        $teamId = $this->actingAs($lead, 'sanctum')->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id, $b->id]])->assertStatus(201)->json('data.id');
        $this->assertSame('individuel', DB::table('stage_teams')->where('id', $teamId)->value('final_mode'));

        $this->post('/api/stagiaire/rapports', ['period_key' => 'FINAL', 'fichier' => $this->pdf(), 'pour_binome' => '1'], $ha)->assertStatus(201);
        $this->assertFalse(StageReport::where('stage_request_id', $b->id)->exists());
        $this->actingAs($lead, 'sanctum')->patchJson("/api/stages/admin/equipes/{$teamId}/rapport-final", ['final_mode' => 'commun'])->assertStatus(409);
    }

    public function test_pair_invitation_forms_the_binome_with_same_dates_and_supervisor(): void
    {
        $this->at('2026-10-22 10:00');
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->postJson('/api/stages/admin/invitations-stagiaires', ['numeros' => '0191000401', 'binome' => true])->assertStatus(422);
        $this->postJson('/api/stages/admin/invitations-stagiaires', ['numeros' => "0191000401\n0191000402", 'binome' => true, 'rapport_final' => 'commun'])
            ->assertOk()->assertJsonCount(2, 'data.invited');
        $links = collect($this->sent)->filter(fn ($s) => str_contains($s['text'], '/stage/rejoindre/') && str_contains($s['text'], 'en binôme'))->values();
        $this->assertCount(2, $links);
        $token = fn (int $i) => preg_match('#rejoindre/([A-Za-z0-9]{16})\b#', $links[$i]['text'], $m) ? $m[1] : null;
        $first = $links[0]['to'] === '+2290191000401' ? 0 : 1;

        // Le premier s'inscrit et déclare les dates ; il attend son binôme
        $this->getJson('/api/stages/rejoindre?token=' . $token($first))->assertOk()->assertJsonPath('data.binome.partner', null);
        $r = $this->postJson('/api/stages/rejoindre', $this->form($token($first), '2026-03-02', '2026-05-29', ['email' => 'p1@example.org']))->assertStatus(201);
        $a = StageRequest::where('reference', $r->json('data.reference'))->first();
        $ha = ['X-Stagiaire-Token' => $r->json('data.token'), 'Accept' => 'application/json'];
        $this->assertNotNull($this->getJson('/api/stagiaire/binome', $ha)->assertOk()->json('data.awaiting'));
        $sup = $this->user('superviseur');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $a->id, 'user_id' => $sup->id, 'assigned_by' => $lead->id, 'created_at' => now()]);

        // Le second retrouve les dates du premier ; elles s'imposent
        $this->getJson('/api/stages/rejoindre?token=' . $token(1 - $first))->assertOk()
            ->assertJsonPath('data.binome.partner.date_debut', '2026-03-02')->assertJsonPath('data.binome.final_mode', 'commun');
        $r = $this->postJson('/api/stages/rejoindre', $this->form($token(1 - $first), '2026-04-01', '2026-04-30', ['email' => 'p2@example.org', 'nom' => 'bio']))->assertStatus(201);
        $b = StageRequest::where('reference', $r->json('data.reference'))->first();

        $this->assertSame(['2026-03-02', '2026-05-29'], [$b->date_debut->toDateString(), $b->date_fin->toDateString()]);
        $this->assertNotNull($b->team_id);
        $this->assertSame($a->fresh()->team_id, $b->team_id);
        $this->assertSame('commun', DB::table('stage_teams')->where('id', $b->team_id)->value('final_mode'));
        $this->assertTrue(DB::table('stage_supervisions')->where('stage_request_id', $b->id)->where('user_id', $sup->id)->exists());
        $this->assertNull($this->getJson('/api/stagiaire/binome', $ha)->json('data.awaiting'));
    }

    public function test_lonely_pair_invitation_is_reported_once(): void
    {
        $this->at('2026-10-22 10:00');
        $this->actingAs($this->lead(), 'sanctum')
            ->postJson('/api/stages/admin/invitations-stagiaires', ['numeros' => "0191000501\n0191000502", 'binome' => true])->assertOk();
        $wa = collect($this->sent)->first(fn ($s) => $s['to'] === '+2290191000501' && str_contains($s['text'], '/stage/rejoindre/'));
        preg_match('#rejoindre/([A-Za-z0-9]{16})\b#', $wa['text'], $m);
        $this->postJson('/api/stages/rejoindre', $this->form($m[1], '2026-03-02', '2026-05-29'))->assertStatus(201);

        $svc = app(\App\Modules\Stages\Services\InternInvitationService::class);
        $this->assertSame(0, $svc->closeLonelyPairs()); // l'autre lien est encore valable
        $this->at('2026-10-30 09:00');
        $this->sent = [];
        $this->assertSame(1, $svc->closeLonelyPairs());
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === '+2290191000501' && str_contains($s['text'], 'ne s\'est pas inscrit')));
        $this->assertSame(0, $svc->closeLonelyPairs());
    }
}
