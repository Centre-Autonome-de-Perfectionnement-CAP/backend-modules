<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module Stages - Lot 2 : invitations de superviseurs, droits des
 * superviseurs, attribution, binômes, groupe WhatsApp annuel.
 */
class StagesLot2Test extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, array{channel: string, to: ?string, text: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();
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

    public function record(string $channel, ?string $to, string $text): void
    {
        $this->sent[] = ['channel' => $channel, 'to' => $to, 'text' => $text];
    }

    private function user(string $slug, ?string $divisionType = null, string $phone = '0197000001'): User
    {
        $roleId = DB::table('roles')->where('slug', $slug)->value('id')
            ?? DB::table('roles')->insertGetId(['uuid' => Str::uuid(), 'name' => $slug, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('users')->insertGetId([
            'uuid' => Str::uuid(), 'last_name' => strtoupper($slug), 'first_name' => 'Test',
            'email' => $slug . uniqid() . '@example.org', 'password' => bcrypt('x'), 'phone' => $phone,
            'chef_division_type' => $divisionType, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_user')->insert(['role_id' => $roleId, 'user_id' => $id]);

        return User::find($id);
    }

    private function lead(): User
    {
        return $this->user('responsable-division', 'formation_continue', '0197112233');
    }

    private function intern(string $start, string $end, string $phone = '0196000001', string $status = 'accepted'): StageRequest
    {
        return StageRequest::create([
            'reference' => 'STG-' . strtoupper(Str::random(6)), 'status' => $status, 'civilite' => 'M.',
            'nom' => 'KOFFI' . random_int(10, 99), 'prenoms' => 'Ama', 'date_naissance' => '2002-01-01', 'lieu_naissance' => 'Cotonou',
            'nationalite' => 'Béninoise', 'etablissement_origine' => 'UAC', 'filiere_niveau' => 'L3',
            'duree_souhaitee' => '3_mois', 'email' => uniqid() . '@example.org', 'whatsapp' => '+229' . $phone,
            'files' => [], 'consent_at' => now(), 'service' => 'Service Informatique', 'lieu' => 'CAP-EPAC',
            'date_debut' => $start, 'date_fin' => $end,
            'horaires' => ['arrivee' => '08:00', 'pause_debut' => '13:00', 'pause_fin' => '15:00', 'sortie' => '18:00'],
        ]);
    }

    private function acceptInvitation(string $phone): User
    {
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->postJson('/api/stages/admin/invitations', ['numeros' => $phone])->assertOk();
        $msg = collect($this->sent)->last(fn ($s) => str_contains($s['text'], 'invitation-superviseur'));
        preg_match('/token=([A-Za-z0-9]{48})/', $msg['text'], $m);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/stages/invitation/accepter', [
            'token' => $m[1], 'prenom' => 'Sena', 'nom' => 'Dossou', 'email' => 'sup' . uniqid() . '@example.org',
            'password' => 'Secret123', 'password_confirmation' => 'Secret123',
        ])->assertStatus(201);

        return User::where('phone', '+229' . preg_replace('/\D/', '', $phone))->latest('id')->firstOrFail();
    }

    // ── Invitations ─────────────────────────────────────────────────────

    public function test_invitation_by_phone_creates_supervisor_account_once(): void
    {
        $lead = $this->lead();
        $res = $this->actingAs($lead, 'sanctum')->postJson('/api/stages/admin/invitations', [
            'numeros' => "01 97 55 44 33\n0196554433, 12",
        ])->assertOk()->json('data');
        $this->assertSame(['+2290197554433', '+2290196554433'], $res['invited']);
        $this->assertSame(['12'], $res['invalid']);

        $wa = collect($this->sent)->firstWhere('to', '+2290197554433');
        $this->assertStringContainsString('superviseur de stage', $wa['text']);
        preg_match('/token=([A-Za-z0-9]{48})/', $wa['text'], $m);
        $token = $m[1];

        $this->app['auth']->forgetGuards();
        $this->getJson("/api/stages/invitation?token={$token}")->assertOk()->assertJsonPath('data.phone', '•••••••••4433');
        $this->postJson('/api/stages/invitation/accepter', [
            'token' => $token, 'prenom' => 'Sena', 'nom' => 'Dossou', 'email' => 'sena@example.org',
            'password' => 'court', 'password_confirmation' => 'court',
        ])->assertStatus(422);
        $this->postJson('/api/stages/invitation/accepter', [
            'token' => $token, 'prenom' => 'Sena', 'nom' => 'Dossou', 'email' => 'sena' . uniqid() . '@example.org',
            'password' => 'Secret123', 'password_confirmation' => 'Secret123',
        ])->assertStatus(201);

        $user = User::where('phone', '+2290197554433')->firstOrFail();
        $this->assertSame('superviseur', $user->roles->first()->slug);
        $this->assertSame('DOSSOU', $user->last_name);

        // Lien à usage unique ; numéro déjà inscrit ignoré
        $this->getJson("/api/stages/invitation?token={$token}")->assertStatus(409)
            ->assertJsonPath('error_code', 'STAGE_INVITE_USED');
        $this->actingAs($lead, 'sanctum')->postJson('/api/stages/admin/invitations', ['numeros' => '0197554433'])
            ->assertOk()->assertJsonPath('data.already_supervisor', ['+2290197554433']);
    }

    public function test_resend_replaces_link_and_revoke_disables_it(): void
    {
        $lead = $this->lead();
        $this->actingAs($lead, 'sanctum')->postJson('/api/stages/admin/invitations', ['numeros' => '0197889900'])->assertOk();
        preg_match('/token=([A-Za-z0-9]{48})/', collect($this->sent)->last()['text'], $first);
        $id = DB::table('stage_supervisor_invitations')->where('phone', '+2290197889900')->value('id');

        $this->postJson("/api/stages/admin/invitations/{$id}/renvoyer")->assertOk();
        preg_match('/token=([A-Za-z0-9]{48})/', collect($this->sent)->last()['text'], $second);
        $this->assertNotSame($first[1], $second[1]);

        $this->deleteJson("/api/stages/admin/invitations/{$id}")->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/stages/invitation?token={$first[1]}")->assertStatus(410);
        $this->getJson("/api/stages/invitation?token={$second[1]}")->assertStatus(410);
    }

    // ── Droits du superviseur ───────────────────────────────────────────

    public function test_supervisor_is_confined_to_stages_module(): void
    {
        $sup = $this->user('superviseur', null, '0197445566');
        $token = $sup->createToken('t')->plainTextToken;
        $h = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/api/stages/admin/suivi', $h)->assertOk();
        $this->getJson('/api/stages/admin/demandes', $h)->assertOk();
        $this->getJson('/api/stages/admin/reglages', $h)->assertStatus(403);
        $this->postJson('/api/stages/admin/invitations', ['numeros' => '0197000000'], $h)->assertStatus(403);
        $this->getJson('/api/stages/admin/groupe-whatsapp', $h)->assertStatus(403);
        // Hors module Stages : refusé même sur une route « authentifié seulement »
        $this->getJson('/api/attestations/document-requests/stats', $h)->assertStatus(403);
        $this->getJson('/api/auth/me', $h)->assertOk();
    }

    public function test_supervisor_self_assigns_and_lead_is_informed(): void
    {
        $lead = $this->lead();
        $sup  = $this->user('superviseur', null, '0197445566');
        $other = $this->user('superviseur', null, '0197445567');
        $today = BusinessHours::today();
        $a = $this->intern($today->subDays(5)->toDateString(), $today->addMonths(2)->toDateString(), '0196000011');
        $pending = $this->intern($today->toDateString(), $today->addMonth()->toDateString(), '0196000012', 'submitted');

        $this->actingAs($sup, 'sanctum');
        $this->postJson('/api/stages/admin/stagiaires/superviseur', ['stage_ids' => [$a->id], 'supervisor_id' => $other->id, 'action' => 'add'])
            ->assertStatus(403);
        $this->postJson('/api/stages/admin/stagiaires/superviseur', ['stage_ids' => [$pending->id], 'supervisor_id' => $sup->id, 'action' => 'add'])
            ->assertStatus(422);
        $this->postJson('/api/stages/admin/stagiaires/superviseur', ['stage_ids' => [$a->id], 'supervisor_id' => $sup->id, 'action' => 'add'])
            ->assertOk()->assertJsonPath('data.changed', 1);
        Queue::assertPushed(SendNotificationJob::class, fn ($job) => str_contains(json_encode($job->payload), 'superviseur'));

        $list = $this->getJson('/api/stages/admin/suivi?scope=mine')->assertOk()->json();
        $this->assertSame([$a->id], array_column($list['data'], 'id'));
        $this->assertSame(1, $list['counts']['mine']);

        // Le responsable attribue un autre superviseur
        $this->actingAs($lead, 'sanctum')->postJson('/api/stages/admin/stagiaires/superviseur', [
            'stage_ids' => [$a->id], 'supervisor_id' => $other->id, 'action' => 'add',
        ])->assertOk();
        $this->assertSame(2, DB::table('stage_supervisions')->where('stage_request_id', $a->id)->count());
        $this->assertSame(0, $this->getJson('/api/stages/admin/suivi?scope=unsupervised')->json('counts.unsupervised'));

        // Liaison : le stagiaire reçoit le nom et le contact, le superviseur la liste
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === $a->whatsapp
            && str_contains($m['text'], 'Votre superviseur') && str_contains($m['text'], $other->phone)));
        Queue::assertPushed(SendNotificationJob::class, fn ($job) => ($job->payload['phone'] ?? null) === $other->phone
            && str_contains($job->payload['message'] ?? '', 'Stagiaires à suivre') && str_contains($job->payload['message'], $a->reference));

        // Retrait : le stagiaire est prévenu
        $this->postJson('/api/stages/admin/stagiaires/superviseur', ['stage_ids' => [$a->id], 'supervisor_id' => $other->id, 'action' => 'remove'])->assertOk();
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === $a->whatsapp && str_contains($m['text'], 'Changement de superviseur')));
    }

    public function test_supervisor_action_on_request_notifies_lead(): void
    {
        $this->lead();
        $sup = $this->user('superviseur', null, '0197445566');
        $req = $this->intern(BusinessHours::today()->addDays(20)->toDateString(), BusinessHours::today()->addDays(80)->toDateString(), '0196000013', 'submitted');

        Queue::fake();
        $this->actingAs($sup, 'sanctum')->postJson("/api/stages/admin/demandes/{$req->id}/rejet", ['motif' => 'Complet'])->assertOk();
        Queue::assertPushed(SendNotificationJob::class, fn ($job) => str_contains(json_encode($job->payload, JSON_UNESCAPED_UNICODE), 'Action d'));
    }

    // ── Binômes ─────────────────────────────────────────────────────────

    public function test_binome_is_exactly_two_free_interns(): void
    {
        $lead = $this->lead();
        $t = BusinessHours::today();
        $a = $this->intern($t->toDateString(), $t->addMonth()->toDateString(), '0196000021');
        $b = $this->intern($t->toDateString(), $t->addMonth()->toDateString(), '0196000022');
        $c = $this->intern($t->toDateString(), $t->addMonth()->toDateString(), '0196000023', 'submitted');
        $d = $this->intern($t->toDateString(), $t->addMonth()->toDateString(), '0196000024');
        $old = $this->intern($t->subMonths(3)->toDateString(), $t->subMonth()->toDateString(), '0196000025');

        $this->actingAs($lead, 'sanctum');
        $this->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id]])->assertStatus(422);
        $this->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id, $b->id, $d->id]])->assertStatus(422);
        $this->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id, $c->id]])->assertStatus(422);
        $this->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id, $old->id]])->assertStatus(422);
        $id = $this->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id, $b->id]])->assertStatus(201)->json('data.id');

        // Nom automatique ; les deux stagiaires sont prévenus
        $data = $this->getJson('/api/stages/admin/equipes')->assertOk()->json('data');
        $team = collect($data['teams'])->firstWhere('id', $id);
        $this->assertCount(2, $team['members']);
        $this->assertStringStartsWith('Binôme ', $team['name']);
        foreach (['+2290196000021', '+2290196000022'] as $phone) {
            $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === $phone && str_contains($m['text'], 'Vous êtes en binôme')));
        }

        // Déjà en binôme : plus proposé, et impossible à reprendre
        $this->assertSame([$d->id], collect($data['candidates'])->pluck('id')->intersect([$a->id, $b->id, $d->id, $old->id])->values()->all());
        $this->postJson('/api/stages/admin/equipes', ['stage_ids' => [$a->id, $d->id]])->assertStatus(409);

        $this->deleteJson("/api/stages/admin/equipes/{$id}")->assertOk();
        $this->assertNull($a->fresh()->team_id);
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === '+2290196000022' && str_contains($m['text'], 'Binôme défait')));
    }

    // ── Groupe WhatsApp annuel ──────────────────────────────────────────

    public function test_whatsapp_group_sync_adds_invites_and_removes(): void
    {
        $this->lead();
        $t = BusinessHours::today();
        $joined   = $this->intern($t->subDays(2)->toDateString(), $t->addMonth()->toDateString(), '0196000031');
        $private  = $this->intern($t->subDays(1)->toDateString(), $t->addMonth()->toDateString(), '0196000032');
        $upcoming = $this->intern($t->addDays(5)->toDateString(), $t->addMonths(2)->toDateString(), '0196000033');
        $calls = [];

        Http::fake(function ($request) use (&$calls) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $calls[] = [$path, $request->data()];
            return match ($path) {
                '/status'              => Http::response(['status' => 'connected', 'user' => ['id' => '22901000000:1@s.whatsapp.net']]),
                '/groups/create'       => Http::response(['success' => true, 'jid' => '120363000000000001@g.us', 'added' => [], 'not_added' => [], 'missing' => []]),
                '/groups/participants' => Http::response(['success' => true, 'results' => [
                    ($request->data()['participants'][0]) => str_ends_with($request->data()['participants'][0], '32') ? '403' : '200',
                ]]),
                '/groups/invite-code'  => Http::response(['success' => true, 'code' => 'AbCdEfGhIjKl', 'link' => 'https://chat.whatsapp.com/AbCdEfGhIjKl']),
                default                => Http::response(['success' => false], 404),
            };
        });

        $this->artisan('stages:sync-whatsapp-group')->assertSuccessful();

        $group = DB::table('stage_whatsapp_groups')->where('year', (int) $t->format('Y'))->first();
        $this->assertSame('120363000000000001@g.us', $group->jid);
        $create = collect($calls)->firstWhere(0, '/groups/create')[1];
        $this->assertSame(['22901971122' . '33'], $create['admins']);

        $this->assertNotNull($joined->fresh()->wa_added_at);
        $this->assertNull($private->fresh()->wa_added_at);
        $this->assertNotNull($private->fresh()->wa_invite_sent_at);
        $this->assertTrue(collect($this->sent)->contains(fn ($s) => $s['to'] === '+2290196000032' && str_contains($s['text'], 'chat.whatsapp.com')));
        $this->assertNull($upcoming->fresh()->wa_group_id);

        // Une semaine après la fin : retiré du groupe
        $joined->forceFill(['date_fin' => $t->subDays(8)->toDateString()])->save();
        $this->artisan('stages:sync-whatsapp-group')->assertSuccessful();
        $this->assertNotNull($joined->fresh()->wa_removed_at);
        $this->assertTrue(collect($calls)->contains(fn ($c) => $c[0] === '/groups/participants' && ($c[1]['action'] ?? '') === 'remove'));
    }

    public function test_whatsapp_group_link_rejects_bad_link_and_requires_lead(): void
    {
        $sup = $this->user('superviseur', null, '0197445566');
        $this->actingAs($sup, 'sanctum')->postJson('/api/stages/admin/groupe-whatsapp/lier', ['lien' => 'x'])->assertStatus(403);

        $this->actingAs($this->lead(), 'sanctum')
            ->postJson('/api/stages/admin/groupe-whatsapp/lier', ['lien' => 'https://exemple.com/abc'])
            ->assertStatus(422)->assertJsonPath('error_code', 'STAGE_WA_BAD_LINK');
    }
}
