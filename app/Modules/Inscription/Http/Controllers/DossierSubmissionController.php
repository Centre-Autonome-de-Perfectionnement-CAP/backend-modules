<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Http\Requests\SubmitCompletedDossierRequest;
use App\Modules\Inscription\Http\Requests\SubmitIngenieurPrepaDossierRequest;
use App\Modules\Inscription\Http\Requests\SubmitIngenieurSpecialiteDossierRequest;
use App\Modules\Inscription\Http\Requests\SubmitLicenceDossierRequest;
use App\Modules\Inscription\Http\Requests\SubmitMasterDossierRequest;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Modules\Inscription\Services\DossierSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Dossier Submission",
 *     description="Soumission des dossiers (Licence, Master, Ingénieur, Compléments)"
 * )
 */
class DossierSubmissionController extends Controller
{
    use ApiResponse;

    public function __construct(private DossierSubmissionService $submissionService)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/dossiers/periods",
     *     summary="Périodes de soumission actives par cycle",
     *     description="Liste les périodes de soumission actives pour un cycle donné",
     *     operationId="getDossierSubmissionPeriods",
     *     tags={"Dossier Submission"},
     *     @OA\Parameter(name="cycle", in="query", required=false, @OA\Schema(type="string", enum={"Licence","Master","Ingénieur"}), description="Cycle cible"),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des périodes",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="department", type="string"),
     *                 @OA\Property(property="academic_year", type="string"),
     *                 @OA\Property(property="start_date", type="string"),
     *                 @OA\Property(property="end_date", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=400, description="Cycle invalide"),
     *     @OA\Response(response=404, description="Aucune période active")
     * )
     */
    public function getSubmissionPeriods(Request $request): JsonResponse
    {
        $currentDate = now()->toDateString();
        $cycleName = $request->query('cycle', 'Licence');
        $validCycles = ['Licence', 'Master', 'Ingénieur'];

        if (!in_array($cycleName, $validCycles)) {
            return $this->errorResponse('Cycle invalide spécifié', 'INVALID_CYCLE', 400);
        }

        $periods = $this->submissionService->getActiveSubmissionPeriods($cycleName);

        if (empty($periods)) {
            return $this->errorResponse("Aucune période active pour {$cycleName}", 'NO_ACTIVE_PERIOD', 404);
        }

        return $this->successResponse($periods, 'Périodes récupérées avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/dossiers/licence",
     *     summary="Soumettre un dossier Licence",
     *     description="Soumet un dossier pour le cycle Licence avec documents requis",
     *     operationId="submitLicenceDossier",
     *     tags={"Dossier Submission"},
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"last_name","first_names","email","birth_date","birth_place","birth_country","gender","contacts","study_level","academic_year_id","department_id","demande_da","cv","acte_naissance","diplome_bac","attestation_travail","quittance_rectorat","quittance_cap"},
     *                 @OA\Property(property="last_name", type="string"),
     *                 @OA\Property(property="first_names", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="birth_date", type="string", format="date"),
     *                 @OA\Property(property="birth_place", type="string"),
     *                 @OA\Property(property="birth_country", type="string"),
     *                 @OA\Property(property="gender", type="string", enum={"M","F"}),
     *                 @OA\Property(property="contacts", type="array", @OA\Items()),
     *                 @OA\Property(property="study_level", type="string"),
     *                 @OA\Property(property="entry_diploma_id", type="integer"),
     *                 @OA\Property(property="academic_year_id", type="integer"),
     *                 @OA\Property(property="department_id", type="integer"),
     *                 @OA\Property(property="demande_da", type="string", format="binary"),
     *                 @OA\Property(property="cv", type="string", format="binary"),
     *                 @OA\Property(property="acte_naissance", type="string", format="binary"),
     *                 @OA\Property(property="diplome_bac", type="string", format="binary"),
     *                 @OA\Property(property="diplome_licence", type="string", format="binary"),
     *                 @OA\Property(property="attestation_travail", type="string", format="binary"),
     *                 @OA\Property(property="quittance_rectorat", type="string", format="binary"),
     *                 @OA\Property(property="quittance_cap", type="string", format="binary"),
     *                 @OA\Property(property="attestation_depot_dossier", type="string", format="binary"),
     *                 @OA\Property(property="photo", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Soumission réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="tracking_code", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Erreur de validation / période inactive")
     * )
     */
    public function submitLicenceDossier(SubmitLicenceDossierRequest $request): JsonResponse
    {
        // Debug : log les données reçues
        Log::info('Licence Dossier Submission - Data received', [
            'birth_date' => $request->input('birth_date'),
            'birth_place' => $request->input('birth_place'),
            'birth_country' => $request->input('birth_country'),
            'all_inputs' => $request->except(['photo', 'demande_da', 'cv', 'acte_naissance', 'diplome_bac', 'diplome_licence', 'attestation_travail', 'quittance_rectorat', 'quittance_cap', 'attestation_depot_dossier'])
        ]);

        $fileFields = [
                'demande_da' => 'Demande manuscrite adressée au D/EPAC',
                'cv' => 'Curriculum Vitae',
                'acte_naissance' => "Photocopie de l’extrait d’acte de naissance légalisé ou sécurisé",
                'diplome_bac' => 'Photocopie légalisée du diplôme BAC ou équivalent',
                'diplome_licence' => 'Photocopie légalisée du diplôme de la licence professionnelle',
                'attestation_travail' => 'Attestation de travail',
                'quittance_rectorat' => 'Quittance Rectorat de 2.000F',
                'quittance_cap' => 'Quittance de 10.000F',
                'attestation_depot_dossier' => 'Attestation de dépôt de dossier pour diplômes étrangers',
            ];

        $result = $this->submissionService->submitDossier(
            $request,
            'Licence Professionnelle',
            ['Baccalauréat Scientifique', 'BTS', 'DTI', 'DUT', 'DEAT'],
            $fileFields
        );

        return $this->createdResponse($result, 'Dossier Licence soumis avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/dossiers/master",
     *     summary="Soumettre un dossier Master",
     *     operationId="submitMasterDossier",
     *     tags={"Dossier Submission"},
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"last_name","first_names","email","birth_date","birth_place","birth_country","gender","contacts","study_level","academic_year_id","department_id","demande_da","cv","acte_naissance","diplome_bac","diplome_license","attestation_travail","quittance_rectorat","quittance_cap"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Soumission réussie"),
     *     @OA\Response(response=400, description="Erreur de validation / période inactive")
     * )
     */
    public function submitMasterDossier(SubmitMasterDossierRequest $request): JsonResponse
    {
        $fileFields = [
                'demande_da' => 'Demande manuscrite adressée au D/EPAC',
                'cv' => 'Curriculum Vitae',
                'acte_naissance' => "Photocopie de l’extrait d’acte de naissance légalisé ou sécurisé",
                'diplome_bac' => 'Photocopie légalisée du diplôme BAC',
                'diplome_license' => 'Photocopie légalisée du diplôme de la licence professionnelle',
                'attestation_travail' => 'Attestation de travail',
                'quittance_rectorat' => 'Quittance Rectorat de 2.000F',
                'quittance_cap' => 'Quittance de 20.000F',
                'attestation_depot_dossier' => 'Attestation de dépôt de dossier pour diplômes étrangers',
                'attestation_anglais' => 'Attestation d’Anglais pour le secteur biologique',
            ];

        $result = $this->submissionService->submitDossier(
            $request,
            'Master Professionnel',
            ['Licence Professionnelle'],
            $fileFields
        );

        return $this->createdResponse($result, 'Dossier Master soumis avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/dossiers/ingenieur/prepa",
     *     summary="Soumettre un dossier Ingénieur Prépa",
     *     operationId="submitIngenieurPrepaDossier",
     *     tags={"Dossier Submission"},
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"last_name","first_names","email","birth_date","birth_place","birth_country","gender","contacts","study_level","academic_year_id","department_id","demande_da","cv","acte_naissance","diplome_bac","diplome_licence","attestation_travail","quittance_cap"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Soumission réussie"),
     *     @OA\Response(response=400, description="Erreur de validation / période inactive")
     * )
     */
    public function submitIngenieurPrepaDossier(SubmitIngenieurPrepaDossierRequest $request): JsonResponse
    {
        $fileFields = [
                'demande_da' => 'Demande manuscrite adressée au D/EPAC',
                'cv' => 'Curriculum Vitae',
                'acte_naissance' => "Photocopie de l’extrait d’acte de naissance légalisé ou sécurisé",
                'diplome_bac' => 'Photocopie légalisée du diplôme BAC',
                'diplome_licence' => 'Photocopie légalisée du diplôme de la licence',
                'attestation_travail' => 'Attestation de travail',
                // 'quittance_rectorat' => 'Quittance Rectorat de 2.000F',
                'quittance_cap' => 'Quittance de 15.000F',
                'attestation_depot_dossier' => 'Attestation de dépôt de dossier pour diplômes étrangers',
            ];

        $result = $this->submissionService->submitDossier(
            $request,
            'Ingénierie',
            ['Licence Professionnelle'],
            $fileFields
        );

        return $this->createdResponse($result, 'Dossier Ingénieur Prépa soumis avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/dossiers/ingenieur/specialite",
     *     summary="Soumettre un dossier Ingénieur Spécialité",
     *     operationId="submitIngenieurSpecialiteDossier",
     *     tags={"Dossier Submission"},
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"student_id_number","department_id","academic_year_id","certificat_prepa","quittance_cap"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Soumission réussie"),
     *     @OA\Response(response=400, description="Non éligible / validation échouée")
     * )
     */
    public function submitIngenieurSpecialiteDossier(SubmitIngenieurSpecialiteDossierRequest $request): JsonResponse
    {
        $this->submissionService->validateIngenieurSpecialiteEligibility(
            $request->student_id_number,
            $request->department_id
        );

        $fileFields = [
            'certificat_prepa' => 'Certificat de Classes Préparatoires',
            'quittance_rectorat' => 'Quittance Rectorat',
            'quittance_cap' => 'Quittance CAP',
        ];

        $result = $this->submissionService->submitDossier(
            $request,
            'Ingénierie',
            ['Certificat de Classes Préparatoires', 'Quittance Rectorat', 'Quittance CAP'],
            $fileFields,
            false
        );

        return $this->createdResponse($result, 'Dossier Ingénieur Spécialité soumis avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/dossiers/{trackingCode}",
     *     summary="Récupérer un dossier par code",
     *     operationId="getDossierByCode",
     *     tags={"Dossier Submission"},
     *     @OA\Parameter(name="trackingCode", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Dossier trouvé"),
     *     @OA\Response(response=404, description="Dossier non trouvé")
     * )
     */
    public function getDossier(string $trackingCode): JsonResponse
    {
        $dossierData = $this->submissionService->getDossierByTrackingCode($trackingCode);
        
        return $this->successResponse($dossierData, 'Dossier récupéré avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/dossiers/complement/{trackingCode}",
     *     summary="Soumettre des compléments de dossier",
     *     operationId="submitComplementDossier",
     *     tags={"Dossier Submission"},
     *     @OA\Parameter(name="trackingCode", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"names","files"},
     *                 @OA\Property(property="names", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="files", type="array", @OA\Items(type="string", format="binary"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Compléments ajoutés"),
     *     @OA\Response(response=400, description="Erreur de validation / période inactive")
     * )
     */
    public function submitComplementDossier(SubmitCompletedDossierRequest $request, string $trackingCode): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->submissionService->submitComplementDossier(
            $validated,
            $trackingCode
        );

        return $this->createdResponse($result, 'Compléments soumis avec succès');
    }
}
