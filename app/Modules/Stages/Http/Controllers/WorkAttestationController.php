<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Core\Support\LinkToken;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\StagePdfService;
use App\Modules\Stages\Services\WorkAttestationService as Work;
use App\Modules\Stages\Support\StageAccess;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Attestations de service fait :
 *  - responsables : inviter, partir d'un dossier, corriger, valider, annuler ;
 *  - secrétariat (et responsables) : imprimer, prévenir la personne, remettre ;
 *  - personne invitée : lien public, sans compte.
 */
class WorkAttestationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly Work $work,
        private readonly StagePdfService $pdfs,
    ) {}

    // ── Responsable ─────────────────────────────────────────────────────

    public function invite(Request $request): JsonResponse
    {
        $data = $request->validate(['numero' => ['required', 'string', 'max:30']], ['numero.required' => 'Indiquez le numéro WhatsApp.']);

        return $this->guard(fn () => $this->successResponse($this->work->present($this->work->invite($data['numero'], $request->user())),
            'Lien envoyé sur WhatsApp (valable ' . Work::TTL_DAYS . ' jours).', 201));
    }

    /** Dossier d'un ancien stagiaire : lien envoyé (« lien ») ou fiche à remplir soi-même (« remplir »). */
    public function fromStage(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['mode' => ['required', Rule::in(['lien', 'remplir'])]]);
        $stage = StageRequest::findOrFail($id);

        return $this->guard(function () use ($data, $stage, $request) {
            if ($data['mode'] === 'lien') {
                if (!$stage->whatsapp) {
                    throw new BusinessException('Aucun numéro WhatsApp dans ce dossier.', 'STAGE_WORK_PHONE', 422);
                }
                $a = $this->work->invite($stage->whatsapp, $request->user(), $stage);

                return $this->successResponse($this->work->present($a), 'Lien envoyé sur WhatsApp à ' . $stage->fullName() . '.', 201);
            }

            return $this->successResponse($this->work->present($this->work->draftFromStage($stage, $request->user())), 'Fiche préremplie : complétez-la puis validez.', 201);
        });
    }

    /** Dossier d'un stagiaire : ses attestations de service fait. */
    public function forStage(int $id): JsonResponse
    {
        $stage = StageRequest::findOrFail($id);

        return $this->guard(fn () => $this->successResponse($this->work->forStage($stage)->values()));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate($this->rules(false) + [
            'phone'        => ['sometimes', 'nullable', 'string', 'max:30'],
            'nature'       => ['sometimes', 'nullable', 'string', 'max:80'],
            'appreciation' => ['sometimes', 'nullable', 'string', 'max:400'],
        ], $this->messages());

        return $this->guard(fn () => $this->successResponse(
            $this->work->present($this->work->update($this->work->find($id, false), $data, $request->user())), 'Modifications enregistrées.'));
    }

    public function validateWork(Request $request, int $id): JsonResponse
    {
        return $this->guard(fn () => $this->successResponse(
            $this->work->present($this->work->validate($this->work->find($id, false), $request->user())),
            'Attestation validée. Le secrétariat est prévenu pour l\'impression.'));
    }

    public function resend(Request $request, int $id): JsonResponse
    {
        return $this->guard(function () use ($id, $request) {
            $this->work->resend($this->work->find($id, false), $request->user());

            return $this->successResponse(null, 'Nouveau lien envoyé sur WhatsApp (valable ' . Work::TTL_DAYS . ' jours).');
        });
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['motif' => ['required', 'string', 'min:5', 'max:1000']], ['motif.required' => 'Indiquez le motif.']);

        return $this->guard(fn () => $this->successResponse(
            $this->work->present($this->work->cancel($this->work->find($id, false), trim(strip_tags($data['motif'])), $request->user())), 'Attestation annulée.'));
    }

    // ── Responsable et secrétariat ──────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in([...array_keys(Work::STATUS_LABELS), 'all'])],
            'q'      => ['nullable', 'string', 'max:100'],
        ]);
        $secretariat = $this->secretariat($request);

        return $this->guard(fn () => response()->json([
            'success' => true,
            'data'    => $this->work->staffList($data['status'] ?? ($secretariat ? 'a_imprimer' : 'a_valider'), $secretariat, $data['q'] ?? null)->values(),
            'counts'  => $this->work->counts($secretariat),
        ]));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->guard(fn () => $this->successResponse($this->work->present($this->work->find($id, $this->secretariat($request)))));
    }

    /** PDF (aperçu filigrané tant qu'elle n'est pas validée). */
    public function pdf(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return $this->guard(function () use ($id, $data, $request) {
            $a = $this->work->find($id, $this->secretariat($request));
            if ($a->status === 'annulee') {
                throw new BusinessException('Cette attestation est annulée.', 'STAGE_ATT_STATUS', 409);
            }
            $date = in_array($a->status, ['invitee', 'a_valider', 'a_imprimer'], true) ? ($data['date'] ?? null) : null;
            $f = $this->pdfs->workAttestation($a, $this->work->documentDate($a, $date));

            return response()->json(['name' => $f['name'], 'mime' => 'application/pdf', 'content' => base64_encode($f['content'])])
                ->header('Cache-Control', 'private, no-store');
        });
    }

    public function printed(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'numero' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9\/\-_. ]+$/'],
            'date'   => ['nullable', 'date_format:Y-m-d'],
        ]);

        return $this->guard(function () use ($id, $data, $request) {
            $a = $this->work->find($id, $this->secretariat($request));
            $first = $a->status === 'a_imprimer';
            $a = $this->work->markPrinted($a, $data['numero'] ?? null, $data['date'] ?? null, $request->user());

            return $this->successResponse($this->work->present($a), $first
                ? 'Attestation imprimée. Faites-la signer par le Chef CAP, puis prévenez l\'intéressé.' : 'Modification enregistrée.');
        });
    }

    public function notify(Request $request, int $id): JsonResponse
    {
        return $this->guard(fn () => $this->successResponse(
            $this->work->present($this->work->notifyReady($this->work->find($id, $this->secretariat($request)), $request->user())),
            'L\'intéressé est prévenu (WhatsApp et email) : il peut venir retirer son attestation.'));
    }

    public function delivered(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return $this->guard(fn () => $this->successResponse(
            $this->work->present($this->work->markDelivered($this->work->find($id, $this->secretariat($request)), $data['date'], $request->user())),
            'Remise enregistrée.'));
    }

    // ── Personne invitée (lien public) ──────────────────────────────────

    public function publicShow(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => LinkToken::RULE]);
        $a = $this->work->findByToken($data['token']);
        if (!$a) {
            return $this->errorResponse("Ce lien n'est plus valable. Demandez-en un nouveau au CAP-EPAC.", 410, 'STAGE_WORK_LINK');
        }

        return $this->successResponse($this->work->publicView($a));
    }

    public function publicSubmit(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => LinkToken::RULE, 'consentement' => ['accepted']] + $this->rules(true),
            $this->messages() + ['consentement.accepted' => 'Vous devez accepter le traitement de vos données.']);

        return $this->guard(function () use ($data) {
            $this->work->submit($data['token'], $data);

            return $this->successResponse(null, 'Merci ! Vos informations sont envoyées. Le CAP-EPAC les vérifie et vous prévient quand votre attestation est prête à retirer.', 201);
        });
    }

    // ── Outils ──────────────────────────────────────────────────────────

    /** Champs de la fiche ($required : formulaire de la personne). */
    private function rules(bool $required): array
    {
        $r = $required ? 'required' : 'sometimes';
        $text = fn (int $max) => [$r, $required ? 'string' : 'nullable', 'string', "max:{$max}"];

        return [
            'civilite'    => [$r, $required ? 'string' : 'nullable', Rule::in(['M.', 'Mme', 'Mlle'])],
            'nom'         => $text(80),
            'prenoms'     => $text(100),
            'email'       => [$r, $required ? 'string' : 'nullable', 'email', 'max:254'],
            'diplome'     => $text(150),
            'date_debut'  => [$r, $required ? 'string' : 'nullable', 'date_format:Y-m-d'],
            'date_fin'    => [$r, $required ? 'string' : 'nullable', 'date_format:Y-m-d'],
            'fonctions'   => [$r, 'array', ($required ? 'min:1' : 'min:0'), 'max:' . Work::MAX_FONCTIONS],
            'fonctions.*' => ['nullable', 'string', 'max:' . Work::MAX_FONCTION_LEN],
            'taches'      => ['sometimes', 'nullable', 'array', 'max:' . Work::MAX_TACHES],
            'taches.*'    => ['nullable', 'string', 'max:' . Work::MAX_TACHE_LEN],
        ];
    }

    private function messages(): array
    {
        return [
            'civilite.required' => 'Choisissez la civilité.', 'nom.required' => 'Indiquez le nom.', 'prenoms.required' => 'Indiquez les prénoms.',
            'email.required' => 'Indiquez l\'email.', 'email.email' => 'Email invalide.', 'diplome.required' => 'Indiquez le diplôme.',
            'date_debut.required' => 'Indiquez le début de la période.', 'date_fin.required' => 'Indiquez la fin de la période.',
            'fonctions.required' => 'Indiquez au moins une fonction.', 'fonctions.min' => 'Indiquez au moins une fonction.',
            'fonctions.max' => Work::MAX_FONCTIONS . ' fonctions au plus.', 'taches.max' => Work::MAX_TACHES . ' tâches au plus.',
            'fonctions.*.max' => 'Une fonction fait ' . Work::MAX_FONCTION_LEN . ' caractères au plus.',
            'taches.*.max' => 'Une tâche fait ' . Work::MAX_TACHE_LEN . ' caractères au plus.',
        ];
    }

    private function secretariat(Request $request): bool
    {
        return !StageAccess::isLead($request->user());
    }

    private function guard(callable $fn): JsonResponse
    {
        try {
            return $fn();
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }
    }
}
