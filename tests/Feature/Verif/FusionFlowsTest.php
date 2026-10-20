<?php

namespace App\Rules {
    // Le conteneur de test n'a pas de DNS : on simule un domaine existant.
    if (!\function_exists('App\\Rules\\checkdnsrr')) {
        function checkdnsrr(string $h, string $t = 'MX'): bool { return true; }
    }
}

namespace Tests\Feature\Verif {

use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class FusionFlowsTest extends TestCase
{
    use DatabaseTransactions;

    private int $deptLp;
    private int $secId;
    private int $rdFcId;

    protected function setUp(): void
    {
        parent::setUp();
        $cycleLp = DB::table('cycles')->insertGetId(['uuid' => Str::uuid(), 'name' => 'Licence Professionnelle', 'created_at' => now(), 'updated_at' => now()]);
        $this->deptLp = DB::table('departments')->insertGetId(['uuid' => Str::uuid(), 'name' => 'Génie Civil', 'cycle_id' => $cycleLp, 'created_at' => now(), 'updated_at' => now()]);

        $mkRole = fn ($slug) => DB::table('roles')->where('slug', $slug)->value('id')
            ?? DB::table('roles')->insertGetId(['uuid' => Str::uuid(), 'name' => $slug, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
        $mkUser = function ($slug, $type = null) use ($mkRole) {
            $id = DB::table('users')->insertGetId([
                'uuid' => Str::uuid(), 'last_name' => strtoupper($slug), 'first_name' => 'Test',
                'email' => $slug . uniqid() . '@example.org', 'password' => bcrypt('x'),
                'phone' => '0197000001', 'chef_division_type' => $type,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('role_user')->insert(['role_id' => $mkRole($slug), 'user_id' => $id]);
            return $id;
        };
        $this->secId = $mkUser('secretaire');
        $mkUser('responsable-division', 'formation_distance');
        $this->rdFcId = $mkUser('responsable-division', 'formation_continue');
    }

    private function jobs(string $channel): \Illuminate\Support\Collection
    {
        return collect(Bus::dispatched(SendNotificationJob::class))
            ->concat(Bus::dispatched(\App\Modules\Demandes\Jobs\SendNotificationJob::class))
            ->filter(fn ($j) => $j->channel === $channel)->values();
    }

    public function test_api_validation_errors_are_json_without_accept_header(): void
    {
        $r = $this->call('POST', '/api/contact/contact', ['name' => 'A']);
        $r->assertStatus(422);
        $this->assertStringContainsString('application/json', $r->headers->get('Content-Type'));
    }

    public function test_contact_mail_then_whatsapp_to_formation_continue_without_content(): void
    {
        Bus::fake([SendNotificationJob::class, \App\Modules\Demandes\Jobs\SendNotificationJob::class]);
        $r = $this->postJson('/api/contact', [
            'name' => "Jean\nDupont", 'email' => 'jean@example.org',
            'subject' => "Sujet\r\ninjection", 'message' => 'Bonjour, ceci est un message secret.',
        ]);
        $r->assertStatus(201)->assertJsonPath('success', true);

        $mail = $this->jobs('mailable');
        $this->assertCount(1, $mail);
        $this->assertCount(0, $this->jobs('whatsapp'), 'aucun WhatsApp avant envoi du mail');
        $after = $mail[0]->payload['after_sent'];
        $this->assertCount(1, $after, 'uniquement responsable Formation Continue');
        $this->assertStringNotContainsString('secret', $after[0]['payload']['message']);
        $this->assertStringNotContainsString('Sujet', $after[0]['payload']['message']);

        // Exécution du job email : le mail part puis le WhatsApp est publié
        Mail::fake();
        $mail[0]->handle(app(\App\Modules\WhatsApp\Services\WhatsAppBridgeClient::class));
        Mail::assertSent(\App\Modules\Contact\Mail\NewContactNotification::class, function ($m) {
            $env = $m->envelope();
            return !str_contains($env->subject, "\n") && count($env->replyTo) === 1;
        });
        $this->assertCount(1, $this->jobs('whatsapp'));
    }

    public function test_contact_admin_list_forbidden_to_non_staff(): void
    {
        $this->getJson('/api/contact/admin/contacts')->assertStatus(401);
    }

    public function test_payment_notification_view_renders(): void
    {
        $html = view('core::emails.notification', ['title' => 'T', 'body' => "Ligne 1\nMotif : <b>x</b>"])->render();
        $this->assertStringContainsString('Ligne 1<br />', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
        // Le service mail avec la clé "message" ne doit plus planter
        Mail::fake();
        $this->assertTrue(app(\App\Modules\Core\Services\MailService::class)->sendNotification('a@example.org', 'S', 'T', 'corps', ['message' => 'x']));
    }

    public function test_notifier_phone_extraction(): void
    {
        $n = app(\App\Modules\Core\Services\Notifier::class);
        $this->assertSame('97000001', $n->phoneFromContacts(['97000001']));
        $this->assertSame('0197000001', $n->phoneFromContacts('["0197000001"]'));
        $this->assertSame('0197000002', $n->phoneFromContacts(['phone' => '0197000002']));
        $this->assertSame('22997000003', $n->phoneFromContacts(json_encode(json_encode(['22997000003']))));
        $this->assertNull($n->phoneFromContacts(['abc', '12']));
    }

    public function test_legacy_full_flow(): void
    {
        Bus::fake([SendNotificationJob::class, \App\Modules\Demandes\Jobs\SendNotificationJob::class]);
        $email = 'ancien.etudiant@gmail.com';

        // OTP obligatoire
        $base = [
            'registrationYear' => 2019, 'matricule' => 'LG-2019-001', 'lastName' => 'Kpade', 'firstName' => 'Rose',
            'dateOfBirth' => '1998-04-12', 'cycle' => 'Licence Professionnelle', 'filiereId' => $this->deptLp,
            'email' => $email, 'phone' => '0197112233',
        ];
        $this->postJson('/api/legacy-students/register', $base)->assertStatus(422)->assertJsonValidationErrors('verification_token');

        Cache::flush();
        Mail::fake();
        app(OtpService::class)->sendOtp($email, 'legacy_student');
        $code = Cache::get('otp_' . md5($email . '_legacy_student'))['code'];
        $token = app(OtpService::class)->verifyOtp($email, $code, 'legacy_student')['verification_token'];

        $r = $this->postJson('/api/legacy-students/register', $base + ['verification_token' => $token]);
        $r->assertStatus(200)->assertJsonPath('dossier_status', 'pending');
        $legacyId = $r->json('id');
        // D4 : secrétaire + RD formation_distance (LP) + accusé étudiant
        $emails = $this->jobs('email')->reject(fn ($j) => str_contains($j->payload['subject'], 'Code de vérification'))->pluck('payload.to')->all();
        $this->assertContains($email, $emails);
        $this->assertCount(3, $emails, 'étudiant + secrétaire + RD formation à distance');

        // Statut attestations : tous types
        $s = $this->getJson('/api/attestations/status?matricule=lg-2019-001');
        $s->assertOk()->assertJsonPath('data.is_legacy', true);
        $this->assertCount(3, $s->json('data.documents'));

        // Soumission attestation d'inscription
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('public');
        $file = fn () => \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $sub = $this->post('/api/attestations/demandes', [
            'matricule' => 'LG-2019-001', 'type' => 'attestation_inscription', 'email' => $email,
            'whatsapp' => '0197112233', 'demande_manuscrite' => $file(), 'recu_paiement' => $file(), 'quittance' => $file(),
        ], ['Accept' => 'application/json']);
        $sub->assertStatus(201);
        $ref = $sub->json('reference');
        $row = DB::table('document_requests')->where('reference', $ref)->first();
        $this->assertEquals($legacyId, $row->legacy_student_id);
        $this->assertNull($row->student_pending_student_id);

        // Doublon refusé
        $this->post('/api/attestations/demandes', [
            'matricule' => 'LG-2019-001', 'type' => 'attestation_inscription', 'email' => $email,
            'whatsapp' => '0197112233', 'demande_manuscrite' => $file(), 'recu_paiement' => $file(), 'quittance' => $file(),
        ], ['Accept' => 'application/json'])->assertStatus(409);

        // Bulletin : année libre
        $b = $this->post('/api/attestations/bulletins', [
            'legacy_matricule' => 'LG-2019-001', 'bulletin_year' => '2019-2020', 'type' => 'bulletin_annuel',
            'email' => $email, 'whatsapp' => '0197112233', 'demande_manuscrite' => $file(), 'acte_naissance' => $file(), 'quittance' => $file(),
        ], ['Accept' => 'application/json']);
        $b->assertStatus(201);
        $this->post('/api/attestations/bulletins', [
            'legacy_matricule' => 'LG-2019-001', 'bulletin_year' => '2019-2021', 'type' => 'bulletin_annuel',
            'email' => $email, 'whatsapp' => '0197112233',
        ], ['Accept' => 'application/json'])->assertStatus(422);
        $bs = $this->getJson('/api/attestations/bulletin-status?matricule=LG-2019-001');
        $bs->assertOk()->assertJsonPath('data.free_year', true)->assertJsonPath('data.years.0.academic_year', '2019-2020');

        // Suivi
        $this->getJson('/api/attestations/demandes/suivi?reference=' . $ref)
            ->assertOk()->assertJsonPath('is_legacy', true)->assertJsonPath('student.matricule', 'LG-2019-001');

        // Progiciel : listing secrétaire avec identité de l'ancien étudiant
        $sec = \App\Models\User::find($this->secId);
        $list = app(\App\Modules\Demandes\Services\DocumentRequestQueryService::class)->listing('secretaire', $sec, []);
        $item = $list->firstWhere('reference', $ref);
        $this->assertNotNull($item);
        $this->assertEquals('KPADE', $item->last_name);
        $this->assertEquals('LG-2019-001', $item->matricule);
        $this->assertEquals('pending', $item->legacy_declaration_status);
        $detail = app(\App\Modules\Demandes\Services\DocumentRequestQueryService::class)->findOrFail($row->id);
        $this->assertEquals($legacyId, $detail->legacy_id);

        // D1 : Valider -> Comptable bloqué tant que la déclaration n'est pas validée
        $this->actingAs($sec, 'sanctum');
        $t = $this->postJson("/api/attestations/document-requests/{$row->id}/transition", ['action' => 'secretaire_validate']);
        $this->assertContains($t->status(), [409], $t->getContent());

        // Validation de la déclaration depuis le dossier
        $this->postJson("/api/admin/legacy-students/{$legacyId}/validate")->assertOk();
        $t2 = $this->postJson("/api/attestations/document-requests/{$row->id}/transition", ['action' => 'secretaire_validate']);
        $t2->assertOk();
        $this->assertEquals('accounting_review', DB::table('document_requests')->where('id', $row->id)->value('status'));

        // Rejet de la déclaration : cascade sur les demandes ouvertes (le bulletin)
        $this->postJson("/api/admin/legacy-students/{$legacyId}/reject", ['reason' => 'Matricule introuvable'])->assertOk()
            ->assertJsonCount(2, 'rejected_references');
        $this->assertEquals(0, DB::table('document_requests')->where('legacy_student_id', $legacyId)->whereNotIn('status', ['rejected', 'picked_up'])->count());
    }

    public function test_legacy_admin_write_forbidden_for_division(): void
    {
        $rd = \App\Models\User::find($this->rdFcId);
        $id = DB::table('legacy_students')->insertGetId(['matricule' => 'X1', 'last_name' => 'A', 'first_name' => 'B', 'enrollment_year' => 2018, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($rd, 'sanctum');
        $this->getJson('/api/admin/legacy-students')->assertOk();
        $this->postJson("/api/admin/legacy-students/{$id}/validate")->assertStatus(403);
    }

    public function test_complement_requires_whatsapp_code(): void
    {
        Bus::fake([SendNotificationJob::class, \App\Modules\Demandes\Jobs\SendNotificationJob::class]);
        Cache::flush();
        $legacy = DB::table('legacy_students')->insertGetId(['matricule' => 'C1', 'last_name' => 'A', 'first_name' => 'B', 'enrollment_year' => 2018, 'status' => 'validated', 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('document_requests')->insertGetId([
            'reference' => 'ATT-TESTC1', 'legacy_student_id' => $legacy, 'type' => 'attestation_inscription', 'status' => 'submitted',
            'email' => 'c1@example.org', 'demandeur_whatsapp' => '+2290197000009', 'created_at' => now(), 'updated_at' => now(), 'submitted_at' => now(),
        ]);
        \Illuminate\Support\Facades\Storage::fake('public');
        $pieces = ['pieces' => ['demande_manuscrite' => \Illuminate\Http\UploadedFile::fake()->create('a.pdf', 50, 'application/pdf')]];
        $base = ['reference' => 'ATT-TESTC1', 'email' => 'x@example.org', 'whatsapp' => '0197000009'];

        $this->post('/api/attestations/demandes/complement', $base + $pieces, ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/api/attestations/demandes/complement', $base + $pieces + ['complement_token' => 'faux'], ['Accept' => 'application/json'])->assertStatus(403);

        $send = $this->postJson('/api/attestations/demandes/complement/otp', ['reference' => 'ATT-TESTC1'])->assertOk();
        $wa = $this->jobs('whatsapp')->first();
        $this->assertEquals('+2290197000009', $wa->payload['phone'], 'code envoyé au numéro ENREGISTRÉ');
        preg_match('/\*(\d{6})\*/', $wa->payload['message'], $m);
        $this->postJson('/api/attestations/demandes/complement/otp', ['reference' => 'ATT-TESTC1'])->assertStatus(429);
        $this->postJson('/api/attestations/demandes/complement/otp/verify', ['reference' => 'ATT-TESTC1', 'code' => '000000'])->assertStatus(422);
        $tok = $this->postJson('/api/attestations/demandes/complement/otp/verify', ['reference' => 'ATT-TESTC1', 'code' => $m[1]])->assertOk()->json('data.complement_token');

        $pieces2 = ['pieces' => ['demande_manuscrite' => \Illuminate\Http\UploadedFile::fake()->create('a.pdf', 50, 'application/pdf')]];
        $this->post('/api/attestations/demandes/complement', $base + $pieces2 + ['complement_token' => $tok], ['Accept' => 'application/json'])->assertStatus(201);
        // jeton à usage unique
        $pieces3 = ['pieces' => ['demande_manuscrite' => \Illuminate\Http\UploadedFile::fake()->create('a.pdf', 50, 'application/pdf')]];
        $this->post('/api/attestations/demandes/complement', $base + $pieces3 + ['complement_token' => $tok], ['Accept' => 'application/json'])->assertStatus(403);
    }

    private function regularStudent(): array
    {
        $ay = DB::table('academic_years')->insertGetId(['uuid' => Str::uuid(), 'academic_year' => '2025-2026', 'year_start' => '2025-10-01', 'year_end' => '2026-07-31', 'created_at' => now(), 'updated_at' => now()]);
        $pi = DB::table('personal_information')->insertGetId(['uuid' => Str::uuid(), 'last_name' => 'AGBO', 'first_names' => 'Luc', 'email' => 'luc@example.org', 'contacts' => json_encode(['0197334455']), 'created_at' => now(), 'updated_at' => now()]);
        $ps = DB::table('pending_students')->insertGetId(['uuid' => Str::uuid(), 'tracking_code' => 'CAP-TEST' . rand(100, 999), 'personal_information_id' => $pi, 'academic_year_id' => $ay, 'department_id' => $this->deptLp, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
        $st = DB::table('students')->insertGetId(['uuid' => Str::uuid(), 'student_id_number' => 'REG' . rand(1000, 9999), 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]);
        $sps = DB::table('student_pending_student')->insertGetId(['uuid' => Str::uuid(), 'student_id' => $st, 'pending_student_id' => $ps, 'created_at' => now(), 'updated_at' => now()]);
        return compact('ay', 'pi', 'ps', 'st', 'sps');
    }

    public function test_regular_student_request_listing_and_transition_unchanged(): void
    {
        Bus::fake([SendNotificationJob::class, \App\Modules\Demandes\Jobs\SendNotificationJob::class]);
        $r = $this->regularStudent();
        $id = DB::table('document_requests')->insertGetId([
            'reference' => 'ATT-REG001', 'student_pending_student_id' => $r['sps'], 'type' => 'attestation_inscription', 'status' => 'submitted',
            'email' => 'luc@example.org', 'demandeur_whatsapp' => '+2290197334455', 'created_at' => now(), 'updated_at' => now(), 'submitted_at' => now(),
        ]);
        $cid = DB::table('users')->insertGetId(['uuid' => Str::uuid(), 'last_name' => 'COMPTA', 'first_name' => 'Test', 'email' => 'compta@example.org', 'password' => 'x', 'phone' => '0197000077', 'created_at' => now(), 'updated_at' => now()]);
        $rid = DB::table('roles')->where('slug', 'comptable')->value('id') ?? DB::table('roles')->insertGetId(['uuid' => Str::uuid(), 'name' => 'comptable', 'slug' => 'comptable', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('role_user')->insert(['role_id' => $rid, 'user_id' => $cid]);
        $sec = \App\Models\User::find($this->secId);
        $item = app(\App\Modules\Demandes\Services\DocumentRequestQueryService::class)->listing('secretaire', $sec, ['search' => 'AGBO'])->firstWhere('id', $id);
        $this->assertEquals('AGBO', $item->last_name);
        $this->assertEquals('Génie Civil', $item->department);
        $this->assertEquals(0, (int) $item->is_legacy);
        $this->actingAs($sec, 'sanctum');
        $this->postJson("/api/attestations/document-requests/{$id}/transition", ['action' => 'secretaire_validate'])->assertOk();
        // une action non autorisée renvoie bien 403 (et plus 500)
        $this->postJson("/api/attestations/document-requests/{$id}/transition", ['action' => 'secretaire_deliver'])->assertStatus(403);
        $this->assertContains('compta@example.org', $this->jobs('email')->pluck('payload.to')->all(), 'notification acteur suivant');
        $this->assertContains('0197000077', $this->jobs('whatsapp')->pluck('payload.phone')->all());
    }

    private function fakeBridge(bool $connected, bool $ok = true): void
    {
        $this->app->instance(\App\Modules\WhatsApp\Services\WhatsAppBridgeClient::class, new class($connected, $ok) extends \App\Modules\WhatsApp\Services\WhatsAppBridgeClient {
            public array $sent = [];
            public function __construct(private bool $c, private bool $o) { parent::__construct(); }
            public function isConnected(): bool { return $this->c; }
            public function sendDetailed(string $phone, string $message, string $context = '', ?string $module = null): array
            { $this->sent[] = [$phone, $message]; return ['ok' => $this->o, 'retryable' => !$this->o, 'http' => $this->o ? 200 : 503, 'error' => $this->o ? null : 'down']; }
            public function sendFileDetailed(string $phone, string $disk, string $path, string $fileName, string $caption = '', string $context = '', ?string $module = null): array
            { $this->sent[] = [$phone, $fileName]; return ['ok' => true, 'retryable' => false, 'http' => 200, 'error' => null]; }
        });
    }

    private function contactDemande(): int
    {
        $r = $this->regularStudent();
        return DB::table('document_requests')->insertGetId([
            'reference' => 'ATT-CT' . random_int(1000, 9999), 'student_pending_student_id' => $r['sps'], 'type' => 'attestation_inscription', 'status' => 'submitted',
            'email' => 'luc@example.org', 'demandeur_whatsapp' => '+2290197334455', 'created_at' => now(), 'updated_at' => now(), 'submitted_at' => now(),
        ]);
    }

    public function test_contact_demandeur_is_synchronous_and_reports_success(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        Mail::fake();
        $this->fakeBridge(true);
        $id = $this->contactDemande();
        $this->actingAs(\App\Models\User::find($this->secId), 'sanctum');
        $res = $this->postJson("/api/attestations/document-requests/{$id}/contact-demandeur", ['message' => 'Bonjour, merci de passer au CAP.'])->assertOk()->json('data');
        $this->assertTrue($res['whatsapp']['success']);
        $this->assertFalse($res['whatsapp']['queued']);
        $this->assertTrue($res['email']['success']);
        $this->assertFalse($res['email']['queued']);
        $this->assertSame('0197334455', substr(preg_replace('/\D/', '', app(\App\Modules\WhatsApp\Services\WhatsAppBridgeClient::class)->sent[0][0]), -10));
        \Illuminate\Support\Facades\Queue::assertNothingPushed();
    }

    public function test_contact_demandeur_reports_whatsapp_failure(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        Mail::fake();
        $this->fakeBridge(false);
        $id = $this->contactDemande();
        $this->actingAs(\App\Models\User::find($this->secId), 'sanctum');
        $res = $this->postJson("/api/attestations/document-requests/{$id}/contact-demandeur", ['message' => 'Bonjour, merci de passer au CAP.'])->assertOk()->json('data');
        $this->assertFalse($res['whatsapp']['success']);
        $this->assertStringContainsString('déconnecté', $res['whatsapp']['reason']);
        $this->assertTrue($res['email']['success']);
    }

    public function test_send_now_delivers_or_requeues_on_failure(): void
    {
        config(['queue.default' => 'database']);
        \Illuminate\Support\Facades\Queue::fake();
        $this->fakeBridge(true, true);
        SendNotificationJob::sendNow('whatsapp', ['phone' => '0197000001', 'message' => 'x', 'context' => 't', 'module' => 'Core']);
        \Illuminate\Support\Facades\Queue::assertNothingPushed();

        $this->fakeBridge(false);
        SendNotificationJob::sendNow('whatsapp', ['phone' => '0197000001', 'message' => 'x', 'context' => 't', 'module' => 'Core']);
        \Illuminate\Support\Facades\Queue::assertPushed(SendNotificationJob::class, 1);

        // Mailable envoyé immédiatement → WhatsApp de suivi envoyé immédiatement.
        Mail::fake();
        $this->fakeBridge(true, true);
        SendNotificationJob::sendNow('email', ['to' => 'a@example.org', 'subject' => 'S', 'view' => 'core::emails.notification', 'vars' => ['title' => 'T', 'body' => 'B']]);
        \Illuminate\Support\Facades\Queue::assertPushed(SendNotificationJob::class, 1);
    }

    public function test_payment_validation_notifies_email_and_whatsapp(): void
    {
        Bus::fake([SendNotificationJob::class, \App\Modules\Demandes\Jobs\SendNotificationJob::class]);
        $pid = DB::table('payments')->insertGetId(['uuid' => Str::uuid(), 'student_id_number' => 'M1', 'reference' => 'P-1', 'amount' => 25000, 'account_number' => 'X', 'payment_date' => now()->toDateString(), 'email' => 'p@example.org', 'contact' => '0197556677', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        app(\App\Modules\Finance\Services\ValidationService::class)->rejectPayment($pid, ['motif' => 'Montant incorrect']);
        $this->assertEquals('p@example.org', $this->jobs('email')->first()->payload['to']);
        $wa = $this->jobs('whatsapp')->first();
        $this->assertEquals('0197556677', $wa->payload['phone']);
        $this->assertStringContainsString('Montant incorrect', $wa->payload['message']);
    }

    /** Étudiant inscrit deux années : admis en L1 (2024), inscrit en L2 (2025) sans décision. */
    private function twoYearStudent(string $cycleName = 'Licence Professionnelle', int $years = 3, string $lvl1 = 'L1', string $lvl2 = 'L2', ?string $dec2 = null): array
    {
        $cy  = DB::table('cycles')->where('name', $cycleName)->value('id')
            ?? DB::table('cycles')->insertGetId(['uuid' => Str::uuid(), 'name' => $cycleName, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('cycles')->where('id', $cy)->update(['years_count' => $years]);
        $dep = DB::table('departments')->insertGetId(['uuid' => Str::uuid(), 'name' => 'Génie Civil ' . $cy, 'cycle_id' => $cy, 'created_at' => now(), 'updated_at' => now()]);
        $mat = 'EL' . random_int(10000, 99999);
        $st  = DB::table('students')->insertGetId(['uuid' => Str::uuid(), 'student_id_number' => $mat, 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]);
        $out = ['mat' => $mat];
        foreach ([['2024-2025', $lvl1, 'pass', '2025-07-10'], ['2025-2026', $lvl2, $dec2, null]] as $i => [$yr, $lvl, $dec, $delib]) {
            $ay  = DB::table('academic_years')->where('academic_year', $yr)->value('id') ?? DB::table('academic_years')->insertGetId(['uuid' => Str::uuid(), 'academic_year' => $yr, 'year_start' => substr($yr, 0, 4) . '-10-01', 'year_end' => substr($yr, 5) . '-07-31', 'created_at' => now(), 'updated_at' => now()]);
            $pi  = DB::table('personal_information')->insertGetId(['uuid' => Str::uuid(), 'last_name' => 'DOSSOU', 'first_names' => 'Eli', 'email' => 'eli@example.org', 'contacts' => json_encode(['0197000222']), 'created_at' => now(), 'updated_at' => now()]);
            $ps  = DB::table('pending_students')->insertGetId(['uuid' => Str::uuid(), 'tracking_code' => 'CAP-EL' . random_int(1000, 9999), 'personal_information_id' => $pi, 'academic_year_id' => $ay, 'department_id' => $dep, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
            $sps = DB::table('student_pending_student')->insertGetId(['uuid' => Str::uuid(), 'student_id' => $st, 'pending_student_id' => $ps, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('academic_paths')->insert(['uuid' => Str::uuid(), 'student_pending_student_id' => $sps, 'academic_year_id' => $ay, 'study_level' => $lvl, 'year_decision' => $dec, 'deliberation_date' => $delib, 'created_at' => now(), 'updated_at' => now()]);
            $out['sps' . ($i + 1)] = $sps; $out['ay' . ($i + 1)] = $ay;
        }
        return $out;
    }

    private function statusTypes(string $mat): array
    {
        $docs = $this->getJson('/api/attestations/status?matricule=' . $mat)->assertOk()->json('data.documents') ?? $this->getJson('/api/attestations/status?matricule=' . $mat)->json('documents');
        return collect($docs)->pluck('status', 'type')->all();
    }

    public function test_passage_eligible_from_earlier_year_and_request_attached_to_that_year(): void
    {
        Bus::fake([SendNotificationJob::class, \App\Modules\Demandes\Jobs\SendNotificationJob::class]);
        $s = $this->twoYearStudent();
        $types = $this->statusTypes($s['mat']);
        $this->assertEquals('disponible', $types['attestation_passage'] ?? null, 'admis en L1 → passage disponible même inscrit en L2');
        $this->assertArrayNotHasKey('attestation_definitive', $types);
        $this->assertArrayHasKey('attestation_inscription', $types);

        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('public');
        $file = fn () => \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $ref = $this->post('/api/attestations/demandes', [
            'matricule' => $s['mat'], 'type' => 'attestation_passage', 'email' => 'eli@example.org', 'whatsapp' => '0197000222',
            'demande_manuscrite' => $file(), 'recu_paiement' => $file(), 'acte_naissance' => $file(), 'bulletin' => $file(), 'quittance' => $file(),
        ], ['Accept' => 'application/json'])->assertStatus(201)->json('reference');
        $row = DB::table('document_requests')->where('reference', $ref)->first();
        $this->assertEquals($s['sps1'], $row->student_pending_student_id, 'rattachée à l\'inscription de l\'année admise');
        $this->assertEquals($s['ay1'], $row->academic_year_id);
        $this->assertEquals('submitted', $this->statusTypes($s['mat'])['attestation_passage']);
    }

    public function test_status_shows_active_request_even_if_a_newer_one_was_picked_up(): void
    {
        // Statut et règle de dépôt doivent concorder : une demande active
        // (même plus ancienne) l'emporte sur une demande récente déjà retirée.
        $s = $this->twoYearStudent();
        $base = ['student_pending_student_id' => $s['sps1'], 'type' => 'attestation_passage', 'email' => 'e@example.org', 'created_at' => now(), 'updated_at' => now()];
        DB::table('document_requests')->insert($base + ['reference' => 'ATT-OLD001', 'status' => 'director_review', 'submitted_at' => now()->subDays(10)]);
        DB::table('document_requests')->insert($base + ['reference' => 'ATT-NEW001', 'status' => 'picked_up', 'submitted_at' => now()->subDay()]);
        $this->assertEquals('director_review', $this->statusTypes($s['mat'])['attestation_passage']);
    }

    public function test_definitive_eligible_and_prepa_excluded(): void
    {
        $s = $this->twoYearStudent('Licence Professionnelle', 3, '3', 'L3');
        $this->assertEquals('disponible', $this->statusTypes($s['mat'])['attestation_definitive'] ?? null);

        $p = $this->twoYearStudent('Cycle Ingénieur - Prépa', 2, '1', '2');
        $types = $this->statusTypes($p['mat']);
        $this->assertArrayNotHasKey('attestation_passage', $types);
        $this->assertArrayNotHasKey('attestation_definitive', $types);
    }

    public function test_check_existing_without_token_hides_personal_data(): void
    {
        $r = $this->regularStudent();
        DB::table('pending_students')->where('id', $r['ps'])->update(['status' => 'pending']);
        $res = $this->getJson('/api/inscription/dossiers/check-existing?email=luc@example.org');
        $res->assertOk();
        $d = $res->json('data');
        if (!empty($d['exists'])) {
            $this->assertTrue($d['requires_verification']);
            $this->assertArrayNotHasKey('tracking_code', $d['dossier']);
            $this->assertArrayNotHasKey('prefill_data', $d['dossier']);
        }
        // avec jeton OTP : données complètes
        Cache::flush(); Mail::fake();
        app(OtpService::class)->sendOtp('luc@example.org', 'inscription');
        $code = Cache::get('otp_' . md5('luc@example.org_inscription'))['code'];
        $tok = app(OtpService::class)->verifyOtp('luc@example.org', $code, 'inscription')['verification_token'];
        $full = $this->getJson('/api/inscription/dossiers/check-existing?email=luc@example.org&verification_token=' . $tok)->json('data');
        if (!empty($full['exists'])) {
            $this->assertFalse($full['requires_verification']);
            $this->assertArrayHasKey('tracking_code', $full['dossier']);
        }
        $this->assertTrue(!empty($d['exists']) || empty($full['exists']));
    }

    public function test_reminders_command_runs(): void
    {
        Bus::fake([SendNotificationJob::class, \App\Modules\Demandes\Jobs\SendNotificationJob::class]);
        $this->artisan('demandes:send-reminders')->assertExitCode(0);
    }

    public function test_quittance_refused_for_unknown_request(): void
    {
        $this->postJson('/api/attestations/quittance/generate', [
            'quittanceNumber' => 'Q1', 'montant' => 2000, 'motif' => 'x', 'nomEtudiant' => 'X', 'matricule' => 'M1',
            'email' => 'victime@example.org', 'referenceDemande' => 'ATT-NOPE00', 'paidAt' => now()->toIso8601String(),
        ])->assertStatus(404);
    }

    public function test_online_payment_closed_requires_quittance(): void
    {
        $this->postJson('/api/attestations/demandes', [
            'matricule' => 'M1', 'type' => 'attestation_inscription', 'email' => 'a@example.org',
            'whatsapp' => '+2290158370390', 'payment_method' => 'tresor_online', 'payment_reference' => 'QUI-TRESO-1',
        ])->assertStatus(422)->assertJsonPath('message', "Le paiement en ligne n'est pas disponible. Joignez la quittance du Trésor Public.");
        $this->postJson('/api/attestations/bulletins', [
            'legacy_matricule' => 'M1', 'bulletin_year' => '2020-2021', 'type' => 'bulletin_annuel', 'email' => 'a@example.org',
            'whatsapp' => '+2290158370390', 'payment_method' => 'tresor_online', 'payment_reference' => 'QUI-TRESO-1',
        ])->assertStatus(422);
    }

    public function test_frontend_url_never_localhost_when_app_url_is_public(): void
    {
        $this->assertDoesNotMatchRegularExpression('#localhost|127\.0\.0\.1#', $this->resolveFrontendUrl('http://localhost:5173', 'https://test.cap-epac.bj'));
        $this->assertSame('https://cap-epac.bj', $this->resolveFrontendUrl('', 'https://cap-epac.bj/'));
        $this->assertSame('https://test.cap-epac.bj', $this->resolveFrontendUrl('https://test.cap-epac.bj/', 'http://localhost'));
    }

    private function resolveFrontendUrl(string $front, string $app): string
    {
        $old = [getenv('FRONTEND_URL'), getenv('APP_URL')];
        $set = function ($k, $v) { putenv("{$k}={$v}"); $_ENV[$k] = $v; $_SERVER[$k] = $v; };
        $set('FRONTEND_URL', $front); $set('APP_URL', $app);
        try {
            return (require base_path('config/app.php'))['frontend_url'];
        } finally {
            $set('FRONTEND_URL', (string) $old[0]); $set('APP_URL', (string) $old[1]);
        }
    }

    public function test_candidature_existing_dossier_hidden_without_otp(): void
    {
        $this->postJson('/api/inscription/dossiers/fetch-for-update', ['email' => 'x@example.org'])->assertStatus(403);
        $this->postJson('/api/inscription/dossiers/update-existing', ['email' => 'x@example.org', 'tracking_code' => 'CAP-1'])->assertStatus(403);
        $this->postJson('/api/inscription/dossiers/transfer-wave', ['email' => 'x@example.org', 'tracking_code' => 'CAP-1'])->assertStatus(403);
    }

    public function test_upload_rules_reject_big_and_exe(): void
    {
        $r = $this->post('/api/inscription/dossiers/licence', [
            'cv' => \Illuminate\Http\UploadedFile::fake()->create('cv.exe', 10, 'application/x-msdownload'),
            'demande_da' => \Illuminate\Http\UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
        ], ['Accept' => 'application/json']);
        $r->assertStatus(422);
        $errors = $r->json('errors');
        $this->assertArrayHasKey('cv', $errors);
        $this->assertStringContainsString('Format', $errors['cv'][0]);
        $this->assertStringContainsString('5 Mo', $errors['demande_da'][0]);
    }
}
}
