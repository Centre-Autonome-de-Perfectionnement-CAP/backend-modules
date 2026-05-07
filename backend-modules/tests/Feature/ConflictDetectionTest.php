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

class ConflictDetectionTest extends TestCase
{
    /**
     * Helper: Créer un programme complet
     */
    protected function createProgram(?ClassGroup $classGroup = null, ?Professor $professor = null): Program
    {
        $classGroup = $classGroup ?? ClassGroup::factory()->create();
        $teachingUnit = TeachingUnit::factory()->create();
        $courseElement = CourseElement::factory()->create([
            'teaching_unit_id' => $teachingUnit->id,
        ]);
        $professor = $professor ?? Professor::factory()->create();
        
        $courseElementProfessor = CourseElementProfessor::create([
            'course_element_id' => $courseElement->id,
            'professor_id' => $professor->id,
        ]);

        return Program::create([
            'class_group_id' => $classGroup->id,
            'course_element_professor_id' => $courseElementProfessor->id,
        ]);
    }

    /**
     * Test: Vérifier l'absence de conflits
     */
    public function test_check_conflicts_returns_no_conflict_when_slot_is_free(): void
    {
        $this->authenticatedUser();
        $program = $this->createProgram();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        $response = $this->postJson('/api/emploi-temps/scheduled-courses/check-conflicts', [
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'is_recurring' => true,
            'recurrence_end_date' => Carbon::now()->addDays(70)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_conflicts' => false,
                ],
            ]);
    }

    /**
     * Test: Détecter un conflit de salle
     */
    public function test_detects_room_conflict(): void
    {
        $this->authenticatedUser();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();
        $startDate = Carbon::now()->addDays(7);

        // Créer un premier cours
        $program1 = $this->createProgram();
        ScheduledCourse::create([
            'program_id' => $program1->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => $startDate,
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        // Essayer de créer un deuxième cours avec la même salle et même créneau
        $program2 = $this->createProgram();

        $response = $this->postJson('/api/emploi-temps/scheduled-courses/check-conflicts', [
            'program_id' => $program2->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => $startDate->format('Y-m-d'),
            'is_recurring' => true,
            'recurrence_end_date' => $startDate->copy()->addDays(70)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'has_conflicts' => true,
                ],
            ])
            ->assertJsonPath('data.conflicts.0.type', 'room');
    }

    /**
     * Test: Détecter un conflit de professeur
     */
    public function test_detects_professor_conflict(): void
    {
        $this->authenticatedUser();
        $professor = Professor::factory()->create();
        $timeSlot = TimeSlot::factory()->create();
        $startDate = Carbon::now()->addDays(7);

        // Créer un premier cours avec ce professeur
        $program1 = $this->createProgram(null, $professor);
        $room1 = Room::factory()->create();

        ScheduledCourse::create([
            'program_id' => $program1->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room1->id,
            'start_date' => $startDate,
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        // Essayer de créer un deuxième cours avec le même professeur au même créneau
        $program2 = $this->createProgram(null, $professor);
        $room2 = Room::factory()->create();

        $response = $this->postJson('/api/emploi-temps/scheduled-courses/check-conflicts', [
            'program_id' => $program2->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room2->id,
            'start_date' => $startDate->format('Y-m-d'),
            'is_recurring' => true,
            'recurrence_end_date' => $startDate->copy()->addDays(70)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'has_conflicts' => true,
                ],
            ])
            ->assertJsonPath('data.conflicts.0.type', 'professor');
    }

    /**
     * Test: Détecter un conflit de groupe de classe
     */
    public function test_detects_class_group_conflict(): void
    {
        $this->authenticatedUser();
        $classGroup = ClassGroup::factory()->create();
        $timeSlot = TimeSlot::factory()->create();
        $startDate = Carbon::now()->addDays(7);

        // Créer un premier cours pour ce groupe
        $program1 = $this->createProgram($classGroup);
        $room1 = Room::factory()->create();

        ScheduledCourse::create([
            'program_id' => $program1->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room1->id,
            'start_date' => $startDate,
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        // Essayer de créer un deuxième cours pour le même groupe au même créneau
        $program2 = $this->createProgram($classGroup);
        $room2 = Room::factory()->create();

        $response = $this->postJson('/api/emploi-temps/scheduled-courses/check-conflicts', [
            'program_id' => $program2->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room2->id,
            'start_date' => $startDate->format('Y-m-d'),
            'is_recurring' => true,
            'recurrence_end_date' => $startDate->copy()->addDays(70)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'has_conflicts' => true,
                ],
            ])
            ->assertJsonPath('data.conflicts.0.type', 'class_group');
    }

    /**
     * Test: La création échoue si des conflits sont détectés
     */
    public function test_creation_fails_when_conflicts_exist(): void
    {
        $this->authenticatedUser();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();
        $startDate = Carbon::now()->addDays(7);

        // Créer un premier cours
        $program1 = $this->createProgram();
        ScheduledCourse::create([
            'program_id' => $program1->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => $startDate,
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        // Essayer de créer un deuxième cours avec conflit
        $program2 = $this->createProgram();

        $response = $this->postJson('/api/emploi-temps/scheduled-courses', [
            'program_id' => $program2->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => $startDate->format('Y-m-d'),
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Des conflits ont été détectés',
            ]);
    }

    /**
     * Test: Les cours annulés ne créent pas de conflits
     */
    public function test_cancelled_courses_do_not_create_conflicts(): void
    {
        $this->authenticatedUser();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();
        $startDate = Carbon::now()->addDays(7);

        // Créer un premier cours et l'annuler
        $program1 = $this->createProgram();
        ScheduledCourse::create([
            'program_id' => $program1->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => $startDate,
            'total_hours' => 42,
            'is_recurring' => true,
            'is_cancelled' => true,
        ]);

        // Créer un deuxième cours - ne devrait pas avoir de conflit
        $program2 = $this->createProgram();

        $response = $this->postJson('/api/emploi-temps/scheduled-courses/check-conflicts', [
            'program_id' => $program2->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => $startDate->format('Y-m-d'),
            'is_recurring' => true,
            'recurrence_end_date' => $startDate->copy()->addDays(70)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'has_conflicts' => false,
                ],
            ]);
    }

    /**
     * Test: Peut mettre à jour un cours sans conflit avec lui-même
     */
    public function test_can_update_course_without_self_conflict(): void
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

        // Mettre à jour le cours devrait fonctionner
        $response = $this->putJson("/api/emploi-temps/scheduled-courses/{$scheduledCourse->id}", [
            'notes' => 'Notes modifiées',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Détecte des conflits multiples
     */
    public function test_detects_multiple_conflicts_simultaneously(): void
    {
        $this->authenticatedUser();
        $professor = Professor::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();
        $startDate = Carbon::now()->addDays(7);

        // Créer un premier cours
        $program1 = $this->createProgram($classGroup, $professor);
        ScheduledCourse::create([
            'program_id' => $program1->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => $startDate,
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        // Essayer de créer un deuxième cours avec le même prof, groupe ET salle
        $program2 = $this->createProgram($classGroup, $professor);

        $response = $this->postJson('/api/emploi-temps/scheduled-courses/check-conflicts', [
            'program_id' => $program2->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => $startDate->format('Y-m-d'),
            'is_recurring' => true,
            'recurrence_end_date' => $startDate->copy()->addDays(70)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'has_conflicts' => true,
                ],
            ]);

        // Devrait détecter au moins 3 types de conflits
        $conflicts = $response->json('data.conflicts');
        $this->assertGreaterThanOrEqual(3, count($conflicts));
    }
}
