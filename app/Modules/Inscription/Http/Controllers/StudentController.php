<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\StudentService;
use App\Modules\Inscription\Models\PersonalInformation;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\StudentGroup;

class StudentController extends Controller{
    use ApiResponse, HasPagination;

    public function __construct(
        protected StudentService $studentService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['year', 'filiere', 'entry_diploma', 'niveau', 'cohort', 'redoublant', 'search']);
        $perPage = $this->getPerPage($request);

        $students = $this->studentService->getAll($filters, $perPage);

        return $this->successResponse($students, 'Étudiants récupérés avec succès');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $student = $this->studentService->getById($id);

        if (!$student) {
            return $this->errorResponse('Étudiant non trouvé', 404);
        }

        return $this->successResponse($student, 'Détails de l\'étudiant récupérés');
    }

    public function exportFichePresence(Request $request)
    {
<<<<<<< HEAD
        $request->validate([
            'cohort' => 'required',
        ], [
            'cohort.required' => 'La sélection de la cohorte est obligatoire',
        ]);

=======
>>>>>>> be0384f0d56cb4491eb015c3bc1466c68a041a8f
        $filters = $request->only(['year', 'filiere', 'niveau', 'cohort', 'groupe']);
        return $this->studentService->exportFichePresence($filters);
    }

    public function exportFicheEmargement(Request $request)
    {
<<<<<<< HEAD
        $request->validate([
            'cohort' => 'required',
        ], [
            'cohort.required' => 'La sélection de la cohorte est obligatoire',
        ]);

=======
>>>>>>> be0384f0d56cb4491eb015c3bc1466c68a041a8f
        $filters = $request->only(['year', 'filiere', 'niveau', 'cohort', 'groupe']);
        return $this->studentService->exportFicheEmargement($filters);
    }

    public function update(Request $request, int $id): JsonResponse {
        $validated = $request->validate([
            'first_name'    => 'sometimes|required|string|max:255',
            'last_name'     => 'sometimes|required|string|max:255',
            'email'         => 'sometimes|required|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'gender'        => 'sometimes|required|in:M,F,Masculin,Féminin',
            'date_of_birth' => 'nullable|date',
        ]);

        $updated = $this->studentService->update($id, $validated);

        if (!$updated) {
            return $this->errorResponse('Étudiant non trouvé ou mise à jour échouée', 404);
        }

        return $this->successResponse($updated, 'Étudiant mis à jour avec succès');
    }

    public function assignClassResponsible($id): JsonResponse {
        try {

            DB::beginTransaction();

            $personalInfo = PersonalInformation::findOrFail($id);

            //
            if ($personalInfo->role_id == 9) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cet étudiant est déjà responsable de classe'
                ], 400);
            }

            // Récupérer le dossier approuvé
            $pendingStudent = PendingStudent::where('personal_information_id', $id)
                ->where('status', 'approved')
                ->first();

            if (!$pendingStudent) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun dossier étudiant approuvé trouvé'
                ], 400);
            }

            // Récupérer l'étudiant via la table pivot
            $student = $pendingStudent->students()->first();

            if (!$student) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun compte étudiant associé'
                ], 400);
            }

            // Vérifier appartenance à un groupe
            $studentGroup = StudentGroup::where('student_id', $student->id)
                ->with('classGroup')
                ->first();

            if (!$studentGroup) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cet étudiant n\'appartient à aucun groupe'
                ], 400);
            }

            $classGroup = $studentGroup->classGroup;

            // 6️Vérifier le nombre de responsables dans ce groupe
            $responsablesCount = StudentGroup::where('class_group_id', $classGroup->id)
                ->whereHas('student.pendingStudents.personalInformation', function ($query) {
                    $query->where('role_id', 9);
                })
                ->count();

            if ($responsablesCount >= 2) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Ce groupe possède déjà 2 responsables'
                ], 400);
            }

            $matricule = $student->student_id_number ?? 'etudiant';
            $defaultPassword = 'password';

            // 8️⃣ Mise à jour du rôle
            $personalInfo->role_id = 9;
            $personalInfo->password = Hash::make($defaultPassword);
            $personalInfo->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Responsable de classe ajouté avec succès',
                'data' => [
                    'student_id' => $student->id,
                    'class_group_id' => $classGroup->id,
                    'role_id' => $personalInfo->role_id
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Informations personnelles introuvables'
            ], 404);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function removeClassResponsible($id): JsonResponse{
        try {

            $personalInfo = PersonalInformation::findOrFail($id);

            if ($personalInfo->role_id != 9) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet étudiant n\'est pas responsable de classe'
                ], 400);
            }

            $personalInfo->role_id = 3;
            $personalInfo->password = null;
            $personalInfo->save();

            return response()->json([
                'success' => true,
                'message' => 'Responsable retiré avec succès'
            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Étudiant introuvable'
            ], 404);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }

}
