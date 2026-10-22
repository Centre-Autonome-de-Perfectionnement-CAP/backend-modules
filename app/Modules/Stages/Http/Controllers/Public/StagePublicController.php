<?php

namespace App\Modules\Stages\Http\Controllers\Public;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\StageRequestService;
use App\Modules\Stages\Services\StageSettingsService;
use App\Modules\Stages\Services\StageSuiviOtpService;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Site vitrine : dépôt d'une demande de stage, suivi par référence + code,
 * réponse du demandeur à une proposition.
 */
class StagePublicController extends Controller
{
    use ApiResponse;

    private const REF_RULE = ['required', 'string', 'max:20', 'regex:/^STG-[A-Za-z0-9]{6}$/'];
    private const FILE_RULE = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

    public function __construct(
        private readonly StageRequestService $service,
        private readonly StageSettingsService $settings,
        private readonly StageSuiviOtpService $otp,
    ) {}

    /** Le formulaire est-il ouvert ? */
    public function status(): JsonResponse
    {
        $open = (bool) $this->settings->get('requests_open');

        return $this->successResponse([
            'open'    => $open,
            'message' => $open ? null : $this->settings->get('closed_message'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $text = fn (int $max, bool $required = true) => [$required ? 'required' : 'nullable', 'string', "max:{$max}"];

        $data = $request->validate([
            'civilite'              => ['required', Rule::in(['M.', 'Mlle', 'Mme'])],
            'nom'                   => [...$text(80), 'min:2'],
            'prenoms'               => $text(100),
            'date_naissance'        => ['required', 'date_format:Y-m-d', 'before:-10 years', 'after:1940-01-01'],
            'lieu_naissance'        => $text(100),
            'nationalite'           => $text(60),
            'etablissement_origine' => $text(150),
            'filiere_niveau'        => $text(150),
            'date_debut_souhaitee'  => ['nullable', 'date_format:Y-m-d', 'after_or_equal:' . BusinessHours::today()->toDateString()],
            'theme_souhaite'        => $text(200, false),
            'email'                 => ['required', 'email', 'max:254'],
            'telephone'             => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-.]{8,20}$/'],
            'duree_stage'           => $text(60),
            'consentement'          => ['accepted'],
            'cv'                    => self::FILE_RULE,
            'lettre_motivation'     => self::FILE_RULE,
            'acte_naissance'        => self::FILE_RULE,
        ], [
            'consentement.accepted'    => 'Vous devez accepter le traitement de vos données.',
            'date_naissance.before'    => 'Date de naissance invalide.',
            'date_debut_souhaitee.after_or_equal' => 'La date de début ne peut pas être passée.',
            'telephone.regex'          => 'Numéro de téléphone invalide.',
            '*.mimes'                  => 'Format accepté : PDF, JPG ou PNG.',
            '*.max'                    => 'Ce champ est trop long ou ce fichier dépasse 5 Mo.',
        ]);

        foreach (['nom', 'prenoms', 'lieu_naissance', 'nationalite', 'etablissement_origine', 'filiere_niveau', 'theme_souhaite', 'duree_stage'] as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim(strip_tags((string) $data[$k]));
            }
        }

        try {
            $req = $this->service->submit($data, [
                'cv'                => $request->file('cv'),
                'lettre_motivation' => $request->file('lettre_motivation'),
                'acte_naissance'    => $request->file('acte_naissance'),
            ]);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return $this->successResponse(
            ['reference' => $req->reference, 'tracking_code' => $req->reference],
            'Votre demande de stage a bien été envoyée. Vous recevrez la réponse par WhatsApp et par email.',
            201,
        );
    }

    public function sendCode(Request $request): JsonResponse
    {
        $data = $request->validate(['reference' => self::REF_RULE]);
        $req  = $this->find($data['reference']);
        if (!$req) {
            return $this->errorResponse('Aucune demande de stage ne correspond à cette référence.', 404, 'STAGE_NOT_FOUND');
        }

        $result = $this->otp->send($req);

        return $this->successResponse($result, $result['sent'] ? 'Code envoyé.' : 'Patientez avant de demander un nouveau code.');
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => self::REF_RULE,
            'code'      => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);
        $ref    = strtoupper(trim($data['reference']));
        $result = $this->otp->verify($ref, $data['code']);

        if (!$result['ok']) {
            return $this->errorResponse($result['message'], 422, 'STAGE_BAD_CODE');
        }

        return $this->successResponse(['token' => $result['token'], 'expires_in' => $result['expires_in']]);
    }

    public function show(Request $request): JsonResponse
    {
        $request->validate(['reference' => self::REF_RULE]);
        $req = $this->authorized($request);
        if ($req instanceof JsonResponse) {
            return $req;
        }

        return $this->successResponse($this->publicPayload($req));
    }

    public function respond(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference'  => self::REF_RULE,
            'action'     => ['required', Rule::in(['accept', 'counter'])],
            'date_debut' => ['required_if:action,counter', 'nullable', 'date_format:Y-m-d'],
            'date_fin'   => ['required_if:action,counter', 'nullable', 'date_format:Y-m-d'],
            'message'    => ['nullable', 'string', 'max:1000'],
        ]);
        $req = $this->authorized($request);
        if ($req instanceof JsonResponse) {
            return $req;
        }

        try {
            $req = $data['action'] === 'accept'
                ? $this->service->respondAccept($req)
                : $this->service->respondCounter(
                    $req, $data['date_debut'], $data['date_fin'],
                    isset($data['message']) ? (trim(strip_tags($data['message'])) ?: null) : null,
                );
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return $this->successResponse(
            $this->publicPayload($req),
            $data['action'] === 'accept'
                ? 'Merci ! Votre stage est confirmé : la note de service vous a été envoyée.'
                : 'Vos dates ont été transmises au responsable de division.',
        );
    }

    // ── Interne ─────────────────────────────────────────────────────────

    private function find(string $reference): ?StageRequest
    {
        return StageRequest::where('reference', strtoupper(trim($reference)))->first();
    }

    private function authorized(Request $request): StageRequest|JsonResponse
    {
        $ref = strtoupper(trim((string) $request->input('reference')));
        if (!$this->otp->check($request->header('X-Suivi-Token'), $ref)) {
            return $this->errorResponse('Session expirée. Demandez un nouveau code.', 401, 'STAGE_SUIVI_SESSION');
        }
        $req = $this->find($ref);

        return $req ?? $this->errorResponse('Demande introuvable.', 404, 'STAGE_NOT_FOUND');
    }

    private function publicPayload(StageRequest $req): array
    {
        $req->loadMissing(['proposals', 'notes']);
        $lastStaff = $req->proposals->where('author', 'staff')->sortByDesc('id')->first();
        $expiry    = (int) $this->settings->get('proposal_expiry_days');

        $termsOf = fn ($p) => $p ? [
            'service'    => $p->service,
            'lieu'       => $p->lieu,
            'date_debut' => $p->date_debut?->toDateString(),
            'date_fin'   => $p->date_fin?->toDateString(),
            'horaires'   => $p->horaires,
            'duree'      => ($p->date_debut && $p->date_fin)
                ? $this->service->durationLabel($p->date_debut->toDateString(), $p->date_fin->toDateString()) : null,
        ] : null;

        return [
            'reference'      => $req->reference,
            'status'         => $req->status,
            'status_label'   => StageStatus::publicLabel($req->status),
            'nom_complet'    => trim($req->civilite . ' ' . $req->fullName()),
            'submitted_at'   => $req->created_at?->toIso8601String(),
            'souhait'        => [
                'date_debut' => $req->date_debut_souhaitee?->toDateString(),
                'duree'      => $this->service->dureeLabel($req->duree_souhaitee),
                'theme'      => $req->theme_souhaite,
            ],
            'proposal'       => $req->status === StageStatus::PROPOSAL_SENT ? array_merge($termsOf($lastStaff) ?? [], [
                'message'    => $lastStaff?->message,
                'sent_at'    => $req->proposal_sent_at?->toIso8601String(),
                'expires_at' => $req->proposal_sent_at?->copy()->addDays($expiry)->toIso8601String(),
            ]) : null,
            'terms'          => $req->status === StageStatus::ACCEPTED ? $termsOf($req) : null,
            'notes'          => $req->status === StageStatus::ACCEPTED ? $req->notes->map(fn ($n) => [
                'number' => $n->number, 'kind' => $n->kind, 'motif' => $n->motif,
                'issued_at' => $n->issued_at?->toIso8601String(),
            ])->values() : [],
            'rejection_reason' => $req->status === StageStatus::REJECTED ? $req->rejection_reason : null,
            'exchanges'      => $req->proposals->map(fn ($p) => [
                'author'     => $p->author,
                'kind'       => $p->kind,
                'date_debut' => $p->date_debut?->toDateString(),
                'date_fin'   => $p->date_fin?->toDateString(),
                'message'    => $p->message,
                'at'         => $p->created_at?->toIso8601String(),
            ])->values(),
            'can_respond'    => $req->status === StageStatus::PROPOSAL_SENT,
        ];
    }
}
