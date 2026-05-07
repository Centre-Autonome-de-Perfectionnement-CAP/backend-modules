<?php

namespace Tests\Feature;

use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\SubmissionPeriod;
use Tests\TestCase;

class CycleTest extends TestCase
{
    /**
     * Test: Récupération de la liste des cycles avec départements
     */
    public function test_can_get_cycles_list(): void
    {
        $cycle = Cycle::factory()->create(['name' => 'Licence']);
        Department::factory()->count(2)->create(['cycle_id' => $cycle->id]);

        $response = $this->getJson('/api/inscription/cycles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'abbreviation',
                        'years_count',
                        'is_lmd',
                        'type',
                        'departments' => [
                            '*' => [
                                'id',
                                'uuid',
                                'name',
                                'cycle_id',
                                'next_level_id',
                                'abbreviation',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Cycles récupérés avec succès',
            ]);
    }

    /**
     * Test: Récupération des départements avec périodes (format filières)
     */
    public function test_can_get_all_departments_with_periods(): void
    {
        $cycle = Cycle::factory()->create(['name' => 'Licence', 'abbreviation' => 'L']);
        $department = Department::factory()->create([
            'cycle_id' => $cycle->id,
            'name' => 'Informatique',
        ]);

        $response = $this->getJson('/api/inscription/filieres');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'abbreviation',
                        'cycle',
                        'dateLimite',
                        'image',
                        'badge',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Départements récupérés avec succès',
            ]);

        $data = $response->json('data');
        foreach ($data as $filiere) {
            $this->assertContains(
                $filiere['badge'],
                ['inscriptions-ouvertes', 'inscriptions-fermees', 'prochainement', null]
            );
        }
    }

    /**
     * Test: Récupération de la prochaine deadline
     */
    public function test_can_get_next_deadline(): void
    {
        $cycle = Cycle::factory()->create();
        $department = Department::factory()->create(['cycle_id' => $cycle->id]);

        $response = $this->getJson('/api/inscription/next-deadline');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'periods' => [
                        '*' => [
                            'deadline',
                            'filiere' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'abbreviation',
                                    'cycle'
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Deadlines récupérés avec succès',
            ]);
        $status = $response->json('data.status');
        $this->assertContains($status, ['open', 'closed']);
    }

    /**
     * Test: Récupération des niveaux d'études par département
     */
    public function test_can_get_study_levels(): void
    {
        $cycle = Cycle::factory()->create();
        $department = Department::factory()->create(['cycle_id' => $cycle->id]);

        $response = $this->getJson('/api/inscription/niveaux');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'label',
                        'value'
                    ]
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Niveaux récupérés avec succès',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);

        if (!empty($data)) {
            foreach ($data as $departmentLevels) {
                $this->assertIsArray($departmentLevels);
                if (!empty($departmentLevels)) {
                    foreach ($departmentLevels as $level) {
                        $this->assertArrayHasKey('value', $level);
                        $this->assertArrayHasKey('label', $level);
                    }
                }
            }
        }
    }

    /**
     * Test: Routes cycles sont publiques (pas d'authentification requise)
     */
    public function test_cycles_routes_are_public(): void
    {
        $response1 = $this->getJson('/api/inscription/cycles');
        $response2 = $this->getJson('/api/inscription/filieres');
        $response3 = $this->getJson('/api/inscription/next-deadline');
        $response4 = $this->getJson('/api/inscription/niveaux');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);
        $response4->assertStatus(200);
    }

    /**
     * Test: Liste vide retourne une structure valide
     */
    public function test_empty_cycles_list_returns_valid_structure(): void
    {
        $response = $this->getJson('/api/inscription/cycles');
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
}
