<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use App\Modules\Bibliotheque\Models\LibraryWork;
use App\Modules\Core\Services\Notifier;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
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

/** Bibliothèque des travaux : proposition, publication, pages publiques, contact. */
class BibliothequeTest extends TestCase
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
                $this->test->record('email', $to, $subject . ' | ' . ($vars['highlight'] ?? ''));
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

    /** Stagiaire en fin de stage, connecté. */
    private function intern(): array
    {
        $req = StageRequest::create([
            'reference' => 'STG-' . strtoupper(Str::random(6)), 'status' => 'accepted', 'civilite' => 'Mlle',
            'nom' => 'HOUNSOU', 'prenoms' => 'Grâce', 'date_naissance' => '2002-01-01', 'lieu_naissance' => 'Cotonou',
            'nationalite' => 'Béninoise', 'etablissement_origine' => 'EPAC', 'filiere' => 'Génie informatique', 'cycle' => 'licence', 'annee_etude' => 3,
            'duree_souhaitee' => '2_mois', 'email' => uniqid() . '@example.org', 'whatsapp' => '+2290196' . random_int(100000, 999999),
            'files' => [], 'consent_at' => now(), 'service' => 'Cellule informatique', 'lieu' => 'CAP-EPAC',
            'date_debut' => '2026-11-02', 'date_fin' => '2026-12-31', 'date_debut_reelle' => '2026-11-02', 'accepted_at' => now(), 'activated_at' => now(),
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

    private function proposal(): array
    {
        return [
            'period_key' => 'FINAL', 'fichier' => UploadedFile::fake()->create('rapport.pdf', 300, 'application/pdf'),
            'fin_formation' => '1', 'theme' => 'Supervision du réseau du CAP avec Zabbix',
            'resume' => 'Mise en place d\'une supervision centralisée du réseau et des serveurs du CAP, avec alertes automatiques.',
            'mots_cles' => 'réseau, supervision ; Zabbix', 'maitre_nom' => 'Dr Paul ADJOVI', 'maitre_fonction' => 'Enseignant, EPAC',
            'maitre_email' => 'p.adjovi@example.org', 'maitre_telephone' => '+229 01 97 11 22 33', 'consentement_publication' => '1',
        ];
    }

    public function test_intern_proposes_lead_publishes_and_public_pages_are_indexable(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->user('responsable-division', 'formation_continue');
        $this->at('2026-12-18 10:00');

        // Sans consentement : refusé ; avec : proposé à la bibliothèque
        $this->post('/api/stagiaire/rapports', ['consentement_publication' => '0'] + $this->proposal(), $h)
            ->assertStatus(422)->assertJsonValidationErrors('consentement_publication');
        $reportId = $this->post('/api/stagiaire/rapports', $this->proposal(), $h)->assertStatus(201)->json('data.id');
        $work = LibraryWork::with('people')->firstWhere(['source_type' => 'stage_report', 'source_id' => $reportId]);
        $this->assertSame(['proposed', 'Licence 3 Génie informatique', ['réseau', 'supervision', 'Zabbix']], [$work->status, $work->formation(), $work->keywords]);
        $this->assertSame(['auteur', 'maitre'], $work->people->pluck('role')->all());
        $this->assertSame('Dr Paul ADJOVI', $this->getJson('/api/stagiaire/rapports', $h)->json('data.bibliotheque.maitre_nom'));

        // Le responsable publie : pas avant la validation du rapport
        $this->actingAs($lead, 'sanctum');
        $this->getJson('/api/bibliotheque/admin/travaux')->assertOk()->assertJsonPath('data.counts.proposed', 1);
        $publish = ['visibility' => 'public', 'downloadable' => true, 'license' => 'tous_droits'];
        $this->getJson("/api/bibliotheque/admin/travaux/{$work->id}")->assertOk()->assertJsonPath('data.has_file', false);
        $this->postJson("/api/bibliotheque/admin/travaux/{$work->id}/publier", $publish)->assertStatus(422)->assertJsonPath('error_code', 'LIBRARY_NOT_VALIDATED');
        $this->postJson("/api/stages/admin/rapports/{$reportId}/decision", ['decision' => 'approved'])->assertOk();
        // Rapport validé : son PDF est déjà le document du travail, rien à ajouter
        $this->getJson("/api/bibliotheque/admin/travaux/{$work->id}")->assertOk()
            ->assertJsonPath('data.has_file', true)->assertJsonPath('data.file_origin', 'rapport');
        $this->getJson("/api/bibliotheque/admin/travaux/{$work->id}/fichier")->assertOk()->assertJsonPath('mime', 'application/pdf');
        $this->postJson("/api/bibliotheque/admin/travaux/{$work->id}/publier", $publish)->assertOk()->assertJsonPath('data.status', 'published');
        $work->refresh();
        $this->assertNull($work->file_path); // pas de copie : la version officielle du rapport est servie
        $this->assertStringStartsWith('supervision-du-reseau-du-cap-avec-zabbix-', $work->slug);

        // Repris par le responsable : le stagiaire ne modifie plus la fiche
        $this->assertFalse($this->getJson('/api/stagiaire/rapports', $h)->json('data.bibliotheque.editable'));

        // Site : liste et fiche (sans aucun contact) ; page avec balises Google Scholar écrites par le serveur
        $this->getJson('/api/travaux?q=zabbix')->assertOk()->assertJsonPath('data.items.0.title', 'Supervision du réseau du CAP avec Zabbix');
        $fiche = $this->getJson("/api/travaux/{$work->id}")->assertOk()
            ->assertJsonPath('data.people.1.name', 'Dr Paul ADJOVI')->assertJsonPath('data.people.1.reachable', true);
        foreach (['p.adjovi@example.org', '97 11 22 33', $req->email] as $private) {
            $this->assertStringNotContainsString($private, $fiche->getContent());
        }
        $this->get("/travaux/{$work->id}")->assertOk()
            ->assertSee('<meta name="citation_title" content="Supervision du réseau du CAP avec Zabbix">', false)
            ->assertSee('citation_pdf_url', false)->assertSee('<div id="root"></div>', false)
            ->assertDontSee('p.adjovi@example.org');
        $this->get('/sitemap.xml')->assertOk()->assertSee("/travaux/{$work->id}", false);
        // Adresse courte ; l'ancienne adresse avec le titre redirige vers elle
        $this->assertSame(url("/travaux/{$work->id}"), $fiche->json('data.url'));
        $this->get("/travaux/{$work->slug}")->assertStatus(301)->assertRedirect(url("/travaux/{$work->id}"));
        $this->get("/travaux/{$work->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertSame([1, 1], [$work->fresh()->views, $work->fresh()->downloads]); // vue comptée par la fiche du site
    }

    public function test_link_only_work_is_hidden_and_contact_is_relayed(): void
    {
        [$req, $h] = $this->intern();
        $lead = $this->user('responsable-division', 'formation_continue');
        $this->at('2026-12-18 10:00');
        $reportId = $this->post('/api/stagiaire/rapports', $this->proposal(), $h)->assertStatus(201)->json('data.id');
        $this->actingAs($lead, 'sanctum')->postJson("/api/stages/admin/rapports/{$reportId}/decision", ['decision' => 'approved'])->assertOk();
        $work = LibraryWork::firstWhere(['source_type' => 'stage_report', 'source_id' => $reportId]);
        $this->postJson("/api/bibliotheque/admin/travaux/{$work->id}/publier", ['visibility' => 'link', 'downloadable' => false, 'license' => 'cc_by_nc'])->assertOk();
        $work->refresh();

        // Par lien : introuvable sans la clé, non référencé, PDF fermé
        $this->get("/travaux/{$work->id}")->assertNotFound();
        $this->getJson("/api/travaux/{$work->id}")->assertNotFound();
        $this->getJson('/api/travaux')->assertOk()->assertJsonPath('data.total', 0);
        $this->get('/sitemap.xml')->assertDontSee("/travaux/{$work->id}<", false);
        $this->get("/travaux/{$work->id}?cle={$work->share_token}")->assertOk()->assertSee('noindex, nofollow', false)->assertDontSee('citation_title', false);
        $this->getJson("/api/travaux/{$work->id}?cle={$work->share_token}")->assertOk()->assertJsonPath('data.pdf_url', null);
        $this->get("/travaux/{$work->id}/pdf?cle={$work->share_token}")->assertNotFound();

        // Contacter le maître de mémoire : message relayé par email, robots écartés
        $maitre = $work->people()->where('role', 'maitre')->first();
        $url = "/api/travaux/{$work->id}/contact?cle={$work->share_token}";
        $form = ['person_id' => $maitre->id, 'nom' => 'Jean AGOSSA', 'email' => 'jean@example.org', 'message' => 'Bonjour, je travaille sur un sujet proche et souhaiterais échanger.'];
        $this->postJson($url, $form + ['site' => 'spam'])->assertStatus(422)->assertJsonValidationErrors('site');
        $this->postJson($url, $form)->assertOk();
        $this->assertTrue(collect($this->sent)->contains(fn ($m) => $m['to'] === 'p.adjovi@example.org' && str_contains($m['text'], 'souhaiterais échanger')));
        $this->assertSame(1, DB::table('library_contact_messages')->where('work_id', $work->id)->count());

        // Lien renouvelé : l'ancien ne marche plus
        $old = $work->share_token;
        $this->postJson("/api/bibliotheque/admin/travaux/{$work->id}/nouveau-lien")->assertOk();
        $this->get("/travaux/{$work->id}?cle={$old}")->assertNotFound();
    }

    public function test_only_lead_manages_and_manual_work_needs_summary(): void
    {
        $sup = $this->user('superviseur');
        $this->actingAs($sup, 'sanctum')->getJson('/api/bibliotheque/admin/travaux')->assertStatus(403);

        $lead = $this->user('responsable-division', 'formation_continue');
        $this->actingAs($lead, 'sanctum');
        $id = $this->post('/api/bibliotheque/admin/travaux', [
            'kind' => 'memoire', 'title' => 'Étude d\'un micro-réseau solaire', 'year' => 2025, 'cycle' => 'ingenieur', 'annee_etude' => 5, 'filiere' => 'Génie électrique',
            'people' => [['role' => 'auteur', 'name' => 'Koffi DOSSOU', 'email' => 'koffi@example.org']],
            'fichier' => UploadedFile::fake()->create('memoire.pdf', 500, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(201)->json('data.id');
        $this->assertSame('5e année du cycle ingénieur Génie électrique', $this->getJson("/api/bibliotheque/admin/travaux/{$id}")->json('data.formation'));

        $publish = ['visibility' => 'public', 'downloadable' => true, 'license' => 'tous_droits'];
        $this->postJson("/api/bibliotheque/admin/travaux/{$id}/publier", $publish)->assertStatus(422)->assertJsonPath('error_code', 'LIBRARY_NO_SUMMARY');
        $this->putJson("/api/bibliotheque/admin/travaux/{$id}", [
            'kind' => 'memoire', 'title' => 'Étude d\'un micro-réseau solaire', 'summary' => 'Dimensionnement et simulation d\'un micro-réseau solaire pour un village.',
            'people' => [['role' => 'auteur', 'name' => 'Koffi DOSSOU', 'email' => 'koffi@example.org']],
        ])->assertOk();
        $this->postJson("/api/bibliotheque/admin/travaux/{$id}/publier", $publish)->assertOk();
        $this->deleteJson("/api/bibliotheque/admin/travaux/{$id}")->assertStatus(409);
        $this->postJson("/api/bibliotheque/admin/travaux/{$id}/retirer")->assertOk()->assertJsonPath('data.status', 'unpublished');
        $this->get('/travaux/' . LibraryWork::find($id)->slug)->assertNotFound();
        $this->deleteJson("/api/bibliotheque/admin/travaux/{$id}")->assertOk();
    }
}
