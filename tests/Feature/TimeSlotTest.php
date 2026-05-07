<?php

namespace Tests\Feature;

use App\Modules\EmploiDuTemps\Models\TimeSlot;
use Tests\TestCase;

class TimeSlotTest extends TestCase
{
    /**
     * Test: Liste des créneaux nécessite authentification
     */
    public function test_get_time_slots_requires_authentication(): void
    {
        $response = $this->getJson('/api/emploi-temps/time-slots');

        $response->assertstatus(401);
    }

    /**
     * Test: Récupérer la liste des créneaux
     */
    public function test_authenticated_user_can_get_time_slots(): void
    {
        $this->authenticatedUser();
        TimeSlot::factory()->count(3)->create();

        $response = $this->getJson('/api/emploi-temps/time-slots');

        $response->assertstatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'day_of_week',
                        'start_time',
                        'end_time',
                        'type',
                        'name',
                        'duration_in_minutes',
                        'duration_in_hours',
                    ],
                ],
                'meta',
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Créer un créneau horaire
     */
    public function test_authenticated_user_can_create_time_slot(): void
    {
        $this->authenticatedUser();

        $data = [
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'type' => 'lecture',
            'name' => 'Matinée - Bloc 1',
        ];

        $response = $this->postJson('/api/emploi-temps/time-slots', $data);

        $response->assertstatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'day_of_week' => 'monday',
                    'duration_in_hours' => 2,
                ],
            ]);

        $this->assertDatabaseHas('time_slots', [
            'day_of_week' => 'monday',
        ]);

        // Vérifier que start_time et end_time sont présents (format peut varier)
        $this->assertNotNull($response->json('data.start_time'));
        $this->assertNotNull($response->json('data.end_time'));
    }

    /**
     * Test: Validation lors de la création
     */
    public function test_time_slot_creation_requires_valid_data(): void
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/emploi-temps/time-slots', []);

        $this->assertValidationErrors($response, ['day_of_week', 'start_time', 'end_time', 'type']);
    }

    /**
     * Test: L'heure de fin doit être après l'heure de début
     */
    public function test_end_time_must_be_after_start_time(): void
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/emploi-temps/time-slots', [
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '08:00',
            'type' => 'lecture',
        ]);

        $this->assertValidationErrors($response, ['end_time']);
    }

    /**
     * Test: Mettre à jour un créneau
     */
    public function test_authenticated_user_can_update_time_slot(): void
    {
        $this->authenticatedUser();
        $timeSlot = TimeSlot::factory()->create();

        $response = $this->putJson("/api/emploi-temps/time-slots/{$timeSlot->id}", [
            'name' => 'Créneau Modifié',
        ]);

        $response->assertstatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Créneau Modifié',
                ],
            ]);

        $this->assertDatabaseHas('time_slots', [
            'id' => $timeSlot->id,
            'name' => 'Créneau Modifié',
        ]);
    }

    /**
     * Test: Supprimer un créneau
     */
    public function test_authenticated_user_can_delete_time_slot(): void
    {
        $this->authenticatedUser();
        $timeSlot = TimeSlot::factory()->create();

        $response = $this->deleteJson("/api/emploi-temps/time-slots/{$timeSlot->id}");

        $response->assertstatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('time_slots', [
            'id' => $timeSlot->id,
        ]);
    }

    /**
     * Test: Filtrer par jour de la semaine
     */
    public function test_can_filter_time_slots_by_day(): void
    {
        $this->authenticatedUser();
        TimeSlot::factory()->monday()->create();
        TimeSlot::factory()->create(['day_of_week' => 'tuesday']);

        $response = $this->getJson('/api/emploi-temps/time-slots?day_of_week=monday');

        $response->assertstatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Test: Filtrer par type
     */
    public function test_can_filter_time_slots_by_type(): void
    {
        $this->authenticatedUser();
        TimeSlot::factory()->lecture()->create();
        TimeSlot::factory()->td()->create();

        $response = $this->getJson('/api/emploi-temps/time-slots?type=lecture');

        $response->assertstatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Test: Récupérer les créneaux d'un jour spécifique
     */
    public function test_can_get_time_slots_by_day(): void
    {
        $this->authenticatedUser();
        TimeSlot::factory()->monday()->count(3)->create();
        TimeSlot::factory()->create(['day_of_week' => 'tuesday']);

        $response = $this->getJson('/api/emploi-temps/time-slots/day/monday');

        $response->assertstatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test: Le calcul de durée fonctionne correctement
     */
    public function test_duration_calculation_is_correct(): void
    {
        $this->authenticatedUser();

        $data = [
            'day_of_week' => 'monday',
            'start_time' => '08:00',
            'end_time' => '10:30',
            'type' => 'lecture',
        ];

        $response = $this->postJson('/api/emploi-temps/time-slots', $data);

        $response->assertstatus(201)
            ->assertJson([
                'data' => [
                    'duration_in_minutes' => 150,
                    'duration_in_hours' => 2.5,
                ],
            ]);
    }
}
