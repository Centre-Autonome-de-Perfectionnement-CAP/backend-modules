<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Cours\Models\Program;
use App\Modules\Cours\Models\CourseElement;
use App\Modules\RH\Models\Professor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SelectController — données de référence pour les selects
 * Toutes les routes : /api/emploi-temps/selects/*
 */
class SelectController extends Controller
{
    // GET /api/emploi-temps/selects/academic-years
    public function academicYears(): JsonResponse
    {
        $data = AcademicYear::orderByDesc('year_start')
            ->get(['id', 'academic_year', 'libelle', 'year_start', 'year_end', 'is_current']);
        return response()->json(['success' => true, 'data' => $data]);
    }

    // GET /api/emploi-temps/selects/departments
    public function departments(): JsonResponse
    {
        $data = Department::orderBy('name')
            ->get(['id', 'name', 'abbreviation', 'cycle_id', 'is_active']);
        return response()->json(['success' => true, 'data' => $data]);
    }

    // GET /api/emploi-temps/selects/class-groups?academic_year_id=1&department_id=2
    public function classGroups(Request $request): JsonResponse
    {
        $query = ClassGroup::query();

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $data = $query->orderBy('group_name')
            ->get(['id', 'group_name', 'study_level', 'academic_year_id', 'department_id']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // GET /api/emploi-temps/selects/professors
    public function professors(Request $request): JsonResponse
    {
        $query = Professor::query();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name',  'like', "%{$s}%")
            );
        }
        $data = $query->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
        return response()->json(['success' => true, 'data' => $data]);
    }

    // GET /api/emploi-temps/selects/course-elements
    public function courseElements(Request $request): JsonResponse
    {
        $query = CourseElement::query();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")
            );
        }
        $data = $query->orderBy('name')->get(['id', 'name', 'code', 'credits', 'teaching_unit_id']);
        return response()->json(['success' => true, 'data' => $data]);
    }

    // GET /api/emploi-temps/selects/programs?academic_year_id=1&class_group_id=3
    // Retourne les programmes d'une classe donnée (filtrés par année + classe)
    public function programs(Request $request): JsonResponse
    {
        $query = Program::with([
            'courseElementProfessor.courseElement:id,name,code,credits',
            'courseElementProfessor.professor:id,first_name,last_name,email',
            'classGroup:id,group_name,study_level',
        ]);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        // Filtre principal : par classe
        if ($request->filled('class_group_id')) {
            $query->where('class_group_id', $request->class_group_id);
        }
      
        $programs = $query->orderBy('id')->get();

        $result = $programs->map(function ($p) {
            $cep = $p->courseElementProfessor;
            return [
                'id'       => $p->id,
                'semester' => $p->semester,
                'course_element' => $cep?->courseElement ? [
                    'id'      => $cep->courseElement->id,
                    'name'    => $cep->courseElement->name,
                    'code'    => $cep->courseElement->code,
                    'credits' => $cep->courseElement->credits,
                ] : null,
                'professor' => $cep?->professor ? [
                    'id'         => $cep->professor->id,
                    'first_name' => $cep->professor->first_name,
                    'last_name'  => $cep->professor->last_name,
                    'email'      => $cep->professor->email,
                ] : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $result]);
    }

    // GET /api/emploi-temps/selects/rooms-by-building/{buildingId}
    public function roomsByBuilding(Request $request, int $buildingId): JsonResponse
    {
        $rooms = \App\Modules\EmploiDuTemps\Models\Room::where('building_id', $buildingId)
            ->when($request->boolean('available_only'), fn($q) => $q->where('is_available', true))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'capacity', 'room_type', 'is_available', 'building_id']);
        return response()->json(['success' => true, 'data' => $rooms]);
    }
}