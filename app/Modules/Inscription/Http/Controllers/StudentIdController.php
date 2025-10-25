<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Modules\Inscription\Models\PersonalInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *   name="Students",
 *   description="Recherche et assignation du matricule étudiant"
 * )
 */
class StudentIdController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/students/lookup-id",
     *   tags={"Students"},
     *   summary="Récupérer le matricule par identité",
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"last_name","first_names","birth_date","birth_place"},
     *       @OA\Property(property="last_name", type="string"),
     *       @OA\Property(property="first_names", type="string"),
     *       @OA\Property(property="birth_date", type="string", format="date"),
     *       @OA\Property(property="birth_place", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(@OA\Property(property="student_id_number", type="string"))),
     *   @OA\Response(response=404, description="Introuvable")
     * )
     */
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'last_name' => ['required','string'],
            'first_names' => ['required','string'],
            'birth_date' => ['required','date'],
            'birth_place' => ['required','string'],
        ]);

        $pi = PersonalInformation::query()
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($data['last_name'])])
            ->whereRaw('LOWER(first_names) = ?', [mb_strtolower($data['first_names'])])
            ->whereDate('birth_date', $data['birth_date'])
            ->whereRaw('LOWER(birth_place) = ?', [mb_strtolower($data['birth_place'])])
            ->first();

        if (!$pi) {
            return response()->json(['message' => 'Identité introuvable'], 404);
        }

        // Le matricule est égal au numéro de téléphone enregistré pour l'étudiant
        $phone = is_array($pi->contacts ?? null) ? ($pi->contacts[0] ?? null) : ($pi->phone ?? null);
        if (!$phone) {
            return response()->json(['message' => 'Aucun numéro de téléphone associé à cette identité'], 404);
        }

        $student = Student::where('student_id_number', $phone)->first();
        if (!$student) {
            return response()->json(['message' => 'Matricule non défini pour cette identité'], 404);
        }

        return response()->json(['student_id_number' => $student->student_id_number]);
    }

    /**
     * @OA\Post(
     *   path="/api/students/assign-id",
     *   tags={"Students"},
     *   summary="Assigner un matricule (numéro de téléphone)",
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"last_name","first_names","birth_date","birth_place","phone"},
     *       @OA\Property(property="last_name", type="string"),
     *       @OA\Property(property="first_names", type="string"),
     *       @OA\Property(property="birth_date", type="string", format="date"),
     *       @OA\Property(property="birth_place", type="string"),
     *       @OA\Property(property="phone", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Créé", @OA\JsonContent(@OA\Property(property="student_id_number", type="string"))),
     *   @OA\Response(response=404, description="Identité introuvable"),
     *   @OA\Response(response=409, description="Matricule déjà défini")
     * )
     */
    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'last_name' => ['required','string'],
            'first_names' => ['required','string'],
            'birth_date' => ['required','date'],
            'birth_place' => ['required','string'],
            'phone' => ['required','string']
        ]);

        $pi = PersonalInformation::query()
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($data['last_name'])])
            ->whereRaw('LOWER(first_names) = ?', [mb_strtolower($data['first_names'])])
            ->whereDate('birth_date', $data['birth_date'])
            ->whereRaw('LOWER(birth_place) = ?', [mb_strtolower($data['birth_place'])])
            ->first();

        if (!$pi) {
            return response()->json(['message' => 'Identité introuvable'], 404);
        }

        // Empêcher réutilisation d'un matricule existant
        $already = Student::where('student_id_number', $data['phone'])->exists();
        if ($already) {
            return response()->json(['message' => 'Matricule déjà existant'], 409);
        }

        $student = Student::create([
            'student_id_number' => $data['phone'],
            'password' => Hash::make($data['phone']),
        ]);

        return response()->json(['student_id_number' => $student->student_id_number], 201);
    }
}
