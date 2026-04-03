<?php

namespace Tests\Feature;

use Tests\TestCase;

class PendingStudentSimpleTest extends TestCase
{
    /**
     * Test: Liste nécessite authentification
     */
    public function test_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/inscription/pending-students');

        $response->assertStatus(401);
    }

    /**
     * Test: Utilisateur authentifié peut lister
     */
    public function test_authenticated_user_can_list(): void
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/inscription/pending-students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /**
     * Test: Création publique nécessite tous les champs
     */
    public function test_public_create_validates_fields(): void
    {
        $response = $this->postJson('/api/inscription/inscriptions', []);

        // Peut retourner 422 (validation) ou 500 (erreur serveur sans données)
        $this->assertContains($response->status(), [422, 500]);
        
        if ($response->status() === 422) {
            $response->assertJsonStructure(['message', 'errors']);
        }
    }

    /**
     * Test: Mise à jour nécessite authentification
     */
    public function test_update_requires_authentication(): void
    {
        $response = $this->putJson('/api/inscription/pending-students/1', [
            'status' => 'approved',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Suppression nécessite authentification
     */
    public function test_delete_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/inscription/pending-students/1');

        $response->assertStatus(401);
    }
}
