<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Inscription\Models\StudentGroup;
use App\Modules\Inscription\Models\PendingStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class ResponsableController extends Controller
{
    public function dashboard(Request $request) {
        try {
            $user = Auth::user();

            if (!$user instanceof PersonalInformation || $user->role_id != 9) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            $classGroup = $this->getUserClasses($user->id)->first();

            if (!$classGroup) {
                return response()->json([
                    'class_info' => null,
                    'students' => []
                ]);
            }

            $students = $this->getClassStudents($classGroup->id);

            return response()->json([
                'class_info' => [
                    'filiere' => optional($classGroup->department)->name,
                    'niveau' => $classGroup->study_level,
                    'annee' => optional($classGroup->academicYear)->name,
                    'groupe' => $classGroup->group_name,
                    'totalEtudiants' => count($students)
                ],
                'students' => $students
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getClasses(Request $request) {
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

            $classesByYear = [];

            foreach ($classes as $class) {
                $yearId = $class->academic_year_id;
                $yearName = optional($class->academicYear)->academic_year ?? 'Année inconnue';

                if (!isset($classesByYear[$yearId])) {
                    $classesByYear[$yearId] = [
                        'academic_year_id' => $yearId,
                        'academic_year_name' => $yearName,
                        'classes' => []
                    ];
                }

                $studentCount = StudentGroup::where('class_group_id', $class->id)->count();

                $classesByYear[$yearId]['classes'][] = [
                    'id' => $class->id,
                    'group_name' => $class->group_name,
                    'study_level' => $class->study_level,
                    'filiere' => optional($class->department)->name,
                    'cycle' => optional($class->department->cycle)->name,
                    'total_etudiants' => $studentCount,
                    'validation_average' => $class->validation_average,
                ];
            }

            return response()->json([
                'classes_by_year' => array_values($classesByYear)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request, string $type) {
        try {
            $user = Auth::user();

            if (!$user instanceof PersonalInformation || $user->role_id != 9) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            $classGroup = $this->getUserClasses($user->id)->first();

            if (!$classGroup) {
                return response()->json(['error' => 'Aucune classe trouvée'], 404);
            }

            $students = $this->getClassStudents($classGroup->id);

            $data = [
                'class' => $classGroup,
                'students' => $students,
                'date' => now()->format('d/m/Y'),
                'responsable' => $user
            ];

            if ($type === 'fiche-presence') {
                $pdf = Pdf::loadView('inscription::exports.fiche-presence', $data);
            } elseif ($type === 'fiche-emargement') {
                $pdf = Pdf::loadView('inscription::exports.fiche-emargement', $data);
            } else {
                return response()->json(['error' => 'Type d\'export invalide'], 400);
            }

            return $pdf->download("{$type}-{$classGroup->group_name}.pdf");

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Erreur export',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getUserClasses(int $personalInfoId)
    {
        return ClassGroup::whereHas('studentGroups', function ($query) use ($personalInfoId) {
            $query->whereHas('student.pendingStudents', function ($q) use ($personalInfoId) {
                $q->where('personal_information_id', $personalInfoId);
            });
        });
    }


    private function getClassStudents(int $classGroupId){
        // 1️⃣ récupérer les student_id depuis student_groups
        $studentIds = StudentGroup::where('class_group_id', $classGroupId)
            ->pluck('student_id');

        // 2️⃣ récupérer les pending_students via student_id
        $pendingStudents = PendingStudent::whereIn('student_id', $studentIds)
            ->with('personalInformation')
            ->get();

        // 3️⃣ formatter les données (inchangé)
        return $pendingStudents->map(function ($student) {
            return [
                'id' => $student->id,
                'matricule' => $student->matricule,
                'nomPrenoms' => trim(
                    ($student->personalInformation->first_names ?? '') . ' ' .
                    ($student->personalInformation->last_name ?? '')
                ),
                'email' => $student->personalInformation->email ?? null,
                'sexe' => $student->personalInformation->gender ?? null,
                'redoublant' => $student->is_repeating ? 'Oui' : 'Non',
                'telephone' => $this->extractPhone($student->personalInformation->contacts ?? null)
            ];
        });
    }

    private function extractPhone($contacts)
    {
        if (is_string($contacts)) {
            $contacts = json_decode($contacts, true);
        }

        if (!is_array($contacts)) {
            return null;
        }

        return $contacts['phone'] ?? $contacts['telephone'] ?? null;
    }
}