<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Notes\Models\LmdSystemGrade;
use App\Modules\Notes\Models\OldSystemGrade;
use App\Modules\Cours\Models\Program;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicGradeController extends Controller
{
    use ApiResponse;

    /**
     * Authentifie un étudiant et retourne ses informations de base
     */
    public function authenticate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id_number' => 'required|string',
        ], [
            'student_id_number.required' => 'Le matricule est requis',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Rechercher l'étudiant par matricule via la table students
            $student = StudentPendingStudent::with([
                'pendingStudent.personalInformation',
                'academicPaths.academicYear',
                'student'
            ])
            ->whereHas('student', function ($query) use ($request) {
                $query->where('student_id_number', $request->student_id_number);
            })
            ->first();

            if (!$student) {
                return $this->notFoundResponse('Matricule introuvable');
            }

            $personalInfo = $student->pendingStudent->personalInformation;
            
            if (!$personalInfo) {
                return $this->errorResponse('Informations personnelles introuvables', 404);
            }

            // Récupérer les parcours académiques
            $academicYears = $student->academicPaths->map(function ($path) {
                return [
                    'id' => $path->academicYear->id,
                    'label' => $path->academicYear->academic_year,
                    'level' => $path->study_level ?? null,
                ];
            })->unique('id')->values();

            return $this->successResponse([
                'student' => [
                    'id' => $student->id,
                    'student_id_number' => $student->student->student_id_number,
                    'last_name' => $personalInfo->last_name,
                    'first_names' => $personalInfo->first_names,
                    'birth_date' => $personalInfo->birth_date,
                ],
                'academic_years' => $academicYears,
            ], 'Authentification réussie');

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de l\'authentification: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Récupère les résultats d'un étudiant pour une année académique
     */
    public function getResults(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:student_pending_student,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
        ], [
            'student_id.required' => 'L\'identifiant de l\'étudiant est requis',
            'student_id.exists' => 'Étudiant introuvable',
            'academic_year_id.required' => 'L\'année académique est requise',
            'academic_year_id.exists' => 'Année académique introuvable',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $studentId = $request->student_id;
            $academicYearId = $request->academic_year_id;

            // Récupérer le parcours académique
            $academicPath = AcademicPath::with(['academicYear'])
                ->where('student_pending_student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->first();

            if (!$academicPath) {
                return $this->notFoundResponse('Aucun parcours académique trouvé pour cette année');
            }

            // Récupérer les notes LMD pour cette année académique
            $lmdGrades = LmdSystemGrade::where('student_pending_student_id', $studentId)
                ->with(['program.courseElementProfessor.courseElement', 'program.courseElementProfessor.professor', 'program'])
                ->get();

            // Filtrer par année académique si le programme a academic_year_id
            $lmdGrades = $lmdGrades->filter(function ($grade) use ($academicYearId) {
                return $grade->program && 
                       ($grade->program->academic_year_id == $academicYearId || !$grade->program->academic_year_id);
            });

            // Récupérer les notes ancien système pour cette année académique
            $oldGrades = OldSystemGrade::where('student_pending_student_id', $studentId)
                ->with(['program.courseElementProfessor.courseElement', 'program.courseElementProfessor.professor', 'program'])
                ->get();

            // Filtrer par année académique si le programme a academic_year_id
            $oldGrades = $oldGrades->filter(function ($grade) use ($academicYearId) {
                return $grade->program && 
                       ($grade->program->academic_year_id == $academicYearId || !$grade->program->academic_year_id);
            });

            $results = [];
            $totalCredits = 0;
            $obtainedCredits = 0;
            $totalCoefficient = 0;
            $weightedSum = 0;

            // Traiter les notes LMD
            foreach ($lmdGrades as $grade) {
                if (!$grade->program || !$grade->program->courseElementProfessor) continue;
                
                $courseElement = $grade->program->courseElementProfessor->courseElement;
                $professor = $grade->program->courseElementProfessor->professor;
                
                $finalAverage = $grade->average;
                if (isset($grade->retake_average) && $grade->retake_average !== null) {
                    $finalAverage = min($grade->retake_average, 12);
                }

                $credits = $courseElement->credits ?? 0;
                $coefficient = $courseElement->coefficient ?? 1;
                $isValidated = $finalAverage >= 12;

                if ($isValidated) {
                    $obtainedCredits += $credits;
                }

                $totalCredits += $credits;
                $totalCoefficient += $coefficient;
                $weightedSum += ($finalAverage * $coefficient);

                $results[] = [
                    'course_name' => $courseElement->name,
                    'course_code' => $courseElement->code ?? null,
                    'professor' => $professor ? ($professor->last_name . ' ' . ($professor->first_names ?? $professor->first_name ?? '')) : 'N/A',
                    'credits' => $credits,
                    'coefficient' => $coefficient,
                    'semester' => $courseElement->semester ?? null,
                    'average' => round($grade->average, 2),
                    'retake_average' => isset($grade->retake_average) ? round($grade->retake_average, 2) : null,
                    'final_average' => round($finalAverage, 2),
                    'validated' => $isValidated,
                    'must_retake' => $grade->must_retake ?? false,
                ];
            }

            // Traiter les notes ancien système
            foreach ($oldGrades as $grade) {
                if (!$grade->program || !$grade->program->courseElementProfessor) continue;
                
                $courseElement = $grade->program->courseElementProfessor->courseElement;
                $professor = $grade->program->courseElementProfessor->professor;
                
                $finalAverage = $grade->average;
                $credits = $courseElement->credits ?? 0;
                $coefficient = $courseElement->coefficient ?? 1;
                $isValidated = $finalAverage >= 12;

                if ($isValidated) {
                    $obtainedCredits += $credits;
                }

                $totalCredits += $credits;
                $totalCoefficient += $coefficient;
                $weightedSum += ($finalAverage * $coefficient);

                $results[] = [
                    'course_name' => $courseElement->name,
                    'course_code' => $courseElement->code ?? null,
                    'professor' => $professor ? ($professor->last_name . ' ' . ($professor->first_names ?? $professor->first_name ?? '')) : 'N/A',
                    'credits' => $credits,
                    'coefficient' => $coefficient,
                    'semester' => $courseElement->semester ?? null,
                    'average' => round($grade->average, 2),
                    'retake_average' => null,
                    'final_average' => round($finalAverage, 2),
                    'validated' => $isValidated,
                    'must_retake' => false,
                ];
            }

            // Calculer la moyenne générale
            $generalAverage = $totalCoefficient > 0 ? round($weightedSum / $totalCoefficient, 2) : 0;

            return $this->successResponse([
                'academic_info' => [
                    'academic_year' => $academicPath->academicYear->academic_year,
                    'level' => $academicPath->study_level,
                ],
                'results' => $results,
                'summary' => [
                    'total_credits' => $totalCredits,
                    'obtained_credits' => $obtainedCredits,
                    'general_average' => $generalAverage,
                    'year_decision' => $academicPath->year_decision,
                ],
            ], 'Résultats récupérés avec succès');

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des résultats: ' . $e->getMessage(), 500);
        }
    }
}
