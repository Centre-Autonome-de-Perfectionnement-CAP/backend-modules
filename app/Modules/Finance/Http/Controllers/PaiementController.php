<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Requests\CreatePaiementRequest;
use App\Modules\Finance\Services\PaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

/**
 * @OA\Tag(
 *     name="Paiements",
 *     description="Gestion des paiements étudiants"
 * )
 */
class PaiementController extends Controller
{
    use ApiResponse, HasPagination;

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
        $filters = $request->only(['search', 'statut', 'matricule', 'date_debut', 'date_fin']);
        $perPage = $this->getPerPage($request);
        
        $paiements = $this->paiementService->getAll($filters, $perPage);

        return $this->successPaginatedResponse(
            $paiements,
            'Paiements récupérés avec succès'
        );
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
        // Créer le paiement via le service
        // Le service vérifie l'existence de l'étudiant et lance une exception si nécessaire
        $paiement = $this->paiementService->create(
            $request->validated(),
            $request->file('quittance')
        );

        return $this->createdResponse(
            [
                'id' => $paiement->id,
                'student_id_number' => $paiement->student_id_number,
                'amount' => $paiement->amount,
                'reference' => $paiement->reference,
                'account_number' => $paiement->account_number,
                'payment_date' => $paiement->payment_date,
                'purpose' => $paiement->purpose,
                'email' => $paiement->email,
                'contact' => $paiement->contact,
                'status' => $paiement->status,
                'created_at' => $paiement->created_at->toISOString(),
            ],
            'Paiement soumis avec succès. Il sera traité dans les plus brefs délais.'
        );
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
            // Décoder la référence au cas où elle serait encodée
            $reference = urldecode($reference);
            $paiement = $this->paiementService->getByReference($reference);

            if (!$paiement) {
                return $this->errorResponse(
                    'Aucune quittance trouvée avec cette référence. Vérifiez que la référence est correcte.',
                    404,
                    'PAIEMENT_NOT_FOUND'
                );
            }

            return $this->successResponse(
                [
                    'reference' => $paiement->reference,
                    'status' => $paiement->status,
                    'student_id_number' => $paiement->student_id_number,
                    'amount' => $paiement->amount,
                    'payment_date' => $paiement->payment_date,
                    'purpose' => $paiement->purpose,
                    'receipt_path' => $paiement->receipt_path,
                    'observation' => $paiement->observation,
                    'created_at' => $paiement->created_at->toISOString(),
                ],
                'Paiement récupéré avec succès'
            );
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du paiement', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse(
                'Erreur lors de la récupération du paiement: ' . $e->getMessage(),
                500,
                'INTERNAL_ERROR'
            );
        }
    }

    /**
     * Télécharger la quittance d'un paiement
     */
    public function download(string $reference)
    {
        // Décoder la référence au cas où elle serait encodée
        $reference = urldecode($reference);
        $paiement = $this->paiementService->getByReference($reference);

        if (!$paiement) {
            return $this->errorResponse(
                'Aucun paiement trouvé avec cette référence',
                404,
                'PAIEMENT_NOT_FOUND'
            );
        }

        if (!$paiement->receipt_path) {
            return $this->errorResponse(
                'Aucune quittance disponible pour ce paiement',
                404,
                'RECEIPT_NOT_FOUND'
            );
        }

        $filePath = storage_path('app/private/' . $paiement->receipt_path);

        if (!file_exists($filePath)) {
            return $this->errorResponse(
                'Le fichier de quittance est introuvable',
                404,
                'FILE_NOT_FOUND'
            );
        }

        return response()->download($filePath, basename($paiement->receipt_path));
    }

    /**
     * Récupérer les informations d'un étudiant par matricule
     */
    public function getStudentInfo(string $matricule): JsonResponse
    {
        $studentInfo = $this->paiementService->getStudentInfo($matricule);
        
        return $this->successResponse(
            $studentInfo,
            'Informations étudiant récupérées avec succès'
        );
    }
}
