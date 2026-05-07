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

class ScheduleViewTest extends TestCase
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
     * Test: Récupérer l'emploi du temps d'un groupe de classe
     */
    public function test_can_get_schedule_by_class_group(): void
    {
        $this->authenticatedUser();
        $classGroup = ClassGroup::factory()->create();
        $program = $this->createProgram($classGroup);
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        // Créer plusieurs cours pour ce groupe
        ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
            'is_recurring' => true,
        ]);

        $response = $this->getJson("/api/emploi-temps/schedule/class-group/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'program_id',
                        'time_slot',
                        'room',
                        'start_date',
                    ],
                ],
            ]);
    }

    /**
     * Test: Récupérer l'emploi du temps d'un professeur
     */
    public function test_can_get_schedule_by_professor(): void
    {
        $this->authenticatedUser();
        $professor = Professor::factory()->create();
        $program = $this->createProgram(null, $professor);
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

        $response = $this->getJson("/api/emploi-temps/schedule/professor/{$professor->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'time_slot',
                        'room',
                        'class_group',
                    ],
                ],
            ]);
    }

    /**
     * Test: Récupérer l'emploi du temps d'une salle
     */
    public function test_can_get_schedule_by_room(): void
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

        $response = $this->getJson("/api/emploi-temps/schedule/room/{$room->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'time_slot',
                        'class_group',
                    ],
                ],
            ]);
    }

    /**
     * Test: Filtrer l'emploi du temps par période
     */
    public function test_can_filter_schedule_by_date_range(): void
    {
        $this->authenticatedUser();
        $classGroup = ClassGroup::factory()->create();
        $program = $this->createProgram($classGroup);
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        // Cours dans la période
        ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
        ]);

        // Cours hors période
        ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(100),
            'total_hours' => 42,
        ]);

        $startDate = Carbon::now()->format('Y-m-d');
        $endDate = Carbon::now()->addDays(30)->format('Y-m-d');

        $response = $this->getJson("/api/emploi-temps/schedule/class-group/{$classGroup->id}?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test: Les cours annulés ne sont pas inclus dans l'emploi du temps
     */
    public function test_cancelled_courses_are_not_included_in_schedule(): void
    {
        $this->authenticatedUser();
        $classGroup = ClassGroup::factory()->create();
        $program = $this->createProgram($classGroup);
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        // Cours actif
        ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
            'is_cancelled' => false,
        ]);

        // Cours annulé
        ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(14),
            'total_hours' => 42,
            'is_cancelled' => true,
        ]);

        $response = $this->getJson("/api/emploi-temps/schedule/class-group/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test: L'emploi du temps d'un groupe vide retourne une liste vide
     */
    public function test_empty_schedule_returns_empty_array(): void
    {
        $this->authenticatedUser();
        $classGroup = ClassGroup::factory()->create();

        $response = $this->getJson("/api/emploi-temps/schedule/class-group/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /**
     * Test: Peut récupérer l'emploi du temps avec plusieurs cours
     */
    public function test_can_get_schedule_with_multiple_courses(): void
    {
        $this->authenticatedUser();
        $classGroup = ClassGroup::factory()->create();
        $room = Room::factory()->create();

        // Créer 3 cours différents pour le même groupe
        foreach (range(1, 3) as $i) {
            $program = $this->createProgram($classGroup);
            $timeSlot = TimeSlot::factory()->create();

            ScheduledCourse::create([
                'program_id' => $program->id,
                'time_slot_id' => $timeSlot->id,
                'room_id' => $room->id,
                'start_date' => Carbon::now()->addDays($i * 7),
                'total_hours' => 42,
            ]);
        }

        $response = $this->getJson("/api/emploi-temps/schedule/class-group/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test: L'emploi du temps inclut toutes les informations nécessaires
     */
    public function test_schedule_includes_all_necessary_information(): void
    {
        $this->authenticatedUser();
        $classGroup = ClassGroup::factory()->create();
        $professor = Professor::factory()->create();
        $program = $this->createProgram($classGroup, $professor);
        $timeSlot = TimeSlot::factory()->create();
        $room = Room::factory()->create();

        ScheduledCourse::create([
            'program_id' => $program->id,
            'time_slot_id' => $timeSlot->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->addDays(7),
            'total_hours' => 42,
        ]);

        $response = $this->getJson("/api/emploi-temps/schedule/class-group/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'time_slot' => [
                            'day_of_week',
                            'start_time',
                            'end_time',
                        ],
                        'room' => [
                            'name',
                            'building',
                        ],
                        'course_element',
                        'professor',
                    ],
                ],
            ]);
    }
}
