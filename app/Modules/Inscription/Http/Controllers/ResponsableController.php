<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Inscription\Models\StudentGroup;
use App\Modules\Inscription\Models\PendingStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class ResponsableController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    // Dashboard : retourne la 1ère classe + ses étudiants
    // ────────────────────────────────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user instanceof PersonalInformation || $user->role_id != 9) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            $classGroup = $this->getUserClasses($user->id)
                ->with(['academicYear', 'department'])
                ->first();

            if (!$classGroup) {
                return response()->json(['class_info' => null, 'students' => []]);
            }

            $students = $this->getClassStudents($classGroup->id);

            return response()->json([
                'class_info' => [
                    'filiere'        => optional($classGroup->department)->name,
                    'niveau'         => $classGroup->study_level,
                    'annee'          => optional($classGroup->academicYear)->academic_year
                                        ?? optional($classGroup->academicYear)->name
                                        ?? 'N/A',
                    'groupe'         => $classGroup->group_name,
                    'totalEtudiants' => count($students),
                ],
                'students' => $students,
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Erreur serveur', 'message' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Toutes les classes du responsable groupées par année
    // ────────────────────────────────────────────────────────────────────────
    public function getClasses(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user instanceof PersonalInformation || $user->role_id != 9) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            $classes = $this->getUserClasses($user->id)
                ->with(['academicYear', 'department.cycle'])
                ->orderByDesc('academic_year_id')
                ->orderBy('study_level')
                ->orderBy('group_name')
                ->get();

            if ($classes->isEmpty()) {
                return response()->json(['classes_by_year' => []]);
            }

            $classesByYear = [];

            foreach ($classes as $class) {
                $yearId   = $class->academic_year_id;
                $yearName = optional($class->academicYear)->academic_year
                            ?? optional($class->academicYear)->name
                            ?? 'Année inconnue';

                if (!isset($classesByYear[$yearId])) {
                    $classesByYear[$yearId] = [
                        'academic_year_id'   => $yearId,
                        'academic_year_name' => $yearName,
                        'classes'            => [],
                    ];
                }

                $studentCount = StudentGroup::where('class_group_id', $class->id)->count();

                $classesByYear[$yearId]['classes'][] = [
                    'id'                 => $class->id,
                    'group_name'         => $class->group_name,
                    'study_level'        => $class->study_level,
                    'filiere'            => optional($class->department)->name,
                    'cycle'              => optional(optional($class->department)->cycle)->name,
                    'total_etudiants'    => $studentCount,
                    'validation_average' => $class->validation_average ?? null,
                ];
            }

            return response()->json(['classes_by_year' => array_values($classesByYear)]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Erreur serveur', 'message' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Étudiants d'une classe (endpoint dédié pour le frontend)
    // ────────────────────────────────────────────────────────────────────────
    public function getStudentsByClass(Request $request, int $classGroupId)
    {
        try {
            $user = Auth::user();
            if (!$user instanceof PersonalInformation || $user->role_id != 9) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            // Vérifier que la classe appartient au responsable
            $authorizedClassIds = $this->getUserClasses($user->id)->pluck('id');
            if (!$authorizedClassIds->contains($classGroupId)) {
                return response()->json(['error' => 'Accès interdit à cette classe'], 403);
            }

            $students = $this->getClassStudents($classGroupId);

            return response()->json(['students' => $students]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Erreur serveur', 'message' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers privés
    // ────────────────────────────────────────────────────────────────────────

    private function getUserClasses(int $personalInfoId)
    {
        $pendingStudentIds = PendingStudent::where('personal_information_id', $personalInfoId)
            ->pluck('id');

        if ($pendingStudentIds->isEmpty()) {
            return ClassGroup::whereRaw('1 = 0');
        }

        $studentIds = DB::table('student_pending_student')
            ->whereIn('pending_student_id', $pendingStudentIds)
            ->pluck('student_id')
            ->unique();

        if ($studentIds->isEmpty()) {
            return ClassGroup::whereRaw('1 = 0');
        }

        $classGroupIds = StudentGroup::whereIn('student_id', $studentIds)
            ->pluck('class_group_id')
            ->unique();

        if ($classGroupIds->isEmpty()) {
            return ClassGroup::whereRaw('1 = 0');
        }

        return ClassGroup::whereIn('id', $classGroupIds);
    }

    private function getClassStudents(int $classGroupId): array
    {
        $studentIds = StudentGroup::where('class_group_id', $classGroupId)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) return [];

        $pendingStudentIds = DB::table('student_pending_student')
            ->whereIn('student_id', $studentIds)
            ->pluck('pending_student_id')
            ->unique();

        if ($pendingStudentIds->isEmpty()) return [];

        $pendingStudents = PendingStudent::whereIn('id', $pendingStudentIds)
            ->with('personalInformation')
            ->get();

        return $pendingStudents->map(function ($ps) {
            $info = $ps->personalInformation;
            return [
                'id'         => $ps->id,
                'matricule'  => $ps->matricule ?? null,
                'nomPrenoms' => trim(($info->first_names ?? '') . ' ' . ($info->last_name ?? '')),
                'email'      => $info->email ?? null,
                'sexe'       => $info->gender ?? null,  
                'redoublant' => $ps->is_repeating ? 'Oui' : 'Non',
                'telephone'  => $this->extractPhone($info->contacts ?? null),
            ];
        })->values()->toArray();
    }

    private function extractPhone($contacts): ?string
    {
        if (is_string($contacts)) $contacts = json_decode($contacts, true);
        if (!is_array($contacts)) return null;
        return $contacts['phone'] ?? $contacts['telephone'] ?? null;
    }
}