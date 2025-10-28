<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Paiement;
use App\Modules\Finance\Http\Requests\CreatePaiementRequest;
use App\Modules\Finance\Services\PaiementService;
use App\Models\Student;
use App\Modules\Inscription\Models\PersonalInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * @OA\Tag(
 *     name="Paiements",
 *     description="Gestion des paiements étudiants"
 * )
 */
class PaiementController extends Controller
{
    public function __construct(
        protected PaiementService $paiementService
    ) {
        // Pas de middleware d'authentification pour cette API
    }

    /**
     * @OA\Get(
     *     path="/api/finance/paiements",
     *     summary="Liste des paiements",
     *     description="Récupère la liste paginée des paiements avec possibilité de recherche et filtrage",
     *     operationId="getPaiements",
     *     tags={"Paiements"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par matricule, référence, email ou contact",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"attente", "accepte", "rejete"})
     *     ),
     *     @OA\Parameter(
     *         name="matricule",
     *         in="query",
     *         description="Filtrer par matricule",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début pour filtrer les paiements",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin pour filtrer les paiements",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des paiements récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="matricule", type="string"),
     *                 @OA\Property(property="montant", type="number"),
     *                 @OA\Property(property="reference", type="string"),
     *                 @OA\Property(property="statut", type="string"),
     *                 @OA\Property(property="date_versement", type="string", format="date"),
     *                 @OA\Property(property="created_at", type="string", format="datetime")
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Paiement::query()->with(['student', 'quittanceFile']);

            // Recherche globale
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('matricule', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('contact', 'like', "%{$search}%")
                      ->orWhere('numero_compte', 'like', "%{$search}%");
                });
            }

            // Filtre par statut
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            // Filtre par matricule
            if ($request->filled('matricule')) {
                $query->where('matricule', $request->matricule);
            }

            // Filtre par plage de dates
            if ($request->filled('date_debut')) {
                $query->whereDate('created_at', '>=', $request->date_debut);
            }

            if ($request->filled('date_fin')) {
                $query->whereDate('created_at', '<=', $request->date_fin);
            }

            // Tri par défaut : plus récents en premier
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->input('per_page', 15);
            $perPage = min(max((int) $perPage, 1), 100); // Entre 1 et 100

            $paiements = $query->paginate($perPage);

            // Formater les données
            $data = $paiements->map(function ($paiement) {
                return [
                    'id' => $paiement->id,
                    'matricule' => $paiement->matricule,
                    'montant' => $paiement->montant,
                    'reference' => $paiement->reference,
                    'numero_compte' => $paiement->numero_compte,
                    'date_versement' => $paiement->date_versement?->format('Y-m-d'),
                    'motif' => $paiement->motif,
                    'email' => $paiement->email,
                    'contact' => $paiement->contact,
                    'statut' => $paiement->statut,
                    'observation' => $paiement->observation,
                    'quittance_id' => $paiement->quittance,
                    'student' => $paiement->student ? [
                        'id' => $paiement->student->id,
                        'student_id_number' => $paiement->student->student_id_number,
                    ] : null,
                    'created_at' => $paiement->created_at->toISOString(),
                    'updated_at' => $paiement->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => $paiements->total(),
                    'per_page' => $paiements->perPage(),
                    'current_page' => $paiements->currentPage(),
                    'last_page' => $paiements->lastPage(),
                    'from' => $paiements->firstItem(),
                    'to' => $paiements->lastItem(),
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des paiements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des paiements.',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur.',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/finance/paiements",
     *     summary="Créer un nouveau paiement",
     *     description="Permet à un étudiant de soumettre un paiement avec une quittance",
     *     operationId="createPaiement",
     *     tags={"Paiements"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"matricule", "reference", "numero_compte", "quittance", "motif"},
     *                 @OA\Property(property="matricule", type="string", maxLength=11, example="202300001", description="Matricule de l'étudiant"),
     *                 @OA\Property(property="montant", type="number", format="float", example=50000, description="Montant du paiement"),
     *                 @OA\Property(property="reference", type="string", maxLength=255, example="REF-2023-001", description="Référence unique du paiement"),
     *                 @OA\Property(property="numero_compte", type="string", maxLength=255, example="123456789", description="Numéro de compte"),
     *                 @OA\Property(property="date_versement", type="string", format="date", example="2023-10-26", description="Date du versement"),
     *                 @OA\Property(property="quittance", type="string", format="binary", description="Fichier de quittance (PDF, JPG, PNG, max 5MB)"),
     *                 @OA\Property(property="motif", type="string", description="Motif du paiement"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, example="student@example.com", description="Email de contact"),
     *                 @OA\Property(property="contact", type="string", maxLength=255, example="+221 77 123 45 67", description="Numéro de contact")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paiement créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement soumis avec succès. Il sera traité dans les plus brefs délais."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="matricule", type="string", example="202300001"),
     *                 @OA\Property(property="montant", type="number", example=50000),
     *                 @OA\Property(property="reference", type="string", example="REF-2023-001"),
     *                 @OA\Property(property="statut", type="string", example="attente"),
     *                 @OA\Property(property="created_at", type="string", format="datetime")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation des données."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors du traitement de votre paiement."),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(CreatePaiementRequest $request): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            // Valider et récupérer l'étudiant
            $student = Student::where('student_id_number', $request->matricule)->first();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le matricule fourni n\'existe pas dans notre système.',
                    'errors' => [
                        'matricule' => ['Le matricule est invalide.']
                    ]
                ], 422);
            }

            // Uploader la quittance via le FileStorageService
            $quittanceFile = null;
            if ($request->hasFile('quittance')) {
                try {
                    $quittanceFile = $this->fileStorageService->uploadFile(
                        uploadedFile: $request->file('quittance'),
                        userId: $student->id,
                        visibility: 'private',
                        collection: 'quittances',
                        moduleName: 'Finance',
                        moduleResourceType: 'Paiement',
                        moduleResourceId: null, // Sera mis à jour après la création du paiement
                        metadata: [
                            'matricule' => $request->matricule,
                            'reference' => $request->reference,
                            'uploaded_from' => 'paiement_api',
                        ]
                    );
                } catch (Exception $e) {
                    Log::error('Erreur lors de l\'upload de la quittance', [
                        'matricule' => $request->matricule,
                        'reference' => $request->reference,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    DB::rollBack();
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de l\'upload de la quittance. Veuillez réessayer.',
                        'error' => config('app.debug') ? $e->getMessage() : 'Erreur de traitement du fichier.',
                    ], 500);
                }
            }

            // Créer le paiement
            $paiement = Paiement::create([
                'matricule' => $request->matricule,
                'montant' => $request->montant,
                'reference' => $request->reference,
                'numero_compte' => $request->numero_compte,
                'date_versement' => $request->date_versement,
                'quittance' => $quittanceFile ? $quittanceFile->id : null,
                'motif' => $request->motif,
                'email' => $request->email,
                'contact' => $request->contact,
                'statut' => 'attente',
            ]);

            // Mettre à jour la relation du fichier avec le paiement
            if ($quittanceFile) {
                $quittanceFile->update([
                    'module_resource_id' => $paiement->id,
                ]);
            }

            // Logger le succès
            Log::info('Nouveau paiement créé', [
                'paiement_id' => $paiement->id,
                'matricule' => $request->matricule,
                'reference' => $request->reference,
                'montant' => $request->montant,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement soumis avec succès. Il sera traité dans les plus brefs délais.',
                'data' => [
                    'id' => $paiement->id,
                    'matricule' => $paiement->matricule,
                    'montant' => $paiement->montant,
                    'reference' => $paiement->reference,
                    'numero_compte' => $paiement->numero_compte,
                    'date_versement' => $paiement->date_versement,
                    'motif' => $paiement->motif,
                    'email' => $paiement->email,
                    'contact' => $paiement->contact,
                    'statut' => $paiement->statut,
                    'created_at' => $paiement->created_at->toISOString(),
                ],
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            
            // Logger l'erreur complète
            Log::error('Erreur lors de la création du paiement', [
                'matricule' => $request->matricule ?? null,
                'reference' => $request->reference ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer ultérieurement.',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur.',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/finance/paiements/{reference}",
     *     summary="Consulter le statut d'un paiement",
     *     description="Permet de vérifier le statut d'un paiement via sa référence",
     *     operationId="getPaiementStatus",
     *     tags={"Paiements"},
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         description="Référence du paiement",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut du paiement récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reference", type="string", example="REF-2023-001"),
     *                 @OA\Property(property="statut", type="string", example="attente"),
     *                 @OA\Property(property="montant", type="number", example=50000),
     *                 @OA\Property(property="date_versement", type="string", format="date"),
     *                 @OA\Property(property="created_at", type="string", format="datetime")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paiement non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun paiement trouvé avec cette référence.")
     *         )
     *     )
     * )
     */
    public function show(string $reference): JsonResponse
    {
        try {
            $paiement = Paiement::where('reference', $reference)->first();

            if (!$paiement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun paiement trouvé avec cette référence.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reference' => $paiement->reference,
                    'statut' => $paiement->statut,
                    'montant' => $paiement->montant,
                    'date_versement' => $paiement->date_versement,
                    'motif' => $paiement->motif,
                    'created_at' => $paiement->created_at->toISOString(),
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Erreur lors de la consultation du paiement', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la consultation du paiement.',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur.',
            ], 500);
        }
    }

    /**
     * Récupérer les informations d'un étudiant par matricule
     */
    public function getStudentInfo(string $matricule): JsonResponse
    {
        try {
            $student = Student::where('student_id_number', $matricule)->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Étudiant non trouvé avec ce matricule.',
                ], 404);
            }

            // Chercher les informations personnelles via le matricule (téléphone)
            $personalInfo = PersonalInformation::where(function($query) use ($matricule) {
                $query->whereJsonContains('contacts', $matricule)
                      ->orWhereJsonContains('contacts', [$matricule]);
            })->first();

            // Récupérer les filières/départements associés à l'étudiant
            // Vous devrez adapter cette partie selon votre structure de base de données
            $filieres = DB::table('student_department')
                ->join('departments', 'student_department.department_id', '=', 'departments.id')
                ->where('student_department.student_id', $student->id)
                ->select('departments.id', 'departments.name as nom')
                ->get()
                ->toArray();

            // Récupérer le diplôme (à adapter selon votre structure)
            $diplome = null;
            if ($personalInfo && isset($personalInfo->entry_diploma_id)) {
                $diplome = DB::table('entry_diplomas')
                    ->where('id', $personalInfo->entry_diploma_id)
                    ->select('id', 'name as nom')
                    ->first();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $student->id,
                    'student_id_number' => $student->student_id_number,
                    'nom' => $personalInfo->last_name ?? null,
                    'prenoms' => $personalInfo->first_names ?? null,
                    'email' => $personalInfo->email ?? null,
                    'tel' => is_array($personalInfo->contacts ?? null) ? ($personalInfo->contacts[0] ?? null) : $matricule,
                    'diplome' => $diplome ? [
                        'id' => $diplome->id,
                        'nom' => $diplome->nom
                    ] : null,
                    'filieres' => $filieres, // Liste des filières pour sélection
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des infos étudiant', [
                'matricule' => $matricule,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des informations.',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur.',
            ], 500);
        }
    }
}
