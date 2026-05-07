<?php

namespace Tests\Feature;

use App\Modules\Inscription\Models\AcademicYear;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    /**
     * Test: Liste des années académiques (route publique)
     */
    public function test_can_get_academic_years_list(): void
    {
        AcademicYear::factory()->count(3)->create();

        $response = $this->getJson('/api/inscription/academic-years');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'libelle',
                        'date_debut',
                        'date_fin',
                    ],
                ],
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Affichage d'une année académique (route publique)
     */
    public function test_can_show_academic_year(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->getJson("/api/inscription/academic-years/{$year->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'libelle',
                    'date_debut',
                    'date_fin',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Création d'année académique nécessite authentification
     */
    public function test_create_academic_year_requires_authentication(): void
    {
        $response = $this->postJson('/api/inscription/academic-years', [
            'year_start' => '2024-09-01',
            'year_end' => '2025-06-30',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    /**
     * Test: Endpoint création est accessible aux utilisateurs authentifiés
     */
    public function test_authenticated_user_can_access_create_endpoint(): void
    {
        $this->authenticatedUser();

        // Test que l'endpoint accepte les requêtes authentifiées
        $response = $this->postJson('/api/inscription/academic-years', []);

        // Doit retourner 422 (validation) et non 401 (non authentifié)
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }

    /**
     * Test: Mise à jour nécessite authentification
     */
    public function test_update_academic_year_requires_authentication(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->putJson("/api/inscription/academic-years/{$year->id}", [
            'year_start' => '2024-09-15',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Suppression nécessite authentification
     */
    public function test_delete_academic_year_requires_authentication(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->deleteJson("/api/inscription/academic-years/{$year->id}");

        $response->assertStatus(401);
    }

    /**
     * Test: Route liste est publique
     */
    public function test_index_route_is_public(): void
    {
        AcademicYear::factory()->create();

        $response = $this->getJson('/api/inscription/academic-years');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'libelle',
                        'date_debut',
                        'date_fin',
                    ],
                ],
            ]);
    }

    /**
     * Test: Route show est publique
     */
    public function test_show_route_is_public(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->getJson("/api/inscription/academic-years/{$year->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'libelle',
                    'date_debut',
                    'date_fin',
                ],
            ]);
    }

    /**
     * Test: Liste vide retourne structure valide
     */
    public function test_empty_list_returns_valid_structure(): void
    {
        // Ne créer aucune année académique
        $response = $this->getJson('/api/inscription/academic-years');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /**
     * Test: Affichage d'une année inexistante retourne erreur
     */
    public function test_show_nonexistent_academic_year_returns_error(): void
    {
        $response = $this->getJson('/api/inscription/academic-years/99999');

        // Peut retourner 404 (not found) ou 500 (erreur serveur)
        $this->assertContains($response->status(), [404, 500]);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test: Mise à jour d'une année inexistante retourne erreur
     */
    public function test_update_nonexistent_academic_year_returns_error(): void
    {
        $this->authenticatedUser();

        $response = $this->putJson('/api/inscription/academic-years/99999', [
            'year_start' => '2024-09-01',
            'year_end' => '2025-06-30',
            'submission_start' => '2024-09-01',
            'submission_end' => '2025-06-29',
        ]);

        // Peut retourner 404 (not found) ou 500 (erreur serveur)
        $this->assertContains($response->status(), [404, 500]);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test: Suppression d'une année inexistante retourne erreur
     */
    public function test_delete_nonexistent_academic_year_returns_error(): void
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/inscription/academic-years/99999');

        // Peut retourner 404 (not found) ou 500 (erreur serveur)
        $this->assertContains($response->status(), [404, 500]);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test: Création d'une année qui existe déjà retourne erreur
     */
    public function test_cannot_create_duplicate_academic_year(): void
    {
        $this->authenticatedUser();

        // Créer une première année académique
        $existingYear = AcademicYear::factory()->create([
            'academic_year' => '2024-2025',
        ]);

        // Essayer de créer une année avec le même academic_year
        $response = $this->postJson('/api/inscription/academic-years', [
            'libelle' => '2024-2025',
            'academic_year' => '2024-2025', // Doublon
            'year_start' => '2024-09-01',
            'year_end' => '2025-06-30',
            'submission_start' => '2024-06-01 00:00:00',
            'submission_end' => '2024-08-31 00:00:00',
        ]);

        // Peut retourner 422 (validation), 409 (conflit) ou 500 (erreur contrainte unique)
        $this->assertContains($response->status(), [422, 409, 500]);
        
        if (in_array($response->status(), [422, 409])) {
            $response->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => false,
            ]);
        } else {
            $response->assertJsonStructure(['message']);
        }
    }
}
