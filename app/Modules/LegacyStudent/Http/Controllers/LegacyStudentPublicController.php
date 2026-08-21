<?php

namespace App\Modules\LegacyStudent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LegacyStudent\Models\LegacyStudent;
use App\Modules\Inscription\Models\Department;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LegacyStudentPublicController extends Controller
{
    use ApiResponse;

    /**
     * Liste des filières disponibles pour la déclaration
     */
    public function availableFilieres(): JsonResponse
    {
        $filieres = Department::select('id', 'name', 'abbreviation')->get();

        if ($filieres->isEmpty()) {
            $defaultFilieres = [
                ['id' => 1, 'name' => 'Génie Civil', 'abbreviation' => 'GC'],
                ['id' => 2, 'name' => 'Génie Électrique et Informatique', 'abbreviation' => 'GEI'],
                ['id' => 3, 'name' => 'Génie Mécanique et Énergétique', 'abbreviation' => 'GME'],
                ['id' => 4, 'name' => 'Management des Projets', 'abbreviation' => 'MP'],
                ['id' => 5, 'name' => 'Génie Chimique des Procédés', 'abbreviation' => 'GCP'],
                ['id' => 6, 'name' => 'Production et Santé Animales', 'abbreviation' => 'PSA'],
                ['id' => 7, 'name' => 'Génie Biomédical', 'abbreviation' => 'GBM'],
            ];
            return response()->json($defaultFilieres);
        }

        return response()->json($filieres);
    }

    /**
     * Enregistre l'auto-déclaration d'un ancien étudiant (< 2023)
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'registrationYear' => 'required|integer|max:2022',
            'matricule' => 'required|string|max:50',
            'lastName' => 'required|string|max:100',
            'firstName' => 'required|string|max:100',
            'dateOfBirth' => 'nullable|date',
            'placeOfBirth' => 'nullable|string|max:150',
            'place_of_birth' => 'nullable|string|max:150',
            'cycle' => 'required|string|max:100',
            'filiereId' => 'required|integer|exists:departments,id',
            'filiereIds' => 'nullable|array',
            'email' => [
                'required',
                'string',
                'max:150',
                new \App\Rules\ValidRealEmail(),
                function ($attribute, $value, $fail) use ($request) {
                    $email = trim(mb_strtolower($value));
                    $matricule = trim($request->input('matricule', ''));

                    // 1. Vérification de l'unicité parmi les autres anciens étudiants (dossiers actifs non rejetés)
                    $otherLegacy = \App\Modules\LegacyStudent\Models\LegacyStudent::whereRaw('LOWER(email) = ?', [$email])
                        ->where('status', '!=', 'rejected')
                        ->where('matricule', '!=', $matricule)
                        ->first();

                    if ($otherLegacy) {
                        $fail("Cette adresse email est déjà associée à un autre dossier étudiant. Chaque étudiant doit obligatoirement utiliser sa propre adresse email.");
                        return;
                    }

                    // 2. Vérification de l'unicité parmi les étudiants récents (dossiers actifs non rejetés)
                    $otherPI = \App\Modules\Inscription\Models\PersonalInformation::whereRaw('LOWER(email) = ?', [$email])
                        ->whereHas('pendingStudents', function ($q) {
                            $q->where('status', '!=', 'rejected');
                        })
                        ->first();

                    if ($otherPI) {
                        $normLastName = mb_strtolower(trim($request->input('lastName', '')));
                        if ($normLastName && mb_strtolower($otherPI->last_name) !== $normLastName) {
                            $fail("Cette adresse email est déjà associée à un autre dossier étudiant. Chaque étudiant doit obligatoirement utiliser sa propre adresse email.");
                        }
                    }
                }
            ],
            'phone' => 'nullable|string|max:30',
        ], [
            'registrationYear.required' => 'L\'année d\'inscription est requise.',
            'registrationYear.max' => 'Ce formulaire est réservé aux étudiants inscrits avant 2023.',
            'matricule.required' => 'Le matricule est requis.',
            'lastName.required' => 'Le nom est requis.',
            'firstName.required' => 'Le prénom est requis.',
            'cycle.required' => 'Le cycle d\'études est requis.',
            'filiereId.required' => 'La filière est requise.',
            'dateOfBirth.date' => 'La date de naissance doit être une date valide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $selectedFiliereId = $validated['filiereId'] ?? ($validated['filiereIds'][0] ?? null);
        $placeOfBirth = $validated['placeOfBirth'] ?? ($validated['place_of_birth'] ?? $request->input('lieu_naissance'));

        // Recherche d'un dossier existant par MATRICULE ou par IDENTITÉ
        $existingByMatricule = LegacyStudent::where('matricule', $validated['matricule'])->first();

        $normLastName = mb_strtolower(trim($validated['lastName']));
        $firstWord = mb_strtolower(explode(' ', trim($validated['firstName']))[0]);
        $identityQuery = LegacyStudent::query()
            ->whereRaw('LOWER(last_name) = ?', [$normLastName])
            ->whereRaw('LOWER(first_name) LIKE ?', ["%{$firstWord}%"]);

        if (!empty($validated['dateOfBirth'])) {
            $identityQuery->whereDate('date_of_birth', $validated['dateOfBirth']);
        }

        $existingByIdentity = $identityQuery->first();
        $existingStudent = $existingByMatricule ?? $existingByIdentity;

        return DB::transaction(function () use ($validated, $selectedFiliereId, $placeOfBirth, $existingStudent, $request) {
            $student = $existingStudent ?? new LegacyStudent();
            
            // Conserver le statut 'validated' si déjà validé, sinon passer/rester en 'pending'
            $newStatus = ($student->status === 'validated') ? 'validated' : 'pending';

            $student->fill([
                'matricule' => $validated['matricule'],
                'last_name' => strtoupper($validated['lastName']),
                'first_name' => ucwords(strtolower($validated['firstName'])),
                'date_of_birth' => $validated['dateOfBirth'] ?? $student->date_of_birth,
                'place_of_birth' => $placeOfBirth ?? $student->place_of_birth,
                'cycle' => $validated['cycle'] ?? $student->cycle,
                'department_id' => $selectedFiliereId ?? $student->department_id,
                'email' => $validated['email'] ?? $student->email,
                'phone' => $validated['phone'] ?? $student->phone,
                'enrollment_year' => $validated['registrationYear'] ?? $student->enrollment_year,
                'status' => $newStatus,
                'rejection_reason' => null, // Réinitialisation du motif de rejet si applicable
            ]);
            $student->save();

            if ($selectedFiliereId) {
                $student->departments()->sync([$selectedFiliereId]);
            }

            $filieres = $student->departments()->select('departments.id', 'departments.name', 'departments.abbreviation')->get();
            $mainFiliere = $filieres->first() ?? $student->department;

            return response()->json([
                'id' => $student->id,
                'matricule' => $student->matricule,
                'lastName' => $student->last_name,
                'firstName' => $student->first_name,
                'dateOfBirth' => $student->date_of_birth,
                'placeOfBirth' => $student->place_of_birth,
                'place_of_birth' => $student->place_of_birth,
                'cycle' => $student->cycle,
                'filiere' => $mainFiliere,
                'filieres' => $filieres,
                'email' => $student->email,
                'phone' => $student->phone,
                'registrationYear' => $student->enrollment_year,
                'status' => 'attached',
                'dossier_status' => $student->status,
                'message' => $student->wasRecentlyCreated 
                    ? 'Votre dossier a été enregistré avec succès. Vous pouvez poursuivre votre démarche.'
                    : 'Votre dossier a été retrouvé et mis à jour. Vous pouvez poursuivre votre démarche.',
            ], 200);
        });
    }

    /**
     * Recherche un ancien étudiant déclaré par nom / prénom / date de naissance
     * (fallback depuis le formulaire apply?type=matricule quand lookup-id échoue)
     */
    public function lookupByName(Request $request): JsonResponse
    {
        $lastName = $request->input('last_name') ?? $request->input('lastName') ?? $request->input('nom') ?? '';
        $firstNames = $request->input('first_names') ?? $request->input('firstName') ?? $request->input('first_name') ?? $request->input('prenoms') ?? $request->input('prenom') ?? '';
        $birthDate = $request->input('birth_date') ?? $request->input('date_of_birth') ?? $request->input('dateOfBirth') ?? $request->input('date_naissance') ?? null;
        $birthPlace = $request->input('birth_place') ?? $request->input('place_of_birth') ?? $request->input('placeOfBirth') ?? $request->input('lieu_naissance') ?? null;

        if (empty($lastName) || empty($firstNames)) {
            return response()->json([
                'success' => false,
                'message' => 'Le nom et les prénoms sont requis.',
            ], 422);
        }

        $firstWord = mb_strtolower(explode(' ', trim($firstNames))[0]);

        $query = LegacyStudent::query()
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($lastName))])
            ->whereRaw('LOWER(first_name) LIKE ?', ["%{$firstWord}%"]);

        if (!empty($birthDate)) {
            $query->whereDate('date_of_birth', $birthDate);
        }

        if (!empty($birthPlace)) {
            $query->where(function ($q) use ($birthPlace) {
                $q->whereNull('place_of_birth')
                  ->orWhereRaw('LOWER(place_of_birth) LIKE ?', ['%' . mb_strtolower(trim($birthPlace)) . '%']);
            });
        }

        $student = $query->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun dossier trouvé parmi les anciens étudiants (avant 2023).',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'student_id_number' => $student->matricule,
                'matricule'         => $student->matricule,
                'last_name'         => $student->last_name,
                'first_names'       => $student->first_name,
                'date_of_birth'     => $student->date_of_birth,
                'place_of_birth'    => $student->place_of_birth,
                'source'            => 'legacy',
            ],
            'message' => 'Matricule retrouvé dans les dossiers anciens.',
        ]);
    }

    /**
     * Statut des attestations pour un ancien étudiant
     * GET /api/attestations/status?matricule=...
     */
    public function attestationsStatus(Request $request): JsonResponse
    {
        $matricule = strtoupper(trim($request->query('matricule', '')));
        if (empty($matricule)) {
            return response()->json(['message' => 'Le matricule est requis.'], 400);
        }

        $student = LegacyStudent::with('departments')->where('matricule', $matricule)->first();

        if (!$student) {
            return response()->json(['message' => 'Aucun étudiant trouvé avec ce matricule.'], 404);
        }

        $filiere = $student->department?->name ?? $student->departments->first()?->name ?? 'Génie Civil';
        $year = $student->enrollment_year ? "{$student->enrollment_year}-" . ($student->enrollment_year + 1) : 'Avant 2023';

        return response()->json([
            'student' => [
                'last_name'     => $student->last_name,
                'first_names'   => $student->first_name,
                'matricule'     => $student->matricule,
                'level'         => 'Ancien Étudiant (< 2023)',
                'department'    => $filiere,
                'academic_year' => $year,
            ],
            'documents' => [
                ['type' => 'succes', 'status' => 'disponible'],
                ['type' => 'definitive', 'status' => 'disponible'],
                ['type' => 'inscription', 'status' => 'disponible'],
                ['type' => 'passage', 'status' => 'disponible'],
            ],
        ]);
    }

    /**
     * Statut des bulletins pour un ancien étudiant
     * GET /api/bulletins/status?matricule=...
     */
    public function bulletinsStatus(Request $request): JsonResponse
    {
        $matricule = strtoupper(trim($request->query('matricule', '')));
        if (empty($matricule)) {
            return response()->json(['message' => 'Le matricule est requis.'], 400);
        }

        $student = LegacyStudent::with('departments')->where('matricule', $matricule)->first();

        if (!$student) {
            return response()->json(['message' => 'Aucun étudiant trouvé avec ce matricule.'], 404);
        }

        $filiere = $student->department?->name ?? $student->departments->first()?->name ?? 'Génie Civil';
        $year = $student->enrollment_year ? "{$student->enrollment_year}-" . ($student->enrollment_year + 1) : 'Avant 2023';

        return response()->json([
            'student' => [
                'last_name'     => $student->last_name,
                'first_names'   => $student->first_name,
                'matricule'     => $student->matricule,
                'level'         => 'Ancien Étudiant (< 2023)',
                'department'    => $filiere,
                'academic_year' => $year,
            ],
            'years' => [
                [
                    'academic_year' => $year,
                    'level'         => 'Ancien Étudiant (< 2023)',
                    'bulletin'      => ['status' => 'disponible'],
                ],
            ],
        ]);
    }
}
