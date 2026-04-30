<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmploiDuTemps\Models\EmploiDuTemps;
use App\Modules\EmploiDuTemps\Http\Resources\EmploiDuTempsResource;
use App\Modules\Cours\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class EmploiDuTempsController extends Controller
{
    /**
     * Relations à eager-loader systématiquement
     */
    private array $with = [
        'academicYear:id,academic_year,libelle,is_current',
        'department:id,name,abbreviation',
        'classGroup:id,group_name,study_level',
        'room.building:id,name,code',
        'program.courseElementProfessor.courseElement:id,name,code,credits',
        'program.courseElementProfessor.professor:id,first_name,last_name,email',
    ];

    // ──────────────────────────────────────────────────────────
    // LIST  GET /api/emploi-temps/emploi-du-temps
    // ──────────────────────────────────────────────────────────

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EmploiDuTemps::with($this->with);

        // ── Filtres disponibles ──────────────────────────────
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('class_group_id')) {
            $query->where('class_group_id', $request->class_group_id);
        }
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }
        if ($request->filled('building_id')) {
            $query->whereHas('room', fn($q) => $q->where('building_id', $request->building_id));
        }
        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }
        if ($request->filled('start_time')) {
            $query->where('start_time', '>=', $request->start_time);
        }
        if ($request->filled('end_time')) {
            $query->where('end_time', '<=', $request->end_time);
        }
        if ($request->filled('professor_id')) {
            $query->whereHas('program.courseElementProfessor', fn($q) =>
                $q->where('professor_id', $request->professor_id)
            );
        }
        if ($request->filled('course_element_id')) {
            $query->whereHas('program.courseElementProfessor', fn($q) =>
                $q->where('course_element_id', $request->course_element_id)
            );
        }
        if ($request->filled('is_cancelled')) {
            $query->where('is_cancelled', filter_var($request->is_cancelled, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('is_recurring')) {
            $query->where('is_recurring', filter_var($request->is_recurring, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('program.courseElementProfessor.courseElement', fn($sq) =>
                    $sq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")
                )->orWhereHas('program.courseElementProfessor.professor', fn($sq) =>
                    $sq->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")
                )->orWhereHas('room', fn($sq) =>
                    $sq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")
                )->orWhereHas('classGroup', fn($sq) =>
                    $sq->where('group_name', 'like', "%{$s}%")
                );
            });
        }

        $perPage = (int) $request->get('per_page', 20);

        return EmploiDuTempsResource::collection(
            $query->orderBy('day_of_week')->orderBy('start_time')->paginate($perPage)
        );
    }

    // ──────────────────────────────────────────────────────────
    // STORE  POST /api/emploi-temps/emploi-du-temps
    // ──────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id'    => 'required|exists:academic_years,id',
            'department_id'       => 'required|exists:departments,id',
            'class_group_id'      => 'required|exists:class_groups,id',
            'program_id'          => 'required|exists:programs,id',
            'room_id'             => 'required|exists:rooms,id',
            'day_of_week'         => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time'          => 'required|date_format:H:i',
            'end_time'            => 'required|date_format:H:i|after:start_time',
            'is_recurring'        => 'boolean',
            'recurrence_end_date' => 'nullable|date',
            'excluded_dates'      => 'nullable|array',
            'excluded_dates.*'    => 'date',
            'notes'               => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Détection des conflits
        $conflicts = $this->detectConflicts($request->all());
        if (!empty($conflicts)) {
            return response()->json([
                'message'   => 'Des conflits ont été détectés.',
                'conflicts' => $conflicts,
            ], 409);
        }

        $emploi = EmploiDuTemps::create($validator->validated());

        return response()->json([
            'message' => 'Emploi du temps créé avec succès.',
            'data'    => new EmploiDuTempsResource($emploi->load($this->with)),
        ], 201);
    }

    // ──────────────────────────────────────────────────────────
    // SHOW  GET /api/emploi-temps/emploi-du-temps/{id}
    // ──────────────────────────────────────────────────────────

    public function show(EmploiDuTemps $emploiDuTemps): JsonResponse
    {
        return response()->json(
            new EmploiDuTempsResource($emploiDuTemps->load($this->with))
        );
    }

    public function update(Request $request, $id): JsonResponse{
        // 🔍 Récupération manuelle par ID
        $emploiDuTemps = EmploiDuTemps::find($id);

        if (!$emploiDuTemps) {
            return response()->json([
                'message' => 'Emploi du temps non trouvé.'
            ], 404);
        }

        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'academic_year_id'    => 'sometimes|exists:academic_years,id',
            'department_id'       => 'sometimes|exists:departments,id',
            'class_group_id'      => 'sometimes|exists:class_groups,id',
            'program_id'          => 'sometimes|exists:programs,id',
            'room_id'             => 'sometimes|exists:rooms,id',
            'day_of_week'         => 'sometimes|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time'          => 'sometimes|date_format:H:i',
            'end_time'            => 'sometimes|date_format:H:i|after:start_time',
            'is_recurring'        => 'boolean',
            'recurrence_end_date' => 'nullable|date',
            'excluded_dates'      => 'nullable|array',
            'excluded_dates.*'    => 'date',
            'notes'               => 'nullable|string|max:1000',
            'is_active'           => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ⚠️ Fusion ancienne + nouvelle data pour check conflits
        $data = array_merge($emploiDuTemps->toArray(), $request->all());

        $conflicts = $this->detectConflicts($data, $emploiDuTemps->id);

        if (!empty($conflicts)) {
            return response()->json([
                'message'   => 'Des conflits ont été détectés.',
                'conflicts' => $conflicts,
            ], 409);
        }

        // ✅ Update
        $emploiDuTemps->update($validator->validated());

        return response()->json([
            'message' => 'Emploi du temps modifié avec succès.',
            'data'    => new EmploiDuTempsResource(
                $emploiDuTemps->fresh($this->with)
            ),
        ]);
    }
    

    public function destroy($id): JsonResponse {
        try {
            $emploiDuTemps = EmploiDuTemps::find($id);

            if (!$emploiDuTemps) {
                return response()->json([
                    'message' => 'Emploi du temps non trouvé.'
                ], 404);
            }

            $emploiDuTemps->delete();

            return response()->json([
                'message' => 'Emploi du temps supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur suppression emploi du temps', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Une erreur est survenue lors de la suppression.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function cancel(EmploiDuTemps $emploiDuTemps): JsonResponse {
        $emploiDuTemps->update(['is_cancelled' => true]);
        return response()->json(['message' => 'Emploi du temps annulé.']);
    }

    // ──────────────────────────────────────────────────────────
    // CHECK CONFLICTS
    // ──────────────────────────────────────────────────────────

    public function checkConflicts(Request $request): JsonResponse
    {
        $conflicts = $this->detectConflicts($request->all(), $request->exclude_id);
        return response()->json([
            'has_conflicts' => !empty($conflicts),
            'conflicts'     => $conflicts,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // OCCURRENCES
    // ──────────────────────────────────────────────────────────

    public function occurrences(EmploiDuTemps $emploiDuTemps): JsonResponse
    {
        $occurrences = $emploiDuTemps->getOccurrences();
        return response()->json([
            'data'  => array_map(fn($d) => $d->format('Y-m-d'), $occurrences),
            'count' => count($occurrences),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // STATS
    // ──────────────────────────────────────────────────────────

    public function stats(Request $request): JsonResponse
    {
        $query = EmploiDuTemps::query();

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $total     = $query->count();
        $active    = (clone $query)->active()->count();
        $cancelled = (clone $query)->where('is_cancelled', true)->count();
        $recurring = (clone $query)->where('is_recurring', true)->count();
        $byDay     = (clone $query)->groupBy('day_of_week')
                        ->selectRaw('day_of_week, count(*) as total')
                        ->pluck('total', 'day_of_week');

        return response()->json(compact('total', 'active', 'cancelled', 'recurring', 'byDay'));
    }

    // ──────────────────────────────────────────────────────────
    // PRIVATE — Détection des conflits
    // ──────────────────────────────────────────────────────────

    private function detectConflicts(array $data, ?int $excludeId = null): array
    {
        $conflicts = [];

        $roomId       = $data['room_id']       ?? null;
        $day          = $data['day_of_week']   ?? null;
        $startTime    = $data['start_time']    ?? null;
        $endTime      = $data['end_time']      ?? null;
        $programId    = $data['program_id']    ?? null;
        $classGroupId = $data['class_group_id'] ?? null;

        if (!$roomId || !$day || !$startTime || !$endTime) {
            return $conflicts;
        }

        // ── Conflit de salle ───────────────────────────────────
        $roomConflict = EmploiDuTemps::where('room_id', $roomId)
            ->where('day_of_week', $day)
            ->where('is_cancelled', false)
            ->where('is_active', true)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        if ($roomConflict) {
            $conflicts[] = ['type' => 'room', 'message' => 'La salle est déjà occupée sur ce créneau.'];
        }

        // ── Conflit groupe de classe ───────────────────────────
        if ($classGroupId) {
            $groupConflict = EmploiDuTemps::where('class_group_id', $classGroupId)
                ->where('day_of_week', $day)
                ->where('is_cancelled', false)
                ->where('is_active', true)
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->exists();

            if ($groupConflict) {
                $conflicts[] = ['type' => 'class_group', 'message' => 'Ce groupe de classe a déjà un cours sur ce créneau.'];
            }
        }

        // ── Conflit professeur ────────────────────────────────
        if ($programId) {
            $program = Program::with('courseElementProfessor')->find($programId);

            if ($program && $program->courseElementProfessor?->professor_id) {
                $profConflict = EmploiDuTemps::where('day_of_week', $day)
                    ->where('is_cancelled', false)
                    ->where('is_active', true)
                    ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->whereHas('program.courseElementProfessor', fn($q) =>
                        $q->where('professor_id', $program->courseElementProfessor->professor_id)
                    )->exists();

                if ($profConflict) {
                    $conflicts[] = ['type' => 'professor', 'message' => 'Ce professeur a déjà un cours sur ce créneau.'];
                }
            }
        }

        return $conflicts;
    }
}