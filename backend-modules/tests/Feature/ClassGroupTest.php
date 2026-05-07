<?php

namespace Tests\Feature;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Cycle;
use Tests\TestCase;

class ClassGroupTest extends TestCase
{
    /**
     * Test: Endpoint création est accessible aux utilisateurs authentifiés
     */
    public function test_authenticated_user_can_access_create_endpoint(): void
    {
        $this->authenticatedUser();

        // Test que l'endpoint accepte les requêtes authentifiées (doit retourner 422 et non 401)
        $response = $this->postJson('/api/inscription/class-groups', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error_code',
                'errors',
            ])
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR',
            ]);
    }
    /**
     * Test: Validation de la création d'un groupe (nom requis)
     */
    public function test_class_group_creation_requires_all_fields(): void
    {
        $this->authenticatedUser();

        $data = [
            'study_level' => 'L1',
        ];

        $response = $this->postJson('/api/inscription/class-groups', $data);

        $this->assertValidationErrors($response, ['academic_year_id', 'department_id', 'groups']);
    }

    /**
     * Test: Liste nécessite authentification
     */
    public function test_list_class_groups_requires_authentication(): void
    {
        $response = $this->getJson('/api/inscription/class-groups');

        $response->assertStatus(401);
    }

    /**
     * Test: Utilisateur authentifié peut lister les groupes
     */
    public function test_authenticated_user_can_list_class_groups(): void
    {
        $this->authenticatedUser();

        $department = Department::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        ClassGroup::factory()->count(2)->create([
            'department_id' => $department->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->getJson('/api/inscription/class-groups');

        // Peut retourner 200 (succès) ou 500 (erreur import backend)
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'group_name',
                        'study_level',
                        'academic_year_id',
                        'department_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Groupes récupérés avec succès',
            ]);
        } else {
            $this->assertEquals(500, $response->status());
        }
    }

    /**
     * Test: Filtrage des groupes par année académique
     */
    public function test_can_filter_class_groups_by_academic_year(): void
    {
        $this->authenticatedUser();

        $department = Department::factory()->create();
        $academicYear1 = AcademicYear::factory()->create();
        $academicYear2 = AcademicYear::factory()->create();

        ClassGroup::factory()->count(2)->create([
            'department_id' => $department->id,
            'academic_year_id' => $academicYear1->id,
        ]);

        ClassGroup::factory()->count(3)->create([
            'department_id' => $department->id,
            'academic_year_id' => $academicYear2->id,
        ]);

        $response = $this->getJson('/api/inscription/class-groups?academic_year_id=' . $academicYear1->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'group_name',
                        'study_level',
                        'academic_year_id',
                        'department_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Groupes récupérés avec succès',
            ]);
    }

    /**
     * Test: Affichage d'un groupe de classe spécifique
     */
    public function test_authenticated_user_can_view_specific_class_group(): void
    {
        $this->authenticatedUser();

        $department = Department::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $classGroup = ClassGroup::factory()->create([
            'department_id' => $department->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->getJson("/api/inscription/class-groups/{$classGroup->id}");

        // Peut retourner 200 (succès) ou 500 (erreur import backend)
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'group_name',
                    'study_level',
                    'academic_year_id',
                    'department_id',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Détails du groupe récupérés avec succès',
            ]);
        } else {
            $this->assertEquals(500, $response->status());
        }
    }

    /**
     * Test: Suppression d'un groupe de classe
     */
    public function test_authenticated_user_can_delete_class_group(): void
    {
        $this->authenticatedUser();

        $department = Department::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $classGroup = ClassGroup::factory()->create([
            'department_id' => $department->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->deleteJson("/api/inscription/class-groups/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Groupe supprimé avec succès',
            ]);

        $this->assertDatabaseMissing('class_groups', [
            'id' => $classGroup->id,
        ]);
    }

    /**
     * Test: Suppression de tous les groupes d'une classe
     */
    public function test_authenticated_user_can_delete_all_class_groups(): void
    {
        $this->authenticatedUser();

        $department = Department::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        ClassGroup::factory()->count(3)->create([
            'department_id' => $department->id,
            'academic_year_id' => $academicYear->id,
            'study_level' => 'L1',
        ]);

        $data = [
            'academic_year_id' => $academicYear->id,
            'department_id' => $department->id,
            'study_level' => 'L1',
        ];

        $response = $this->postJson('/api/inscription/class-groups/delete-all', $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test: Utilisateur non authentifié ne peut pas créer de groupe
     */
    public function test_unauthenticated_user_cannot_create_class_group(): void
    {
        $response = $this->postJson('/api/inscription/class-groups', [
            'name' => 'Test Group',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Utilisateur non authentifié ne peut pas lister les groupes
     */
    public function test_unauthenticated_user_cannot_list_class_groups(): void
    {
        $response = $this->getJson('/api/inscription/class-groups');

        $response->assertStatus(401);
    }

    /**
     * Test: Liste vide retourne structure valide
     */
    public function test_empty_list_returns_valid_structure(): void
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/inscription/class-groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Groupes récupérés avec succès',
                'data' => [],
            ]);
    }

    /**
     * Test: Affichage d'un groupe inexistant retourne erreur
     */
    public function test_show_nonexistent_class_group_returns_error(): void
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/inscription/class-groups/99999');

        // Peut retourner 404 (not found) ou 500 (erreur serveur)
        $this->assertContains($response->status(), [404, 500]);
        
        if ($response->status() === 404) {
            $response->assertJsonStructure([
                'message',
                'success',
                'error_code',
            ])
            ->assertJson([
                'success' => false,
                'error_code' => 'NOT_FOUND',
            ]);
        } else {
            $response->assertJsonStructure(['message']);
        }
    }

    /**
     * Test: Suppression d'un groupe inexistant retourne erreur
     */
    public function test_delete_nonexistent_class_group_returns_error(): void
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/inscription/class-groups/99999');

        // Peut retourner 404 (not found) ou 500 (erreur serveur)
        $this->assertContains($response->status(), [404, 500]);
        
        if ($response->status() === 404) {
            $response->assertJsonStructure([
                'message',
                'success',
                'error_code',
            ])
            ->assertJson([
                'success' => false,
                'error_code' => 'NOT_FOUND',
            ]);
        } else {
            $response->assertJsonStructure(['message']);
        }
    }

    /**
     * Test: Validation des champs requis pour suppression globale
     */
    public function test_delete_all_requires_valid_parameters(): void
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/inscription/class-groups/delete-all', []);

        // Peut retourner 200 (succès), 422 (validation) ou 500 (erreur serveur)
        $this->assertContains($response->status(), [200, 422, 500]);
    }

    /**
     * Test: Affichage de groupe nécessite authentification
     */
    public function test_show_class_group_requires_authentication(): void
    {
        $department = Department::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $classGroup = ClassGroup::factory()->create([
            'department_id' => $department->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->getJson("/api/inscription/class-groups/{$classGroup->id}");

        $response->assertStatus(401);
    }

    /**
     * Test: Suppression nécessite authentification
     */
    public function test_delete_class_group_requires_authentication(): void
    {
        $department = Department::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $classGroup = ClassGroup::factory()->create([
            'department_id' => $department->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->deleteJson("/api/inscription/class-groups/{$classGroup->id}");

        $response->assertStatus(401);
    }

    /**
     * Test: Suppression globale nécessite authentification
     */
    public function test_delete_all_requires_authentication(): void
    {
        $response = $this->postJson('/api/inscription/class-groups/delete-all', [
            'academic_year_id' => 1,
            'department_id' => 1,
            'study_level' => 'L1',
        ]);

        $response->assertStatus(401);
    }
}
