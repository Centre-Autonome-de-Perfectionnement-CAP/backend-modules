<?php

namespace Tests\Feature;

use App\Modules\EmploiDuTemps\Models\Building;
use Tests\TestCase;

class BuildingTest extends TestCase
{
    /**
     * Test: Liste des bâtiments nécessite authentification
     */
    public function test_get_buildings_requires_authentication(): void
    {
        $response = $this->getJson('/api/emploi-temps/buildings');

        $response->assertstatus(401);
    }

    /**
     * Test: Récupérer la liste des bâtiments
     */
    public function test_authenticated_user_can_get_buildings(): void
    {
        $this->authenticatedUser();
        Building::factory()->count(3)->create();

        $response = $this->getJson('/api/emploi-temps/buildings');

        $response->assertstatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'name',
                        'code',
                        'address',
                        'description',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Créer un bâtiment
     */
    public function test_authenticated_user_can_create_building(): void
    {
        $this->authenticatedUser();

        $data = [
            'name' => 'Bâtiment Sciences',
            'code' => 'BSC',
            'address' => '123 Rue de l\'Université',
            'description' => 'Bâtiment principal des sciences',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/emploi-temps/buildings', $data);

        $response->assertstatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'code',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Bâtiment Sciences',
                    'code' => 'BSC',
                ],
            ]);

        $this->assertDatabaseHas('buildings', [
            'name' => 'Bâtiment Sciences',
            'code' => 'BSC',
        ]);
    }

    /**
     * Test: Validation lors de la création
     */
    public function test_building_creation_requires_valid_data(): void
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/emploi-temps/buildings', []);

        $this->assertValidationErrors($response, ['name', 'code']);
    }

    /**
     * Test: Le code du bâtiment doit être unique
     */
    public function test_building_code_must_be_unique(): void
    {
        $this->authenticatedUser();
        Building::factory()->create(['code' => 'BSC']);

        $response = $this->postJson('/api/emploi-temps/buildings', [
            'name' => 'Autre Bâtiment',
            'code' => 'BSC',
        ]);

        $this->assertValidationErrors($response, ['code']);
    }

    /**
     * Test: Afficher un bâtiment spécifique
     */
    public function test_authenticated_user_can_show_building(): void
    {
        $this->authenticatedUser();
        $building = Building::factory()->create();

        $response = $this->getJson("/api/emploi-temps/buildings/{$building->id}");

        $response->assertstatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $building->id,
                    'name' => $building->name,
                    'code' => $building->code,
                ],
            ]);
    }

    /**
     * Test: Mettre à jour un bâtiment
     */
    public function test_authenticated_user_can_update_building(): void
    {
        $this->authenticatedUser();
        $building = Building::factory()->create();

        $response = $this->putJson("/api/emploi-temps/buildings/{$building->id}", [
            'name' => 'Bâtiment Mis à Jour',
            'code' => $building->code,
        ]);

        $response->assertstatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Bâtiment Mis à Jour',
                ],
            ]);

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'name' => 'Bâtiment Mis à Jour',
        ]);
    }

    /**
     * Test: Supprimer un bâtiment
     */
    public function test_authenticated_user_can_delete_building(): void
    {
        $this->authenticatedUser();
        $building = Building::factory()->create();

        $response = $this->deleteJson("/api/emploi-temps/buildings/{$building->id}");

        $response->assertstatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('buildings', [
            'id' => $building->id,
        ]);
    }

    /**
     * Test: Filtrer les bâtiments par recherche
     */
    public function test_can_filter_buildings_by_search(): void
    {
        $this->authenticatedUser();
        Building::factory()->create(['name' => 'Bâtiment Sciences']);
        Building::factory()->create(['name' => 'Bâtiment Arts']);

        $response = $this->getJson('/api/emploi-temps/buildings?search=Sciences');

        $response->assertstatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Test: Filtrer les bâtiments par status actif
     */
    public function test_can_filter_buildings_by_active_status(): void
    {
        $this->authenticatedUser();
        Building::factory()->create(['is_active' => true]);
        Building::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/emploi-temps/buildings?is_active=1');

        $response->assertstatus(200)
            ->assertJsonPath('meta.total', 1);
    }
}
