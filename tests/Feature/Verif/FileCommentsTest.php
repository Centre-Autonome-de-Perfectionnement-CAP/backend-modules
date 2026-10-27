<?php

namespace Tests\Feature\Verif;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Commentaires des acteurs sur les pièces d'un dossier + édition du nom
 * d'une pièce de la secrétaire.
 */
class FileCommentsTest extends TestCase
{
    use DatabaseTransactions;

    private int $demandeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->demandeId = DB::table('document_requests')->insertGetId([
            'reference' => 'ATT-FC' . random_int(1000, 9999), 'type' => 'attestation_inscription', 'status' => 'submitted',
            'email' => 'luc@example.org', 'demandeur_whatsapp' => '+2290197334455',
            'files' => json_encode(['acte_naissance' => 'demandes/x/acte.pdf']),
            'secretary_files' => json_encode([['id' => 'sec_abc', 'path' => 'demandes/x/sec.pdf', 'original_name' => 'Relevé', 'comment' => null, 'uploaded_at' => now()->toIso8601String()]]),
            'created_at' => now(), 'updated_at' => now(), 'submitted_at' => now(),
        ]);
    }

    private function user(string $slug): User
    {
        $roleId = DB::table('roles')->where('slug', $slug)->value('id')
            ?? DB::table('roles')->insertGetId(['uuid' => Str::uuid(), 'name' => $slug, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('users')->insertGetId([
            'uuid' => Str::uuid(), 'last_name' => strtoupper($slug), 'first_name' => 'Test',
            'email' => $slug . uniqid() . '@example.org', 'password' => bcrypt('x'), 'phone' => '0197000001',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_user')->insert(['role_id' => $roleId, 'user_id' => $id]);
        return User::find($id);
    }

    private function url(string $suffix = ''): string
    {
        return "/api/attestations/document-requests/{$this->demandeId}/file-comments{$suffix}";
    }

    public function test_actor_comments_and_only_author_can_edit_or_delete(): void
    {
        $compta = $this->user('comptable');
        $dir    = $this->user('directeur');

        $this->actingAs($compta, 'sanctum');
        $c = $this->postJson($this->url(), ['file_source' => 'initial', 'file_key' => 'acte_naissance', 'body' => '<b>Acte</b> lisible'])
            ->assertStatus(201)->json('data');
        $this->assertSame('Acte lisible', $c['body']);
        $this->assertTrue($c['can_edit']);
        $this->assertSame('Comptable', $c['author_role_label']);

        // un autre acteur voit le commentaire mais ne peut ni le modifier ni le supprimer
        $this->actingAs($dir, 'sanctum');
        $list = $this->getJson($this->url())->assertOk()->json('data');
        $this->assertFalse($list['history_visible']);
        $this->assertFalse($list['comments'][0]['can_edit']);
        $this->assertArrayNotHasKey('revisions', $list['comments'][0]);
        $this->patchJson($this->url('/' . $c['id']), ['body' => 'pirate'])->assertStatus(403);
        $this->deleteJson($this->url('/' . $c['id']))->assertStatus(403);

        // l'auteur modifie puis supprime
        $this->actingAs($compta, 'sanctum');
        $this->patchJson($this->url('/' . $c['id']), ['body' => 'Acte illisible en bas'])->assertOk()->assertJsonPath('data.body', 'Acte illisible en bas');
        $this->deleteJson($this->url('/' . $c['id']))->assertOk();
        $this->assertCount(0, $this->getJson($this->url())->json('data.comments'));

        // la secrétaire voit l'historique complet, y compris le commentaire supprimé
        $this->actingAs($this->user('secretaire'), 'sanctum');
        $hist = $this->getJson($this->url())->assertOk()->json('data');
        $this->assertTrue($hist['history_visible']);
        $this->assertNotNull($hist['comments'][0]['deleted_at']);
        $this->assertNull($hist['comments'][0]['body']);
        $this->assertSame(['edited', 'deleted'], array_column($hist['comments'][0]['revisions'], 'action'));
        $this->assertSame('Acte lisible', $hist['comments'][0]['revisions'][0]['previous_body']);
        $this->assertSame('Acte illisible en bas', $hist['comments'][0]['revisions'][1]['previous_body']);
    }

    public function test_comment_rejected_for_unknown_file_empty_body_or_outsider(): void
    {
        $this->actingAs($this->user('comptable'), 'sanctum');
        $this->postJson($this->url(), ['file_source' => 'initial', 'file_key' => 'inconnu', 'body' => 'x'])->assertStatus(404);
        $this->postJson($this->url(), ['file_source' => 'evil', 'file_key' => 'acte_naissance', 'body' => 'x'])->assertStatus(422);
        $this->postJson($this->url(), ['file_source' => 'initial', 'file_key' => '../etc', 'body' => 'x'])->assertStatus(422);
        $this->postJson($this->url(), ['file_source' => 'initial', 'file_key' => 'acte_naissance', 'body' => '<p> </p>'])->assertStatus(422);
        $this->postJson($this->url(), ['file_source' => 'secretary', 'file_key' => 'sec_abc', 'body' => 'Relevé conforme'])->assertStatus(201);

        // un compte hors circuit (ex. professeur) n'a aucun accès
        $this->actingAs($this->user('professeur'), 'sanctum');
        $this->getJson($this->url())->assertStatus(403);
        $this->postJson($this->url(), ['file_source' => 'initial', 'file_key' => 'acte_naissance', 'body' => 'x'])->assertStatus(403);
    }

    public function test_secretary_can_rename_and_comment_her_file(): void
    {
        $this->actingAs($this->user('secretaire'), 'sanctum');
        $res = $this->patchJson("/api/attestations/document-requests/{$this->demandeId}/secretary-files/sec_abc", ['name' => 'Relevé <i>S1</i>', 'comment' => 'Copie certifiée'])
            ->assertOk()->json('data.secretary_files');
        $this->assertSame('Relevé S1', $res[0]['original_name']);
        $this->assertSame('Copie certifiée', $res[0]['comment']);

        // sans nom : l'ancien nom est conservé (compatibilité de l'ancien appel)
        $res = $this->patchJson("/api/attestations/document-requests/{$this->demandeId}/secretary-files/sec_abc", ['comment' => 'Autre'])
            ->assertOk()->json('data.secretary_files');
        $this->assertSame('Relevé S1', $res[0]['original_name']);

        $this->actingAs($this->user('comptable'), 'sanctum');
        $this->patchJson("/api/attestations/document-requests/{$this->demandeId}/secretary-files/sec_abc", ['name' => 'x'])->assertStatus(403);
    }

    public function test_preview_as_json_returns_base64_content(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('demandes/x/acte.pdf', "%PDF-1.4 test");
        $this->actingAs($this->user('comptable'), 'sanctum');
        $url = "/api/attestations/document-requests/{$this->demandeId}/files/initial/acte_naissance";

        $json = $this->getJson($url . '?format=json')->assertOk()->json('data');
        $this->assertSame("%PDF-1.4 test", base64_decode($json['content']));
        $this->assertNotEmpty($json['mime']);

        // Sans le paramètre : comportement inchangé (fichier brut)
        $this->get($url)->assertOk()->assertHeader('content-disposition');

        // Accès toujours protégé
        $this->actingAs($this->user('professeur'), 'sanctum');
        $this->getJson($url . '?format=json')->assertStatus(403);
    }
}
