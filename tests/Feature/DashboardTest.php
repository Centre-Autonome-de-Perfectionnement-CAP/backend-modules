<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Endpoint stats retourne les données correctes
     */
    public function test_stats_endpoint_returns_correct_data(): void
    {
        // Arrange - Créer des données de test
        $this->authenticatedUser();

        // Créer une année académique courante
        $currentYear = AcademicYear::factory()->create([
            'is_current' => true,
            'libelle' => '2024-2025'
        ]);

        // Créer des cycles d'abord
        $cycle1 = Cycle::factory()->create();
        $cycle2 = Cycle::factory()->create();

        // Créer des filières en utilisant les cycles existants
        $dept1 = Department::factory()->create(['cycle_id' => $cycle1->id]);
        $dept2 = Department::factory()->create(['cycle_id' => $cycle1->id]);
        $dept3 = Department::factory()->create(['cycle_id' => $cycle2->id]);

        // Créer des étudiants avec différents statuss en utilisant les départements existants
        PendingStudent::factory()->count(5)->create([
            'status' => 'approved',
            'department_id' => $dept1->id,
            'academic_year_id' => $currentYear->id,
        ]);
        PendingStudent::factory()->count(3)->create([
            'status' => 'pending',
            'department_id' => $dept2->id,
            'academic_year_id' => $currentYear->id,
        ]);
        PendingStudent::factory()->count(2)->create([
            'status' => 'rejected',
            'department_id' => $dept3->id,
            'academic_year_id' => $currentYear->id,
        ]);

        // Act
        $response = $this->getJson('/api/inscription/dashboard/stats');

        // Assert
        $response->assertstatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'inscritsCap',
                    'dossiersAttente',
                    'anneeAcademique',
                    'nombreFilieres',
                    'nombreCycles',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Statistiques récupérées avec succès',
                'data' => [
                    'inscritsCap' => 5,
                    'dossiersAttente' => 3, // 3 pending seulement
                    'anneeAcademique' => '2024-2025',
                    'nombreFilieres' => 3,
                    'nombreCycles' => 2,
                ]
            ]);
    }

    /**
     * Test: Endpoint stats sans année académique courante
     */
    public function test_stats_without_current_academic_year(): void
    {
        // Arrange
        $this->authenticatedUser();

        // Ne pas créer d'année académique courante
        Department::factory()->count(2)->create();
        Cycle::factory()->count(1)->create();

        // Act
        $response = $this->getJson('/api/inscription/dashboard/stats');

        // Assert
        $response->assertstatus(200);

        $currentYear = date('Y');
        $expectedYear = $currentYear . '-' . ($currentYear + 1);

        $this->assertEquals($expectedYear, $response->json('data.anneeAcademique'));
    }

    /**
     * Test: Endpoint graphes retourne les données correctes
     */
    public function test_graphs_endpoint_returns_correct_data(): void
    {
        // Arrange
        $this->authenticatedUser();

        $academicYear = AcademicYear::factory()->create([
            'is_current' => true,
            'libelle' => '2024-2025'
        ]);

        $filieres = Department::factory()->count(2)->create();
        $cycles = Cycle::factory()->count(2)->create();

        // Créer des étudiants avec différents statuss
        PendingStudent::factory()->count(4)->create(['status' => 'approved']);
        PendingStudent::factory()->count(2)->create(['status' => 'pending']);
        PendingStudent::factory()->count(1)->create(['status' => 'rejected']);

        // Act
        $response = $this->getJson('/api/inscription/dashboard/graphes');

        // Assert
        $response->assertstatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'inscritsParFiliere' => [
                        '*' => [
                            'filiere',
                            'nombre',
                        ],
                    ],
                    'inscritsParCycle' => [
                        '*' => [
                            'cycle',
                            'nombre',
                        ],
                    ],
                    'dossiersParstatus' => [
                        '*' => [
                            'status',
                            'nombre',
                        ],
                    ],
                    'anneeAcademique',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Données graphiques récupérées avec succès',
                'data' => [
                    'anneeAcademique' => '2024-2025',
                ]
            ]);

        // Vérifier la structure des données de status
        $statusData = $response->json('data.dossiersParstatus');
        $this->assertIsArray($statusData);

        // Vérifier que les statuss sont bien traduits
        $statusLabels = array_column($statusData, 'status');
        $this->assertContains('Approuvé', $statusLabels);
        $this->assertContains('En attente', $statusLabels);
        $this->assertContains('Rejeté', $statusLabels);
    }

    /**
     * Test: Graphes avec paramètre année spécifique
     */
    public function test_graphs_with_specific_year_parameter(): void
    {
        // Arrange
        $this->authenticatedUser();

        Department::factory()->count(2)->create();
        Cycle::factory()->count(1)->create();
        PendingStudent::factory()->count(3)->create(['status' => 'approved']);

        // Act
        $response = $this->getJson('/api/inscription/dashboard/graphes?year=2023-2024');

        // Assert
        $response->assertstatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'anneeAcademique' => '2023-2024'
                ]
            ]);
    }

    /**
     * Test: Graphes sans données
     */
    public function test_graphs_with_no_data(): void
    {
        // Arrange - Base de données vide
        $this->authenticatedUser();

        // Act
        $response = $this->getJson('/api/inscription/dashboard/graphes');

        // Assert
        $response->assertstatus(200);

        $data = $response->json('data');

        // Vérifier que les tableaux existent même sans données
        $this->assertIsArray($data['inscritsParFiliere']);
        $this->assertIsArray($data['inscritsParCycle']);
        $this->assertIsArray($data['dossiersParstatus']);

        // Sans filières, devrait retourner le message par défaut
        $firstFiliere = $data['inscritsParFiliere'][0] ?? null;
        if ($firstFiliere) {
            $this->assertEquals('Aucune filière', $firstFiliere['filiere']);
            $this->assertEquals(0, $firstFiliere['nombre']);
        }
    }

    /**
     * Test: Accès non autorisé sans authentification
     */
    public function test_endpoints_require_authentication(): void
    {
        // Tester sans authentification
        $responseStats = $this->getJson('/api/inscription/dashboard/stats');
        $responseGraphs = $this->getJson('/api/inscription/dashboard/graphes');

        // Doit retourner 401 Unauthorized
        $responseStats->assertstatus(401);
        $responseGraphs->assertstatus(401);
    }

    /**
     * Test: Format d'année académique invalide
     */
    public function test_graphs_with_invalid_year_format(): void
    {
        // Arrange
        $this->authenticatedUser();

        // Act - Format d'année invalide
        $response = $this->getJson('/api/inscription/dashboard/graphes?year=2024');

        // Assert - Doit quand même fonctionner (le service gère les formats)
        $response->assertstatus(200);
    }

    /**
     * Test: Performance des endpoints
     */
    public function test_endpoints_performance(): void
    {
        // Arrange - Créer beaucoup de données
        $this->authenticatedUser();

        AcademicYear::factory()->create(['is_current' => true]);
        Department::factory()->count(10)->create();
        Cycle::factory()->count(5)->create();
        PendingStudent::factory()->count(100)->create();

        // Act & Assert - Vérifier que les endpoints répondent rapidement
        $startTime = microtime(true);

        $response = $this->getJson('/api/inscription/dashboard/stats');

        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertstatus(200);

        // Le temps de réponse devrait être raisonnable (moins de 2 secondes)
        $this->assertLessThan(2, $responseTime, "L'endpoint stats est trop lent");
    }
}
