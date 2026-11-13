<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Inscription\Services\WhatsAppBroadcastService;
use App\Modules\WhatsApp\Models\WaMessageLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Inscription : messages WhatsApp groupés aux étudiants cochés et aux numéros ajoutés. */
class InscriptionWhatsAppMessagesTest extends TestCase
{
    use DatabaseTransactions;

    private int $ay;
    private int $dept;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Storage::fake('local');
        $cycle = DB::table('cycles')->insertGetId(['uuid' => Str::uuid(), 'name' => 'Licence', 'created_at' => now(), 'updated_at' => now()]);
        $this->dept = DB::table('departments')->insertGetId(['uuid' => Str::uuid(), 'name' => 'Génie Civil', 'cycle_id' => $cycle, 'created_at' => now(), 'updated_at' => now()]);
        $this->ay = DB::table('academic_years')->insertGetId(['uuid' => Str::uuid(), 'academic_year' => '2025-2026', 'year_start' => '2025-10-01', 'year_end' => '2026-07-31', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function user(string $slug): User
    {
        $rid = DB::table('roles')->where('slug', $slug)->value('id')
            ?? DB::table('roles')->insertGetId(['uuid' => Str::uuid(), 'name' => $slug, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('users')->insertGetId(['uuid' => Str::uuid(), 'last_name' => 'TEST', 'first_name' => ucfirst($slug), 'email' => $slug . uniqid() . '@example.org',
            'password' => bcrypt('x'), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('role_user')->insert(['role_id' => $rid, 'user_id' => $id]);

        return User::find($id);
    }

    private function student(string $nom, ?string $phone, string $status = 'approved'): int
    {
        $pi = DB::table('personal_information')->insertGetId(['uuid' => Str::uuid(), 'last_name' => $nom, 'first_names' => 'Test', 'email' => Str::lower($nom) . '@example.org',
            'contacts' => json_encode($phone ? [$phone] : []), 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('pending_students')->insertGetId(['uuid' => Str::uuid(), 'tracking_code' => 'CAP-' . Str::upper(Str::random(6)), 'personal_information_id' => $pi,
            'academic_year_id' => $this->ay, 'department_id' => $this->dept, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** @return \Illuminate\Support\Collection<int, SendNotificationJob> */
    private function jobs(): \Illuminate\Support\Collection
    {
        return collect(Bus::dispatched(SendNotificationJob::class))->values();
    }

    public function test_only_checked_students_and_added_numbers_receive_once(): void
    {
        $a = $this->student('AGOSSOU', '0197000101');
        $b = $this->student('BIO', '0197000102');
        $this->student('CHABI', '0197000103'); // non coché : ne reçoit rien
        $c = $this->student('DOSSOU', null);
        $pending = $this->student('ZINSOU', '0197000104', 'pending'); // dossier non validé : ignoré

        $this->actingAs($this->user('secretaire'), 'sanctum');
        $res = $this->postJson('/api/inscription/messages-whatsapp', [
            'etudiants' => [$a, $b, $c, $pending],
            'numeros'   => "0197000101\n01 97 00 01 99 ; abc",
            'message'   => "Bonjour,\nRéunion demain à 10 h.",
        ])->assertOk();

        $this->assertSame(3, $res->json('data.recipients'));
        $this->assertSame(['abc'], $res->json('data.invalid_numbers'));
        $this->assertSame(['DOSSOU Test'], $res->json('data.without_phone'));
        $jobs = $this->jobs();
        $this->assertSame(['whatsapp'], $jobs->pluck('channel')->unique()->values()->all());
        $this->assertSame(['+2290197000101', '+2290197000102', '+2290197000199'], $jobs->map(fn ($j) => $j->payload['phone'])->sort()->values()->all());
        $this->assertSame("Bonjour,\nRéunion demain à 10 h.", $jobs[0]->payload['message']);
        $this->assertStringStartsWith('diffusion:', $jobs[0]->payload['context']);
    }

    public function test_message_first_then_each_file_and_files_are_purged(): void
    {
        $a = $this->student('AGOSSOU', '0197000101');
        $this->actingAs($this->user('admin'), 'sanctum');
        $this->post('/api/inscription/messages-whatsapp', [
            'etudiants' => [$a],
            'message'   => 'Voici le calendrier.',
            'fichiers'  => [UploadedFile::fake()->create('Calendrier 2026.pdf', 120, 'application/pdf'), UploadedFile::fake()->image('affiche.png')],
        ], ['Accept' => 'application/json'])->assertOk();

        $jobs = $this->jobs();
        $this->assertSame(['whatsapp_file', 'whatsapp_file'], $jobs->pluck('channel')->all());
        $this->assertSame(['Voici le calendrier.', ''], $jobs->map(fn ($j) => $j->payload['caption'])->all());
        $this->assertSame(['Calendrier 2026.pdf', 'affiche.png'], $jobs->map(fn ($j) => $j->payload['file_name'])->all());
        Storage::disk('local')->assertExists($jobs[0]->payload['path']);

        // Éphémère : rien n'est effacé tout de suite, tout l'est après quelques jours
        $svc = app(WhatsAppBroadcastService::class);
        $this->assertSame(0, $svc->purgeFiles());
        $this->travel(4)->days();
        touch(Storage::disk('local')->path($jobs[0]->payload['path']), now()->subDays(4)->getTimestamp());
        touch(Storage::disk('local')->path($jobs[1]->payload['path']), now()->subDays(4)->getTimestamp());
        $this->assertSame(1, $svc->purgeFiles());
        Storage::disk('local')->assertMissing($jobs[0]->payload['path']);
    }

    public function test_rights_validation_and_history(): void
    {
        $a = $this->student('AGOSSOU', '0197000101');
        $this->actingAs($this->user('comptable'), 'sanctum');
        $this->postJson('/api/inscription/messages-whatsapp', ['etudiants' => [$a], 'message' => 'x'])->assertStatus(403);
        $this->getJson('/api/inscription/messages-whatsapp')->assertStatus(403);

        $rd = $this->user('chef-division');
        $this->actingAs($rd, 'sanctum');
        $this->postJson('/api/inscription/messages-whatsapp', ['etudiants' => [$a], 'message' => '   '])->assertStatus(422);
        $this->postJson('/api/inscription/messages-whatsapp', ['numeros' => 'abc', 'message' => 'Salut'])->assertStatus(422)
            ->assertJsonPath('message', 'Aucun destinataire avec un numéro WhatsApp valide.');
        $this->post('/api/inscription/messages-whatsapp', ['etudiants' => [$a], 'message' => 'Salut',
            'fichiers' => [UploadedFile::fake()->create('virus.exe', 10)]], ['Accept' => 'application/json'])->assertStatus(422);

        $ctx = $this->postJson('/api/inscription/messages-whatsapp', ['etudiants' => [$a], 'numeros' => '0197000150', 'message' => 'Salut à tous'])
            ->assertOk()->json('data.recipients');
        $this->assertSame(2, $ctx);
        // Journal du module WhatsApp (écrit à l'envoi) : un reçu, un échec
        $context = $this->jobs()->last()->payload['context'];
        WaMessageLog::create(['recipient' => '22901970001010', 'message' => 'Salut à tous', 'context' => $context, 'module' => 'Inscription', 'status' => 'sent']);
        WaMessageLog::create(['recipient' => '2290197000150', 'message' => 'Salut à tous', 'context' => $context, 'module' => 'Inscription', 'status' => 'failed']);

        $h = $this->getJson('/api/inscription/messages-whatsapp')->assertOk()->json('data.0');
        $this->assertSame([2, 1, 1, 'Salut à tous', 'Chef-division TEST'], [$h['total'], $h['sent'], $h['failed'], $h['message'], $h['by']]);
    }
}
