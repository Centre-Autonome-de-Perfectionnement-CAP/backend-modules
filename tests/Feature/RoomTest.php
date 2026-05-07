<?php

namespace Tests\Feature;

use App\Modules\EmploiDuTemps\Models\Building;
use App\Modules\EmploiDuTemps\Models\Room;
use App\Modules\EmploiDuTemps\Models\TimeSlot;
use Tests\TestCase;

class RoomTest extends TestCase
{
    /**
     * Test: Liste des salles nécessite authentification
     */
    public function test_get_rooms_requires_authentication(): void
    {
        $response = $this->getJson('/api/emploi-temps/rooms');

        $response->assertstatus(401);
    }

    /**
     * Test: Récupérer la liste des salles
     */
    public function test_authenticated_user_can_get_rooms(): void
    {
        $this->authenticatedUser();
        Room::factory()->count(3)->create();

        $response = $this->getJson('/api/emploi-temps/rooms');

        $response->assertstatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'building_id',
                        'name',
                        'code',
                        'capacity',
                        'room_type',
                        'equipment',
                        'is_available',
                        'building',
                    ],
                ],
                'meta',
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Créer une salle
     */
    public function test_authenticated_user_can_create_room(): void
    {
        $this->authenticatedUser();
        $building = Building::factory()->create();

        $data = [
            'building_id' => $building->id,
            'name' => 'Amphi A',
            'code' => 'BSC-A01',
            'capacity' => 200,
            'room_type' => 'amphitheater',
            'equipment' => ['projecteur', 'climatisation', 'microphones'],
            'is_available' => true,
        ];

        $response = $this->postJson('/api/emploi-temps/rooms', $data);

        $response->assertstatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Amphi A',
                    'code' => 'BSC-A01',
                    'capacity' => 200,
                ],
            ]);

        $this->assertDatabaseHas('rooms', [
            'name' => 'Amphi A',
            'code' => 'BSC-A01',
        ]);
    }

    /**
     * Test: Validation lors de la création
     */
    public function test_room_creation_requires_valid_data(): void
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/emploi-temps/rooms', []);

        $this->assertValidationErrors($response, ['building_id', 'name', 'code', 'capacity', 'room_type']);
    }

    /**
     * Test: Le code de la salle doit être unique
     */
    public function test_room_code_must_be_unique(): void
    {
        $this->authenticatedUser();
        $building = Building::factory()->create();
        Room::factory()->create(['code' => 'ROOM-001']);

        $response = $this->postJson('/api/emploi-temps/rooms', [
            'building_id' => $building->id,
            'name' => 'Salle Test',
            'code' => 'ROOM-001',
            'capacity' => 30,
            'room_type' => 'classroom',
        ]);

        $this->assertValidationErrors($response, ['code']);
    }

    /**
     * Test: La capacité doit être positive
     */
    public function test_room_capacity_must_be_positive(): void
    {
        $this->authenticatedUser();
        $building = Building::factory()->create();

        $response = $this->postJson('/api/emploi-temps/rooms', [
            'building_id' => $building->id,
            'name' => 'Salle Test',
            'code' => 'TEST-001',
            'capacity' => 0,
            'room_type' => 'classroom',
        ]);

        $this->assertValidationErrors($response, ['capacity']);
    }

    /**
     * Test: Mettre à jour une salle
     */
    public function test_authenticated_user_can_update_room(): void
    {
        $this->authenticatedUser();
        $room = Room::factory()->create();

        $response = $this->putJson("/api/emploi-temps/rooms/{$room->id}", [
            'name' => 'Salle Mise à Jour',
            'capacity' => 50,
        ]);

        $response->assertstatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Salle Mise à Jour',
                    'capacity' => 50,
                ],
            ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'Salle Mise à Jour',
            'capacity' => 50,
        ]);
    }

    /**
     * Test: Supprimer une salle
     */
    public function test_authenticated_user_can_delete_room(): void
    {
        $this->authenticatedUser();
        $room = Room::factory()->create();

        $response = $this->deleteJson("/api/emploi-temps/rooms/{$room->id}");

        $response->assertstatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('rooms', [
            'id' => $room->id,
        ]);
    }

    /**
     * Test: Filtrer par bâtiment
     */
    public function test_can_filter_rooms_by_building(): void
    {
        $this->authenticatedUser();
        $building1 = Building::factory()->create();
        $building2 = Building::factory()->create();

        Room::factory()->create(['building_id' => $building1->id]);
        Room::factory()->create(['building_id' => $building2->id]);

        $response = $this->getJson("/api/emploi-temps/rooms?building_id={$building1->id}");

        $response->assertstatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Test: Filtrer par type de salle
     */
    public function test_can_filter_rooms_by_type(): void
    {
        $this->authenticatedUser();
        Room::factory()->amphitheater()->create();
        Room::factory()->classroom()->create();

        $response = $this->getJson('/api/emploi-temps/rooms?room_type=amphitheater');

        $response->assertstatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Test: Filtrer par capacité minimale
     */
    public function test_can_filter_rooms_by_min_capacity(): void
    {
        $this->authenticatedUser();
        Room::factory()->create(['capacity' => 30]);
        Room::factory()->create(['capacity' => 100]);

        $response = $this->getJson('/api/emploi-temps/rooms?min_capacity=50');

        $response->assertstatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Test: Récupérer les salles disponibles
     */
    public function test_can_get_available_rooms_for_time_slot(): void
    {
        $this->authenticatedUser();
        $timeSlot = TimeSlot::factory()->create();
        Room::factory()->count(3)->create();

        $response = $this->getJson('/api/emploi-temps/rooms-available?time_slot_id=' . $timeSlot->id . '&date=2025-11-15');

        $response->assertstatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test: La requête de salles disponibles nécessite les paramètres obligatoires
     */
    public function test_available_rooms_requires_parameters(): void
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/emploi-temps/rooms-available');

        $this->assertValidationErrors($response, ['time_slot_id', 'date']);
    }
}
