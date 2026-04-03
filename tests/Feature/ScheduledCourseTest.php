<?php

namespace Tests\Feature;

use App\Modules\EmploiDuTemps\Models\Room;
use App\Modules\EmploiDuTemps\Models\TimeSlot;
use App\Modules\EmploiDuTemps\Models\ScheduledCourse;
use App\Modules\Cours\Models\Program;
use App\Modules\Cours\Models\CourseElement;
use App\Modules\Cours\Models\CourseElementProfessor;
use App\Modules\Cours\Models\TeachingUnit;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\RH\Models\Professor;
use Tests\TestCase;
use Carbon\Carbon;

class ScheduledCourseTest extends TestCase
{
    /**
     * Helper: Créer un programme complet avec ses dépendances
     */
    protected function createProgram(): Program
    {
        $classGroup = ClassGroup::factory()->create();
        $professor = Professor::factory()->create();
        
        // Créer un Program temporaire pour satisfaire la contrainte de TeachingUnit
        $tempProgram = Program::create([
            'class_group_id' => $classGroup->id,
            'course_element_professor_id' => 1, // Temporaire
        ]);
        
        // Créer TeachingUnit avec le program_id
        $teachingUnit = TeachingUnit::create([
            'name' => 'Test Teaching Unit',
            'code' => 'TEST-' . uniqid(),
            'program_id' => $tempProgram->id,
        ]);
        
        $courseElement = CourseElement::create([
            'name' => 'Test Course Element',
            'code' => 'CE-' . uniqid(),
            'credits' => 6,
            'teaching_unit_id' => $teachingUnit->id,
        ]);
        
        $courseElementProfessor = CourseElementProfessor::create([
            'course_element_id' => $courseElement->id,
            'professor_id' => $professor->id,
        ]);

        // Mettre à jour le program avec le bon course_element_professor_id
        $tempProgram->update([
            'course_element_professor_id' => $courseElementProfessor->id,
        ]);

        return $tempProgram->fresh();
    }

    /**
     * Test: Liste des cours planifiés nécessite authentification
     */
    public function test_get_scheduled_courses_requires_authentication(): void
    {
        $response = $this->getJson('/api/emploi-temps/scheduled-courses');

        $response->assertStatus(401);
    }

    /**
     * Test: Récupérer la liste des cours planifiés
     */
    public function test_authenticated_user_can_get_scheduled_courses(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        $response = $this->getJson('/api/emploi-temps/scheduled-courses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'program_id',
                        'time_slot_id',
                        'room_id',
                        'start_date',
                        'total_hours',
                        'hours_completed',
                        'remaining_hours',
                        'progress_percentage',
                        'is_recurring',
                        'is_cancelled',
                    ],
                ],
                'meta',
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Créer un cours planifié
     */
    public function test_authenticated_user_can_create_scheduled_course(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $data = [
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'total_hours' => 42,
            'is_recurring' => true,
            'notes' => 'Cours de test',
        ];

        $response = $this->postJson('/api/emploi-temps/scheduled-courses', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'program_id' => $program->id,
                    'total_hours' => 42.0,
                    'is_recurring' => true,
                ],
            ]);

        $this->assertDatabaseHas('scheduled_courses', [
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
        ]);
    }

    /**
     * Test: Validation lors de la création
     */
    public function test_scheduled_course_creation_requires_valid_data(): void
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/emploi-temps/scheduled-courses', []);

        $this->assertValidationErrors($response, [
            'program_id',
            'time_slot_id',
            'room_id',
            'start_date',
            'total_hours',
        ]);
    }

    /**
     * Test: La date de début doit être dans le futur
     */
    public function test_start_date_must_be_in_future(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $response = $this->postJson('/api/emploi-temps/scheduled-courses', [
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'total_hours' => 42,
        ]);

        $this->assertValidationErrors($response, ['start_date']);
    }

    /**
     * Test: Mettre à jour un cours planifié
     */
    public function test_authenticated_user_can_update_scheduled_course(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $scheduledCourse = ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        $response = $this->putJson("/api/emploi-temps/scheduled-courses/{$scheduledCourse->id}", [
            'notes' => 'Notes modifiées',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'notes' => 'Notes modifiées',
                ],
            ]);
    }

    /**
     * Test: Supprimer un cours planifié
     */
    public function test_authenticated_user_can_delete_scheduled_course(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $scheduledCourse = ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
        ]);

        $response = $this->deleteJson("/api/emploi-temps/scheduled-courses/{$scheduledCourse->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('scheduled_courses', [
            'id' => $scheduledCourse->id,
        ]);
    }

    /**
     * Test: Annuler un cours
     */
    public function test_can_cancel_scheduled_course(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $scheduledCourse = ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
        ]);

        $response = $this->postJson("/api/emploi-temps/scheduled-courses/{$scheduledCourse->id}/cancel", [
            'notes' => 'Annulation pour test',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_cancelled' => true,
                    'notes' => 'Annulation pour test',
                ],
            ]);

        $this->assertDatabaseHas('scheduled_courses', [
            'id' => $scheduledCourse->id,
            'is_cancelled' => true,
        ]);
    }

    /**
     * Test: Mettre à jour les heures effectuées
     */
    public function test_can_update_completed_hours(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $scheduledCourse = ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
        ]);

        $response = $this->postJson("/api/emploi-temps/scheduled-courses/{$scheduledCourse->id}/update-hours", [
            'hours_completed' => 12.5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'hours_completed' => 12.5,
                    'remaining_hours' => 29.5,
                ],
            ]);
    }

    /**
     * Test: Exclure une date d'un cours récurrent
     */
    public function test_can_exclude_date_from_recurring_course(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $scheduledCourse = ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        $dateToExclude = Carbon::now()->addDays(14)->format('Y-m-d');

        $response = $this->postJson("/api/emploi-temps/scheduled-courses/{$scheduledCourse->id}/exclude-date", [
            'date' => $dateToExclude,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $scheduledCourse->refresh();
        $this->assertContains($dateToExclude, $scheduledCourse->excluded_dates);
    }

    /**
     * Test: Obtenir les occurrences d'un cours récurrent
     */
    public function test_can_get_occurrences_of_recurring_course(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create(['start_time' => '08:00', 'end_time' => '10:00']);
        $room = Room::factory()->create();

        $scheduledCourse = ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 8,
            'is_recurring' => true,
        ]);

        $response = $this->getJson("/api/emploi-temps/scheduled-courses/{$scheduledCourse->id}/occurrences");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'occurrences',
                    'total_occurrences',
                ],
            ]);
    }

    /**
     * Test: Le calcul de progression fonctionne
     */
    public function test_progress_calculation_is_correct(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $scheduledCourse = ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 40,
            'hours_completed' => 10,
        ]);

        $response = $this->getJson("/api/emploi-temps/scheduled-courses/{$scheduledCourse->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'progress_percentage' => 25.0,
                    'remaining_hours' => 30.0,
                ],
            ]);
    }
}
