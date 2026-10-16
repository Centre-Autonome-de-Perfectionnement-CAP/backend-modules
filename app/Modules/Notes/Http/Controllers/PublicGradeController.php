<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Notes\Models\LmdSystemGrade;
use App\Modules\Notes\Models\OldSystemGrade;
use App\Modules\Cours\Models\Program;
use App\Modules\Finance\Services\FinancialCalculationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicGradeController extends Controller
{
    use ApiResponse;

    private FinancialCalculationService $financialService;

    public function __construct(FinancialCalculationService $financialService)
    {
        $this->financialService = $financialService;
    }

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
                // Vérifier dans les anciens étudiants (< 2023)
                $legacy = \App\Modules\LegacyStudent\Models\LegacyStudent::with(['departments', 'academicRecords'])
                    ->where('matricule', strtoupper(trim($request->student_id_number)))
                    ->first();

                if ($legacy) {
                    $academicYears = [];
                    if ($legacy->academicRecords && $legacy->academicRecords->isNotEmpty()) {
                        $academicYears = $legacy->academicRecords->map(function ($record) {
                            return [
                                'id' => $record->id,
                                'label' => $record->academic_year . ($record->level ? " ({$record->level})" : ''),
                                'level' => $record->level ?? 'Ancien Étudiant',
                                'is_legacy' => true,
                            ];
                        })->values()->toArray();
                    } else {
                        $yearLabel = "{$legacy->enrollment_year}-" . ($legacy->enrollment_year + 1);
                        $academicYears = [
                            [
                                'id' => 0,
                                'label' => $yearLabel . ($legacy->cycle ? " ({$legacy->cycle})" : ''),
                                'level' => $legacy->cycle ?? 'Ancien Étudiant',
                                'is_legacy' => true,
                            ]
                        ];
                    }

                    return $this->successResponse([
                        'student' => [
                            'id' => $legacy->id,
                            'student_id_number' => $legacy->matricule,
                            'last_name' => $legacy->last_name,
                            'first_names' => $legacy->first_name,
                            'birth_date' => $legacy->date_of_birth,
                            'is_legacy' => true,
                        ],
                        'academic_years' => $academicYears,
                    ], 'Authentification réussie (Ancien Étudiant)');
                }

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
            'student_id' => 'required|integer',
            'academic_year_id' => 'required',
        ], [
            'student_id.required' => 'L\'identifiant de l\'étudiant est requis',
            'academic_year_id.required' => 'L\'année académique est requise',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $studentId = $request->student_id;
            $academicYearId = $request->academic_year_id;

            // 1. Vérifier s'il s'agit d'un ancien étudiant (< 2023)
            $legacy = \App\Modules\LegacyStudent\Models\LegacyStudent::with('academicRecords')->find($studentId);
            if ($legacy) {
                $record = $legacy->academicRecords()->where('id', $academicYearId)->first()
                    ?? $legacy->academicRecords()->first();

                if (!$record) {
                    return $this->errorResponse(
                        "Votre dossier ancien étudiant ({$legacy->matricule}) est bien enregistré. " .
                        "Toutefois, vos relevés de notes et résultats d'archives n'ont pas encore été complétés par le secrétariat de scolarité. " .
                        "Veuillez vous rapprocher de la scolarité du CAP ou formuler une demande de bulletin.",
                        404
                    );
                }

                $courses = is_array($record->courses) ? $record->courses : json_decode($record->courses ?? '[]', true) ?? [];
                $results = [];
                $totalCredits = $record->total_credits ?? 0;
                $obtainedCredits = $record->obtained_credits ?? 0;
                $totalCoefficient = 0;
                $weightedSum = 0;

                foreach ($courses as $c) {
                    $grade = (float) ($c['grade'] ?? $c['average'] ?? 0);
                    $coeff = (int) ($c['coefficient'] ?? 1);
                    $cred = (int) ($c['credits'] ?? 0);
                    $isValidated = $grade >= 10;

                    $totalCoefficient += $coeff;
                    $weightedSum += ($grade * $coeff);

                    $results[] = [
                        'course_name' => $c['name'] ?? $c['course_name'] ?? 'Matière',
                        'course_code' => $c['code'] ?? $c['course_code'] ?? null,
                        'professor' => $c['professor'] ?? $c['professor_name'] ?? 'Équipe pédagogique',
                        'credits' => $cred,
                        'coefficient' => $coeff,
                        'semester' => $c['semester'] ?? $record->semester ?? null,
                        'average' => round($grade, 2),
                        'retake_average' => isset($c['retake_grade']) ? round((float) $c['retake_grade'], 2) : null,
                        'final_average' => round($grade, 2),
                        'validated' => $isValidated,
                        'must_retake' => !$isValidated,
                    ];
                }

                $calculatedAverage = $record->general_average !== null
                    ? (float) $record->general_average
                    : ($totalCoefficient > 0 ? round($weightedSum / $totalCoefficient, 2) : 0);

                return $this->successResponse([
                    'academic_info' => [
                        'academic_year' => $record->academic_year,
                        'level' => $record->level ?? $legacy->cycle ?? 'Ancien Cursus',
                    ],
                    'results' => $results,
                    'summary' => [
                        'total_credits' => $totalCredits ?: 60,
                        'obtained_credits' => $obtainedCredits ?: 60,
                        'general_average' => $calculatedAverage,
                        'year_decision' => $record->decision ?? 'pass',
                        'mention' => $record->mention,
                        'thesis_title' => $record->thesis_title,
                        'thesis_grade' => $record->thesis_grade,
                        'thesis_date' => $record->thesis_date?->format('d/m/Y'),
                        'quitus_accorded' => (bool) $record->quitus_accorded,
                    ],
                ], 'Résultats récupérés avec succès');
            }

            // 2. Flux normal pour les étudiants modernes
            // Récupérer le parcours académique
            $academicPath = AcademicPath::with(['academicYear'])
                ->where('student_pending_student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->first();

            if (!$academicPath) {
                return $this->notFoundResponse('Aucun parcours académique trouvé pour cette année');
            }

            // Vérifier si l'étudiant a soldé sa scolarité
            $financialStatus = $this->financialService->calculateBalance($studentId, $academicYearId);
            
            if ($financialStatus['balance'] > 0) {
                return $this->errorResponse(
                    'Vous devez être en règle avec la scolarité pour consulter vos résultats. ' . 
                    'Solde restant : ' . number_format($financialStatus['balance'], 0, ',', ' ') . ' FCFA. ' .
                    'Veuillez attendre la validation de votre quittance par le service financier.',
                    403
                );
            }

            // Récupérer les notes LMD
            $lmdGrades = LmdSystemGrade::where('student_pending_student_id', $studentId)
                ->with(['program.courseElementProfessor.courseElement', 'program.courseElementProfessor.professor'])
                ->get();

            // Récupérer les notes ancien système
            $oldGrades = OldSystemGrade::where('student_pending_student_id', $studentId)
                ->with(['program.courseElementProfessor.courseElement', 'program.courseElementProfessor.professor'])
                ->get();

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
