<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
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
 * Module Stages - Lot 1 : dépôt, suivi par code, proposition / contre-
 * proposition, note de service (initiale + rectificative), rejet, échéances,
 * droits d'accès, heures ouvrées du personnel.
 */
class StagesLot1Test extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, array{channel: string, to: ?string, text: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
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
                $this->test->record('email', $to, $subject . ' ' . json_encode($vars, JSON_UNESCAPED_UNICODE) . ($attachments ? ' [PJ:' . implode(',', array_column($attachments, 'name')) . ']' : ''));
            }
        });
    }

    public function record(string $channel, ?string $to, string $text): void
    {
        $this->sent[] = ['channel' => $channel, 'to' => $to, 'text' => $text];
    }

    // ── Outils ──────────────────────────────────────────────────────────

    private function user(string $slug, ?string $divisionType = null): User
    {
        $roleId = DB::table('roles')->where('slug', $slug)->value('id')
            ?? DB::table('roles')->insertGetId(['uuid' => Str::uuid(), 'name' => $slug, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('users')->insertGetId([
            'uuid' => Str::uuid(), 'last_name' => strtoupper($slug), 'first_name' => 'Test',
            'email' => $slug . uniqid() . '@example.org', 'password' => bcrypt('x'), 'phone' => '0197000001',
            'chef_division_type' => $divisionType, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_user')->insert(['role_id' => $roleId, 'user_id' => $id]);

        return User::find($id);
    }

    private function manager(): User
    {
        return $this->user('responsable-division', 'formation_continue');
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'civilite' => 'M.', 'nom' => 'AGOSSA', 'prenoms' => 'Jean <b>Marie</b>',
            'date_naissance' => '2002-05-10', 'lieu_naissance' => 'Cotonou', 'nationalite' => 'Béninoise',
            'etablissement_origine' => 'UAC', 'filiere_niveau' => 'Licence 3 Informatique',
            'email' => 'Jean.Agossa' . random_int(100, 999) . '@example.org',
            'telephone' => '01 97 ' . random_int(10, 99) . ' 44 55',
            'duree_stage' => '3_mois', 'consentement' => '1',
            'cv' => UploadedFile::fake()->create('cv.pdf', 120, 'application/pdf'),
            'lettre_motivation' => UploadedFile::fake()->create('lettre.pdf', 80, 'application/pdf'),
            'acte_naissance' => UploadedFile::fake()->image('acte.jpg'),
        ], $override);
    }

    private function submit(array $override = []): StageRequest
    {
        $ref = $this->post('/api/stages/demandes', $this->payload($override), ['Accept' => 'application/json'])
            ->assertStatus(201)->json('data.reference');

        return StageRequest::where('reference', $ref)->firstOrFail();
    }

    private function suiviToken(StageRequest $req): string
    {
        $this->sent = [];
        $this->postJson('/api/stages/suivi/code', ['reference' => $req->reference])->assertOk()->assertJsonPath('data.sent', true);
        $wa = collect($this->sent)->firstWhere('channel', 'whatsapp');
        $this->assertNotNull($wa);
        preg_match('/\*(\d{6})\*/', $wa['text'], $m);

        return $this->postJson('/api/stages/suivi/code/verifier', ['reference' => $req->reference, 'code' => $m[1]])
            ->assertOk()->json('data.token');
    }

    private function terms(string $start, string $end): array
    {
        return [
            'service' => 'Service Informatique', 'lieu' => 'CAP-EPAC',
            'date_debut' => $start, 'date_fin' => $end,
            'horaires' => ['arrivee' => '08:00', 'pause_debut' => '13:00', 'pause_fin' => '15:00', 'sortie' => '18:00'],
        ];
    }

    // ── Tests ───────────────────────────────────────────────────────────

    public function test_public_submission_is_stored_privately_and_notifies(): void
    {
        $req = $this->submit();

        $this->assertMatchesRegularExpression('/^STG-[A-Z2-9]{6}$/', $req->reference);
        $this->assertSame('submitted', $req->status);
        $this->assertSame('Jean Marie', $req->prenoms, 'les balises sont retirées');
        $this->assertStringStartsWith('+229', $req->whatsapp);
        foreach (['cv', 'lettre_motivation', 'acte_naissance'] as $k) {
            Storage::disk('local')->assertExists($req->files[$k]);
        }
        $this->assertSame(['submitted'], $req->history()->pluck('action')->all());
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['channel'] === 'whatsapp' && str_contains($s['text'], $req->reference)));
    }

    public function test_submission_refused_when_active_duplicate_or_closed_or_invalid(): void
    {
        $req = $this->submit();

        $this->post('/api/stages/demandes', $this->payload(['email' => $req->email]), ['Accept' => 'application/json'])
            ->assertStatus(409)->assertJsonPath('error_code', 'STAGE_REQUEST_ALREADY_ACTIVE');

        $this->post('/api/stages/demandes', $this->payload(['cv' => UploadedFile::fake()->create('x.exe', 10, 'application/octet-stream')]), ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('cv');

        $this->actingAs($this->manager(), 'sanctum')
            ->putJson('/api/stages/admin/reglages', ['requests_open' => false])->assertOk();
        $this->getJson('/api/stages/statut')->assertOk()->assertJsonPath('data.open', false);
        Cache::flush(); // limiteur anti-spam (3 dépôts / minute) remis à zéro
        $this->post('/api/stages/demandes', $this->payload(), ['Accept' => 'application/json'])
            ->assertStatus(403)->assertJsonPath('error_code', 'STAGE_REQUESTS_CLOSED');
    }

    public function test_access_is_limited_to_formation_continue_manager_and_admin(): void
    {
        $this->actingAs($this->user('responsable-division', 'formation_distance'), 'sanctum')
            ->getJson('/api/stages/admin/demandes')->assertStatus(403);
        $this->actingAs($this->user('secretaire'), 'sanctum')
            ->getJson('/api/stages/admin/demandes')->assertStatus(403);
        $this->actingAs($this->user('chef-division', 'formation_continue'), 'sanctum')
            ->getJson('/api/stages/admin/demandes')->assertOk();
        $this->actingAs($this->user('admin'), 'sanctum')
            ->getJson('/api/stages/admin/demandes')->assertOk();
        $this->getJson('/api/stages/suivi?reference=STG-AAAAAA')->assertStatus(401);
    }

    public function test_proposal_counter_proposal_note_and_rectification(): void
    {
        $req     = $this->submit(['date_debut_souhaitee' => BusinessHours::today()->addDays(10)->toDateString()]);
        $manager = $this->manager();
        $start   = BusinessHours::today()->addDays(14)->toDateString();
        $end     = BusinessHours::today()->addDays(14)->addMonths(2)->toDateString();

        // Proposition différente de la demande → envoyée au demandeur
        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/stages/admin/demandes/{$req->id}/proposition", $this->terms($start, $end) + ['message' => 'Bienvenue'])
            ->assertOk()->assertJsonPath('data.status', 'proposal_sent');

        // Suivi : accès uniquement avec le code
        $this->getJson("/api/stages/suivi?reference={$req->reference}", ['X-Suivi-Token' => 'st_faux'])->assertStatus(401);
        $token = $this->suiviToken($req);
        $this->getJson("/api/stages/suivi?reference={$req->reference}", ['X-Suivi-Token' => $token])
            ->assertOk()->assertJsonPath('data.can_respond', true)->assertJsonPath('data.proposal.message', 'Bienvenue');

        // Contre-proposition du demandeur
        $cStart = BusinessHours::today()->addDays(21)->toDateString();
        $cEnd   = BusinessHours::today()->addDays(21)->addMonths(2)->toDateString();
        $this->postJson('/api/stages/suivi/reponse', [
            'reference' => $req->reference, 'action' => 'counter', 'date_debut' => $cStart, 'date_fin' => $cEnd, 'message' => 'Examens jusqu\'au 5',
        ], ['X-Suivi-Token' => $token])->assertOk()->assertJsonPath('data.status', 'counter_proposed');

        // Le responsable accepte la contre-proposition → note initiale
        $detail = $this->actingAs($manager, 'sanctum')
            ->postJson("/api/stages/admin/demandes/{$req->id}/accepter-contre-proposition")
            ->assertOk()->assertJsonPath('data.status', 'accepted')->json('data');
        $this->assertSame($cStart, $detail['terms']['date_debut']);
        $this->assertSame('Service Informatique', $detail['terms']['service']);
        $this->assertCount(1, $detail['notes']);
        $this->assertMatchesRegularExpression('/^NS-STG-\d{4}-\d{4}$/', $detail['notes'][0]['number']);

        // Rectification → note rectificative numérotée
        $rect = $this->terms($cStart, BusinessHours::today()->addDays(21)->addMonths(3)->toDateString());
        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/stages/admin/demandes/{$req->id}/rectification", $rect + ['motif' => 'Prolongation'])
            ->assertOk();
        $notes = $req->fresh()->notes;
        $this->assertSame(['initiale', 'rectificative'], $notes->pluck('kind')->all());
        $this->assertSame($notes[0]->number . '-R1', $notes[1]->number);
        $this->assertSame('Prolongation', $notes[1]->motif);

        // Rien de changé → refusé ; plus de proposition possible
        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/stages/admin/demandes/{$req->id}/rectification", $rect + ['motif' => 'x'])
            ->assertStatus(422)->assertJsonPath('error_code', 'STAGE_NOTHING_CHANGED');
        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/stages/admin/demandes/{$req->id}/proposition", $this->terms($start, $end))
            ->assertStatus(409);

        $this->assertSame(
            ['submitted', 'proposal_sent', 'counter_proposed', 'accepted_counter', 'rectified'],
            $req->history()->pluck('action')->all(),
        );
    }

    public function test_demandeur_accepts_proposal_and_note_is_issued(): void
    {
        $req = $this->submit();
        $start = BusinessHours::today()->addDays(7)->toDateString();
        $end   = BusinessHours::today()->addDays(7)->addMonths(1)->toDateString();
        $this->actingAs($this->manager(), 'sanctum')
            ->postJson("/api/stages/admin/demandes/{$req->id}/proposition", $this->terms($start, $end))->assertOk();

        $token = $this->suiviToken($req);
        $this->postJson('/api/stages/suivi/reponse', ['reference' => $req->reference, 'action' => 'accept'], ['X-Suivi-Token' => $token])
            ->assertOk()->assertJsonPath('data.status', 'accepted')->assertJsonPath('data.terms.date_debut', $start);

        $this->assertSame(1, $req->fresh()->notes()->count());
        Queue::assertPushed(SendNotificationJob::class); // responsable informé

        // Note de service en PDF jointe au WhatsApp et à l'email
        $note = $req->fresh()->notes()->first();
        Queue::assertPushed(SendNotificationJob::class, fn ($j) => $j->channel === 'whatsapp_file' && str_ends_with($j->payload['path'], '.pdf'));
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['channel'] === 'email' && str_contains($m['text'], '[PJ:Note de service ' . $note->number)));
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists('stages/notes/' . $note->number . '.pdf'));
    }

    public function test_proposal_identical_to_request_issues_note_directly(): void
    {
        $start = BusinessHours::today()->addDays(10);
        $req = $this->submit(['date_debut_souhaitee' => $start->toDateString(), 'duree_stage' => '2_mois']);

        $this->actingAs($this->manager(), 'sanctum')
            ->postJson("/api/stages/admin/demandes/{$req->id}/proposition",
                $this->terms($start->toDateString(), $start->addMonthsNoOverflow(2)->subDay()->toDateString()))
            ->assertOk()->assertJsonPath('data.status', 'accepted');

        $this->assertSame(['submitted', 'accepted_direct'], $req->history()->pluck('action')->all());
        $this->assertSame(1, $req->fresh()->notes()->count());
    }

    public function test_reject_requires_motif_and_informs_demandeur(): void
    {
        $req = $this->submit();
        $manager = $this->manager();

        $this->actingAs($manager, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/rejet", ['motif' => '  '])
            ->assertStatus(422);
        $this->actingAs($manager, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/rejet", ['motif' => 'Capacité atteinte'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'Capacité atteinte')));

        // Le formulaire reste ouvert : le même demandeur peut redéposer
        $this->getJson('/api/stages/statut')->assertJsonPath('data.open', true);
        $this->submit(['email' => $req->email]);
    }

    public function test_unanswered_proposal_is_reminded_then_expires(): void
    {
        $req = $this->submit();
        $start = BusinessHours::today()->addDays(30)->toDateString();
        $end   = BusinessHours::today()->addDays(30)->addMonths(1)->toDateString();
        $this->actingAs($this->manager(), 'sanctum')
            ->postJson("/api/stages/admin/demandes/{$req->id}/proposition", $this->terms($start, $end))->assertOk();

        $this->travel(3)->days();
        $this->sent = [];
        $this->artisan('stages:process-deadlines')->assertSuccessful();
        $this->assertNotNull($req->fresh()->proposal_reminded_at);
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => str_contains($s['text'], 'Rappel')));

        $this->artisan('stages:process-deadlines')->assertSuccessful(); // pas de 2e relance
        $this->assertSame(1, $req->history()->where('action', 'reminder_sent')->count());

        $this->travel(4)->days();
        $this->artisan('stages:process-deadlines')->assertSuccessful();
        $this->assertSame('expired', $req->fresh()->status);
    }

    public function test_staff_notifications_wait_for_business_hours(): void
    {
        $tz = BusinessHours::TZ;
        $this->assertNull(BusinessHours::nextStaffSlot(CarbonImmutable::parse('2026-10-20 10:00', $tz)));
        $this->assertSame('2026-10-26 07:30', BusinessHours::nextStaffSlot(CarbonImmutable::parse('2026-10-24 10:00', $tz))->format('Y-m-d H:i'));
        $this->assertSame('2026-10-21 07:30', BusinessHours::nextStaffSlot(CarbonImmutable::parse('2026-10-20 20:00', $tz))->format('Y-m-d H:i'));
        $this->assertSame('2026-10-20 07:30', BusinessHours::nextStaffSlot(CarbonImmutable::parse('2026-10-20 06:00', $tz))->format('Y-m-d H:i'));
        $this->assertSame('2026-10-26 07:30', BusinessHours::nextStaffSlot(CarbonImmutable::parse('2026-10-23 19:00', $tz))->format('Y-m-d H:i'));
    }

    public function test_admin_file_preview_and_detail(): void
    {
        $req = $this->submit();
        $this->actingAs($this->manager(), 'sanctum');

        $detail = $this->getJson("/api/stages/admin/demandes/{$req->reference}")->assertOk()->json('data');
        $this->assertCount(3, $detail['files']);
        $this->assertTrue($detail['can']['propose']);
        $this->assertNotEmpty($detail['suggested_terms']['date_debut']);
        $this->assertNotEmpty($detail['default_rejection_reason']);

        $this->getJson("/api/stages/admin/demandes/{$req->id}/pieces/cv?format=json")
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonStructure(['name', 'mime', 'content']);
        $this->getJson("/api/stages/admin/demandes/{$req->id}/pieces/inconnue")->assertStatus(404);
    }

    // ── Demande en binôme ───────────────────────────────────────────────

    /** Deux demandes liées dès le dépôt, confirmées par le binôme depuis son suivi. */
    private function pairedRequests(): array
    {
        $a = $this->submit(['telephone' => '01 97 11 22 31']);
        $this->post('/api/stages/demandes', $this->payload(['binome_reference' => 'STG-ZZZZZZ']), ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('error_code', 'STAGE_TEAM_REF_UNKNOWN');
        $b = $this->submit(['telephone' => '01 97 11 22 32', 'nom' => 'HOUNSOU', 'binome_reference' => strtolower($a->reference)]);
        $this->assertNull($b->team_id);

        $token = $this->suiviToken($a);
        $id = $this->getJson("/api/stages/suivi?reference={$a->reference}", ['X-Suivi-Token' => $token])->assertOk()
            ->assertJsonPath('data.binome.received.0.from.reference', $b->reference)->json('data.binome.received.0.id');
        $this->postJson("/api/stages/suivi/binome/{$id}", ['reference' => $a->reference, 'accept' => true], ['X-Suivi-Token' => $token])
            ->assertOk()->assertJsonPath('data.binome.team.partner.reference', $b->reference);
        $this->assertSame($a->fresh()->team_id, $b->fresh()->team_id);

        return [$a->fresh(), $b->fresh(), $token];
    }

    public function test_paired_requests_are_decided_together_with_same_terms(): void
    {
        [$a, $b, $tokenA] = $this->pairedRequests();
        $manager = $this->manager();
        $start = BusinessHours::today()->addDays(14)->toDateString();
        $end   = BusinessHours::today()->addDays(14)->addMonths(2)->toDateString();

        // Une proposition pour l'un part aux deux
        $this->actingAs($manager, 'sanctum')->getJson("/api/stages/admin/demandes/{$a->id}")->assertOk()
            ->assertJsonPath('data.pair.reference', $b->reference)->assertJsonPath('data.can.accept_counter', false);
        $this->postJson("/api/stages/admin/demandes/{$a->id}/proposition", $this->terms($start, $end))->assertOk();
        $this->assertSame(['proposal_sent', 'proposal_sent'], [$a->fresh()->status, $b->fresh()->status]);

        // Pas de contre-proposition en binôme
        $this->getJson("/api/stages/suivi?reference={$a->reference}", ['X-Suivi-Token' => $tokenA])->assertJsonPath('data.can_counter', false);
        $this->postJson('/api/stages/suivi/reponse', [
            'reference' => $a->reference, 'action' => 'counter',
            'date_debut' => BusinessHours::today()->addDays(30)->toDateString(), 'date_fin' => BusinessHours::today()->addDays(30)->addMonths(2)->toDateString(),
        ], ['X-Suivi-Token' => $tokenA])->assertStatus(409)->assertJsonPath('error_code', 'STAGE_PAIR_NO_COUNTER');

        // Chacun accepte : une note de service chacun, mêmes conditions
        $this->postJson('/api/stages/suivi/reponse', ['reference' => $a->reference, 'action' => 'accept'], ['X-Suivi-Token' => $tokenA])->assertOk();
        // Le binôme a accepté : seules ses conditions peuvent être proposées à l'autre
        $this->actingAs($manager, 'sanctum')->postJson("/api/stages/admin/demandes/{$b->id}/proposition",
            $this->terms($start, BusinessHours::today()->addDays(14)->addMonths(3)->toDateString()))->assertStatus(409);
        $tokenB = $this->suiviToken($b);
        $this->postJson('/api/stages/suivi/reponse', ['reference' => $b->reference, 'action' => 'accept'], ['X-Suivi-Token' => $tokenB])->assertOk();
        [$a, $b] = [$a->fresh(), $b->fresh()];
        $this->assertSame(['accepted', 'accepted'], [$a->status, $b->status]);
        $this->assertSame([1, 1], [$a->notes()->count(), $b->notes()->count()]);
        $this->assertSame(
            app(\App\Modules\Stages\Services\StageRequestService::class)->currentTerms($a),
            app(\App\Modules\Stages\Services\StageRequestService::class)->currentTerms($b),
        );

        // Rectification : les deux gardent les mêmes conditions
        $rect = $this->terms($start, BusinessHours::today()->addDays(14)->addMonths(3)->toDateString());
        $this->actingAs($manager, 'sanctum')->postJson("/api/stages/admin/demandes/{$a->id}/rectification", $rect + ['motif' => 'Prolongation'])->assertOk();
        $this->assertSame($rect['date_fin'], $b->fresh()->date_fin->toDateString());
        $this->assertSame('rectificative', $b->notes()->reorder('version', 'desc')->first()->kind);

        // Accepté : le binôme apparaît dans les binômes de stagiaires
        $teams = $this->getJson('/api/stages/admin/equipes')->assertOk()->json('data.teams');
        $this->assertCount(2, collect($teams)->firstWhere('id', $a->team_id)['members']);
    }

    public function test_paired_request_rejected_together_or_continues_alone_after_expiry(): void
    {
        [$a, $b] = $this->pairedRequests();
        $manager = $this->manager();

        // Non retenue : la décision vaut pour les deux, le binôme disparaît
        $this->actingAs($manager, 'sanctum')->postJson("/api/stages/admin/demandes/{$a->id}/rejet", ['motif' => 'Capacité atteinte'])->assertOk();
        $this->assertSame(['rejected', 'rejected'], [$a->fresh()->status, $b->fresh()->status]);
        $this->assertNull($a->fresh()->team_id);
        $this->assertNull($b->fresh()->team_id);
        $this->assertFalse(collect($this->sent)->contains(fn ($m) => str_contains($m['text'], 'ne poursuit pas')));
    }

    public function test_paired_request_expired_leaves_the_other_alone(): void
    {
        [$a, $b] = $this->pairedRequests();
        $start = BusinessHours::today()->addDays(30)->toDateString();
        $this->actingAs($this->manager(), 'sanctum')->postJson("/api/stages/admin/demandes/{$a->id}/proposition",
            $this->terms($start, BusinessHours::today()->addDays(30)->addMonths(1)->toDateString()))->assertOk();
        $tokenB = $this->suiviToken($b);
        $this->postJson('/api/stages/suivi/reponse', ['reference' => $b->reference, 'action' => 'accept'], ['X-Suivi-Token' => $tokenB])->assertOk();

        // A ne répond jamais : sa proposition expire, B continue seul (stage accepté) et en est informé
        $this->sent = [];
        $this->travel(8)->days();
        $this->artisan('stages:process-deadlines')->assertSuccessful();
        $this->assertSame(['expired', 'accepted'], [$a->fresh()->status, $b->fresh()->status]);
        $this->assertNull($b->fresh()->team_id);
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === $b->email && str_contains($m['text'], 'ne poursuit pas')));
    }
}
