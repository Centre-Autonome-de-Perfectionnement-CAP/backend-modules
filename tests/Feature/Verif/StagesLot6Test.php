<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Core\Jobs\SendNotificationJob;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\AttestationService;
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

/** Module Stages - Lot 6 : tableau de bord, suivi, exports. */
class StagesLot6Test extends TestCase
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

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('rapport.pdf', 300, 'application/pdf');
    }

    private function lead(): User
    {
        return $this->user('responsable-division', 'formation_continue');
    }

    /** Présent tous les jours ouvrés du $from au $to, sauf les jours listés. */
    private function attend(StageRequest $req, string $from, string $to, array $absent = []): void
    {
        for ($d = CarbonImmutable::parse($from); $d->lessThanOrEqualTo(CarbonImmutable::parse($to)); $d = $d->addDay()) {
            if ($d->isWeekend() || in_array($d->toDateString(), $absent, true)) {
                continue;
            }
            foreach (['arrivee' => '08:00', 'sortie' => '18:00'] as $k => $hm) {
                DB::table('stage_attendances')->insert([
                    'stage_request_id' => $req->id, 'day' => $d->toDateString(), 'kind' => $k,
                    'at' => CarbonImmutable::parse($d->toDateString() . " {$hm}", BusinessHours::TZ)->utc(), 'created_at' => now(),
                ]);
            }
        }
    }

    private function finalReport(StageRequest $req, string $status = 'approved'): void
    {
        DB::table('stage_reports')->insert([
            'stage_request_id' => $req->id, 'kind' => 'final', 'period_key' => 'FINAL', 'current_version' => 1,
            'status' => $status, 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ── Tests ───────────────────────────────────────────────────────────

    private function xlsx(array $json): array
    {
        $this->assertStringEndsWith('.xlsx', $json['name']);
        $path = tempnam(sys_get_temp_dir(), 'x') . '.xlsx';
        file_put_contents($path, base64_decode($json['content']));
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);
        @unlink($path);

        return $sheet;
    }

    public function test_dashboard_scopes_and_alerts(): void
    {
        $this->at('2026-11-20 10:00');
        [$good] = $this->intern('2026-11-02', '2026-11-30');
        [$bad]  = $this->intern('2026-11-02', '2026-12-31');
        $this->attend($good, '2026-11-02', '2026-11-19');
        $this->attend($bad, '2026-11-02', '2026-11-19', ['2026-11-03', '2026-11-04', '2026-11-05', '2026-11-06', '2026-11-09']);
        $lead = $this->lead();
        $sup = $this->user('superviseur');
        DB::table('stage_supervisions')->insert(['stage_request_id' => $bad->id, 'user_id' => $sup->id, 'created_at' => now()]);

        $d = $this->actingAs($lead, 'sanctum')->getJson('/api/stages/admin/tableau-de-bord')->assertOk()->json('data');
        $this->assertTrue($d['lead']);
        $this->assertSame(2, $d['stagiaires']['en_cours']);
        $this->assertSame(1, $d['stagiaires']['fin_proche']);
        $this->assertSame(1, $d['assiduite']['sous_seuil']);
        // Une ligne par stagiaire, avec ses codes (le plus urgent d'abord)
        $codes = collect($d['alertes'])->mapWithKeys(fn ($a) => [$a['stage_id'] => array_column($a['codes'], 'code')]);
        $this->assertSame([$bad->id => ['assiduite', 'rapports'], $good->id => ['fin_proche', 'rapports', 'sans_superviseur']], $codes->all());
        $this->assertCount(12, $d['mensuel']);

        // Superviseur : seulement ses stagiaires, pas de chiffres réservés
        $s = $this->actingAs($sup, 'sanctum')->getJson('/api/stages/admin/tableau-de-bord')->assertOk()->json('data');
        $this->assertFalse($s['lead']);
        $this->assertSame(1, $s['stagiaires']['en_cours']);
        $this->assertNull($s['a_faire']['attestations']);
        $this->assertSame([], $s['mensuel']);
        $this->assertTrue(collect($s['alertes'])->every(fn ($a) => $a['stage_id'] === $bad->id));

        $res = $this->getJson('/api/stages/admin/suivi?scope=mine')->assertOk();
        $rows = $res->json('data');
        $this->assertSame([$bad->id], array_column($rows, 'id'));
        $this->assertSame(['active' => 2, 'unsupervised' => 1, 'mine' => 1], array_intersect_key($res->json('counts'), array_flip(['active', 'unsupervised', 'mine'])));
        $this->assertSame('assiduite', $rows[0]['codes'][0]['code']);
        $this->assertSame(64.3, $rows[0]['taux']); // 9 jours sur 14

        // Secrétariat : pas d'accès
        $this->actingAs($this->user('secretaire'), 'sanctum')->getJson('/api/stages/admin/tableau-de-bord')->assertStatus(403);
    }

    public function test_exports_are_xlsx_without_formulas_and_scoped(): void
    {
        $this->at('2026-11-20 10:00');
        [$req] = $this->intern('2026-11-02', '2026-11-30');
        $req->forceFill(['prenoms' => '=HYPERLINK("http://x","clic")'])->save();
        $this->attend($req, '2026-11-02', '2026-11-19', ['2026-11-10']);
        $lead = $this->lead();
        $sup = $this->user('superviseur');
        $sec = $this->user('secretaire');

        $this->actingAs($lead, 'sanctum');
        $sheet = $this->xlsx($this->getJson('/api/stages/admin/exports/stagiaires?scope=current')->assertOk()->json());
        $this->assertSame('Référence', $sheet[3][0]);
        $line = collect($sheet)->first(fn ($r) => $r[0] === $req->reference);
        $this->assertNotNull($line);
        $this->assertStringContainsString('=HYPERLINK', $line[1], 'texte conservé tel quel, pas une formule');
        $this->assertSame($req->whatsapp, $line[24], 'le « + » du numéro est conservé');
        $this->assertSame(0, $line[14], 'un zéro reste un zéro (retards)');
        $this->assertSame(0, $line[15], 'sorties anticipées');

        $pres = $this->xlsx($this->getJson("/api/stages/admin/exports/stagiaires/{$req->id}/presences")->assertOk()->json());
        $this->assertSame('02/11/2026', $pres[4][0]);
        $this->assertTrue(collect($pres)->contains(fn ($r) => $r[0] === '10/11/2026' && $r[1] === 'Absent'));

        $dem = $this->xlsx($this->getJson('/api/stages/admin/exports/demandes?status=all')->assertOk()->json());
        $this->assertTrue(collect($dem)->contains(fn ($r) => $r[0] === $req->reference));

        // Superviseur : pas l'export des dossiers de demande ; secrétariat : registre seulement
        $this->actingAs($sup, 'sanctum')->getJson('/api/stages/admin/exports/demandes')->assertStatus(403);
        $this->actingAs($sup, 'sanctum')->getJson('/api/stages/admin/exports/stagiaires?scope=mine')->assertOk();
        $this->actingAs($sec, 'sanctum')->getJson('/api/stages/admin/exports/stagiaires')->assertStatus(403);
        $reg = $this->xlsx($this->actingAs($sec, 'sanctum')->getJson('/api/stages/admin/exports/attestations?status=a_valider')->assertOk()->json());
        $this->assertCount(3, array_filter($reg, fn ($r) => array_filter($r, fn ($c) => $c !== null && $c !== '')), 'aucune ligne « à valider » pour le secrétariat');
    }

    public function test_note_pdf_staff_and_own_intern_only(): void
    {
        $this->at('2026-11-20 10:00');
        [$req, $h] = $this->intern('2026-11-02', '2026-12-31');
        [, $other] = $this->intern('2026-11-02', '2026-12-31');
        $snap = ['reference' => $req->reference, 'civilite' => 'M.', 'nom' => 'Aho', 'prenoms' => 'Luc', 'etablissement' => 'UAC', 'formation' => 'L3',
            'service' => 'Service Informatique', 'lieu' => 'CAP-EPAC', 'date_debut' => '2026-11-02', 'date_fin' => '2026-12-31',
            'horaires' => ['arrivee' => '08:00', 'pause_debut' => '13:00', 'pause_fin' => '15:00', 'sortie' => '18:00']];
        $n1 = DB::table('stage_notes')->insertGetId(['stage_request_id' => $req->id, 'number' => 'NS-T-' . $req->id, 'kind' => 'initiale', 'version' => 1,
            'snapshot' => json_encode($snap), 'issued_at' => now()]);
        $n2 = DB::table('stage_notes')->insertGetId(['stage_request_id' => $req->id, 'number' => 'NS-T-' . $req->id . '-R1', 'kind' => 'rectificative', 'version' => 2,
            'snapshot' => json_encode(['date_fin' => '2027-01-29'] + $snap), 'motif' => 'Prolongation', 'issued_at' => now()]);

        $this->actingAs($this->lead(), 'sanctum');
        foreach ([$n1, $n2] as $n) {
            $pdf = base64_decode($this->getJson("/api/stages/admin/demandes/{$req->id}/notes/{$n}/pdf")->assertOk()->json('content'));
            $this->assertStringStartsWith('%PDF-', $pdf);
        }
        $this->getJson("/api/stages/admin/demandes/" . ($req->id + 99999) . "/notes/{$n1}/pdf")->assertStatus(404);

        $this->getJson("/api/stagiaire/notes/{$n1}/pdf", $h)->assertOk()->assertJsonStructure(['name', 'content']);
        $this->getJson("/api/stagiaire/notes/{$n1}/pdf", $other)->assertStatus(404);
    }
}
