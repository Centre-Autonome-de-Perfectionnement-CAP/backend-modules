<?php

namespace Tests\Feature;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Cycle;
use Tests\TestCase;

class PublicReferenceTest extends TestCase
{
    /**
     * Test: Récupération de la liste publique des années académiques
     */
    public function test_can_get_public_academic_years_list(): void
    {
        // Arrange - Créer 3 années académiques
        AcademicYear::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/inscription/public/academic-years');

        // Assert - Structure stricte
        $response->assertstatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'academic_year',
                        'year_start',
                        'year_end',
                        'submission_start',
                        'submission_end',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Années académiques récupérées avec succès',
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test: Récupération des années académiques pour un département spécifique
     */
    public function test_can_get_academic_years_for_department(): void
    {
        // Arrange - Créer un cycle, département et années
        $cycle = Cycle::factory()->create();
        $department = Department::factory()->create(['cycle_id' => $cycle->id]);
        AcademicYear::factory()->count(2)->create();

        // Act
        $response = $this->getJson("/api/inscription/public/academic-years/department/{$department->id}");

        // Assert - Structure stricte
        $response->assertstatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'academic_year',
                        'year_start',
                        'year_end',
                        'submission_start',
                        'submission_end',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Années académiques du département récupérées avec succès',
            ]);
    }

    /**
     * Test: Récupération de la liste publique des diplômes d'entrée
     */
    public function test_can_get_public_entry_diplomas(): void
    {
        // Act - Pas besoin de créer de données, le service peut retourner une liste par défaut
        $response = $this->getJson('/api/inscription/public/entry-diplomas');

        // Assert - Structure stricte
        $response->assertstatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Données récupérées avec succès',
            ]);

        // Vérifier que data est un array
        $this->assertIsArray($response->json('data'));
    }

    /**
     * Test: Routes sont publiques (pas d'authentification requise)
     */
    public function test_public_reference_routes_are_public(): void
    {
        // Arrange
        $cycle = Cycle::factory()->create();
        $department = Department::factory()->create(['cycle_id' => $cycle->id]);

        // Act - Accès sans authentification
        $response1 = $this->getJson('/api/inscription/public/academic-years');
        $response2 = $this->getJson("/api/inscription/public/academic-years/department/{$department->id}");
        $response3 = $this->getJson('/api/inscription/public/entry-diplomas');

        // Assert - Tous devraient retourner 200, pas 401
        $response1->assertstatus(200);
        $response2->assertstatus(200);
        $response3->assertstatus(200);
    }

    /**
     * Test: Liste vide d'années académiques retourne structure valide
     */
    public function test_empty_academic_years_list_returns_valid_structure(): void
    {
        // Arrange - Pas de données

        // Act
        $response = $this->getJson('/api/inscription/public/academic-years');

        // Assert
        $response->assertstatus(200)
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
     * Test: Département inexistant retourne structure valide (liste vide ou erreur)
     */
    public function test_nonexistent_department_returns_valid_response(): void
    {
        // Act - ID département inexistant
        $response = $this->getJson('/api/inscription/public/academic-years/department/99999');

        // Assert - Soit 200 avec liste vide, soit 404
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson(['success' => true]);
        } else {
            $response->assertstatus(404);
        }
    }

    /**
     * Test: Format de date des années académiques
     */
    public function test_academic_year_dates_format(): void
    {
        // Arrange
        AcademicYear::factory()->create([
            'academic_year' => '2024-2025',
            'year_start' => '2024-09-01',
            'year_end' => '2025-06-30',
        ]);

        // Act
        $response = $this->getJson('/api/inscription/public/academic-years');

        // Assert
        $response->assertstatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $firstYear = $data[0];
        $this->assertArrayHasKey('academic_year', $firstYear);
        $this->assertArrayHasKey('year_start', $firstYear);
        $this->assertArrayHasKey('year_end', $firstYear);

        // Vérifier le format de l'année académique
        $this->assertMatchesRegularExpression('/^\d{4}-\d{4}$/', $firstYear['academic_year']);
    }
}
