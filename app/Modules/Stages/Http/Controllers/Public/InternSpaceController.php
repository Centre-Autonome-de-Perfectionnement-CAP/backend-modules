<?php

namespace App\Modules\Stages\Http\Controllers\Public;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageInternRequest;
use App\Modules\Stages\Services\AttendanceService;
use App\Modules\Stages\Services\InternActivationService;
use App\Modules\Stages\Services\InternAuthService;
use App\Modules\Stages\Services\InternRequestService;
use App\Modules\Stages\Services\InternWebAuthnService;
use App\Modules\Stages\Services\ReportService;
use App\Modules\Stages\Services\AttestationService;
use App\Modules\Stages\Models\StageReport;
use Illuminate\Support\Facades\Storage;
use App\Modules\Stages\Services\StageRequestService;
use App\Modules\Stages\Services\StageSettingsService;
use App\Modules\Stages\Support\BusinessHours;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Espace stagiaire (site vitrine) : activation, connexion, règlement,
 * empreinte, pointage, assiduité, demandes.
 */
class InternSpaceController extends Controller
{
    use ApiResponse;

    private const POSITION_RULES = [
        'position'          => ['required', 'array'],
        'position.lat'      => ['required', 'numeric', 'between:-90,90'],
        'position.lng'      => ['required', 'numeric', 'between:-180,180'],
        'position.accuracy' => ['required', 'numeric', 'min:0', 'max:100000'],
    ];

    public function __construct(
        private readonly InternAuthService $auth,
        private readonly InternActivationService $activation,
        private readonly InternWebAuthnService $webauthn,
        private readonly AttendanceService $attendance,
        private readonly InternRequestService $requests,
        private readonly StageRequestService $stages,
        private readonly StageSettingsService $settings,
        private readonly ReportService $reports,
        private readonly AttestationService $attestations,
    ) {}

    // ── Activation (lien du premier jour) ───────────────────────────────

    public function activationInfo(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:48', 'alpha_num']]);
        $req  = $this->activation->findByToken($data['token']);
        if (!$req) {
            return $this->errorResponse("Ce lien n'est plus valable. Si votre compte est déjà activé, connectez-vous.", 410, 'STAGE_ACTIVATION_INVALID');
        }

        return $this->successResponse([
            'reference'   => $req->reference,
            'nom_complet' => trim($req->civilite . ' ' . $req->fullName()),
            'lieu'        => $req->lieu,
            'checks_position' => (bool) $this->settings->get('lieu_position'),
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => ['required', 'string', 'size:48', 'alpha_num'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ] + self::POSITION_RULES, ['password.confirmed' => 'Les deux mots de passe ne correspondent pas.']);

        return $this->guard(function () use ($data, $request) {
            $account = $this->activation->activate($data['token'], $data['position'], $data['password']);

            return $this->successResponse(['token' => $this->auth->open($account, $request)], 'Présence confirmée. Bienvenue !', 201);
        });
    }

    // ── Connexion ───────────────────────────────────────────────────────

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['identifiant' => ['required', 'string', 'max:254'], 'password' => ['required', 'string', 'max:200']]);

        return $this->guard(function () use ($data, $request) {
            $account = $this->auth->login($data['identifiant'], $data['password']);

            return $this->successResponse(['token' => $this->auth->open($account, $request)]);
        });
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->close($request->header(InternAuthService::HEADER));

        return $this->successResponse(null, 'Déconnecté.');
    }

    public function resetCode(Request $request): JsonResponse
    {
        $data = $request->validate(['identifiant' => ['required', 'string', 'max:254']]);
        $this->auth->sendResetCode($data['identifiant']);

        return $this->successResponse(null, 'Si un compte correspond, un code a été envoyé sur votre WhatsApp et par email.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifiant' => ['required', 'string', 'max:254'],
            'code'        => ['required', 'string', 'regex:/^\d{6}$/'],
            'password'    => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['password.confirmed' => 'Les deux mots de passe ne correspondent pas.']);

        return $this->guard(function () use ($data, $request) {
            $account = $this->auth->resetPassword($data['identifiant'], $data['code'], $data['password']);

            return $this->successResponse(['token' => $this->auth->open($account, $request)], 'Mot de passe modifié.');
        });
    }

    // ── Espace (session stagiaire) ──────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $req     = $account->stage;
        $version = (int) $this->settings->get('reglement_version');
        $sup = DB::table('stage_supervisions as s')->join('users as u', 'u.id', '=', 's.user_id')
            ->where('s.stage_request_id', $req->id)->get(['u.first_name', 'u.last_name', 'u.phone']);

        return $this->successResponse([
            'reference'   => $req->reference,
            'nom_complet' => trim($req->civilite . ' ' . $req->fullName()),
            'prenoms'     => $req->prenoms,
            'terms'       => $this->stages->currentTerms($req) + [
                'date_debut_reelle' => $req->date_debut_reelle?->toDateString(),
                'duree' => $req->date_debut && $req->date_fin ? $this->stages->durationLabel($req->date_debut->toDateString(), $req->date_fin->toDateString()) : null,
            ],
            'notes'       => $req->notes()->get()->map(fn ($n) => ['id' => $n->id, 'number' => $n->number, 'kind' => $n->kind, 'issued_at' => $n->issued_at?->toIso8601String()]),
            'supervisors' => $sup->map(fn ($u) => ['name' => trim($u->first_name . ' ' . $u->last_name), 'phone' => $u->phone]),
            'team'        => $req->team_id ? DB::table('stage_teams')->where('id', $req->team_id)->value('name') : null,
            'reglement'   => [
                'version'  => $version,
                'text'     => $this->settings->get('reglement_text'),
                'accepted' => (int) $account->reglement_version === $version,
            ],
            'webauthn'    => ['enrolled' => $this->webauthn->hasCredential($account), 'rp_id' => $this->webauthn->rpId()],
            'today'       => $this->attendance->today($req),
            'summary'     => $this->attendance->report($req)['summary'],
            'position_checked' => (bool) $this->settings->get('lieu_position'),
        ]);
    }

    public function acceptReglement(Request $request): JsonResponse
    {
        $data = $request->validate(['version' => ['required', 'integer']]);
        $account = $this->account($request);
        $version = (int) $this->settings->get('reglement_version');
        if ((int) $data['version'] !== $version) {
            return $this->errorResponse('Le règlement a été mis à jour. Relisez-le puis acceptez-le.', 409, 'STAGE_REGLEMENT_OUTDATED');
        }
        $account->forceFill(['reglement_version' => $version, 'reglement_accepted_at' => now()])->save();
        DB::table('stage_request_history')->insert([
            'stage_request_id' => $account->stage_request_id, 'action' => 'reglement_accepted',
            'from_status' => $account->stage->status, 'to_status' => $account->stage->status,
            'actor' => 'demandeur', 'comment' => "Règlement intérieur accepté (version {$version})", 'created_at' => now(),
        ]);

        return $this->successResponse(null, 'Règlement accepté.');
    }

    public function webauthnRegisterOptions(Request $request): JsonResponse
    {
        return $this->guard(fn () => $this->successResponse($this->webauthn->registrationOptions($this->ready($request))));
    }

    public function webauthnRegister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'clientDataJSON'    => ['required', 'string', 'max:20000'],
            'attestationObject' => ['required', 'string', 'max:20000'],
            'label'             => ['nullable', 'string', 'max:120'],
        ]);

        return $this->guard(function () use ($data, $request) {
            $this->webauthn->register($this->ready($request), $data, $data['label'] ?? null);

            return $this->successResponse(null, 'Empreinte enregistrée. Vous pouvez maintenant pointer.');
        });
    }

    public function punchOptions(Request $request): JsonResponse
    {
        return $this->guard(fn () => $this->successResponse(
            $this->webauthn->assertionOptions($this->ready($request), $this->sessionKey($request)),
        ));
    }

    public function punch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind'                       => ['required', Rule::in(AttendanceService::KINDS)],
            'webauthn'                   => ['required', 'array'],
            'webauthn.id'                => ['required', 'string', 'max:1024'],
            'webauthn.clientDataJSON'    => ['required', 'string', 'max:20000'],
            'webauthn.authenticatorData' => ['required', 'string', 'max:20000'],
            'webauthn.signature'         => ['required', 'string', 'max:20000'],
        ] + self::POSITION_RULES);

        return $this->guard(function () use ($data, $request) {
            $account = $this->ready($request);
            try {
                $this->webauthn->verify($account, $this->sessionKey($request), $data['webauthn']);
            } catch (BusinessException $e) {
                $this->attendance->fail($account->stage, 'webauthn');
                throw $e;
            }
            $p = $this->attendance->punch($account->stage, $data['kind'], $data['position']);
            $msg = 'Pointage « ' . AttendanceService::LABELS[$p->kind] . ' » enregistré à ' . $p->at->setTimezone(BusinessHours::TZ)->format('H\hi')
                . ($p->late_minutes ? " (retard de {$p->late_minutes} min)" : '')
                . ($p->early ? ' (sortie anticipée)' : '') . '.';

            return $this->successResponse(['today' => $this->attendance->today($account->stage->fresh())], $msg, 201);
        });
    }

    public function attendance(Request $request): JsonResponse
    {
        return $this->successResponse($this->attendance->report($this->account($request)->stage));
    }

    public function requestsIndex(Request $request): JsonResponse
    {
        $req = $this->account($request)->stage;

        return $this->successResponse([
            'items' => StageInternRequest::where('stage_request_id', $req->id)->orderByDesc('id')->limit(100)->get()
                ->map(fn ($i) => $this->requests->present($i))->values(),
            'types' => InternRequestService::TYPES,
            'delay_days' => (int) $this->settings->get('permission_delay_days'),
        ]);
    }

    public function requestsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind'         => ['required', Rule::in(['permission', 'prolongation', 'raccourcissement'])],
            'type'         => ['required_if:kind,permission', 'nullable', Rule::in(array_keys(InternRequestService::TYPES))],
            'date_from'    => ['required_if:kind,permission', 'nullable', 'date_format:Y-m-d'],
            'date_to'      => ['nullable', 'date_format:Y-m-d'],
            'time_from'    => ['nullable', 'date_format:H:i'],
            'time_to'      => ['nullable', 'date_format:H:i'],
            'new_date_fin' => ['required_unless:kind,permission', 'nullable', 'date_format:Y-m-d'],
            'motif'        => ['required', 'string', 'min:3', 'max:1000'],
            'piece'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $data['motif'] = trim(strip_tags($data['motif']));

        return $this->guard(function () use ($data, $request) {
            $item = $this->requests->create($this->ready($request)->stage, $data, $request->file('piece'));

            return $this->successResponse($this->requests->present($item), 'Demande envoyée. Vous serez informé(e) de la réponse.', 201);
        });
    }

    public function requestsCancel(Request $request, int $id): JsonResponse
    {
        return $this->guard(function () use ($request, $id) {
            $this->requests->cancel($this->ready($request)->stage, $id);

            return $this->successResponse(null, 'Demande annulée.');
        });
    }

    // ── Notes de service ────────────────────────────────────────────────

    public function notePdf(Request $request, int $id): JsonResponse
    {
        $req  = $this->account($request)->stage;
        $note = \App\Modules\Stages\Models\StageNote::where('stage_request_id', $req->id)->findOrFail($id);
        $f = app(\App\Modules\Stages\Services\StagePdfService::class)->note($note);

        return response()->json(['name' => $f['name'], 'mime' => 'application/pdf', 'content' => base64_encode($f['content'])])
            ->header('Cache-Control', 'private, no-store');
    }

    // ── Attestation ─────────────────────────────────────────────────────

    public function attestation(Request $request): JsonResponse
    {
        return $this->successResponse($this->attestations->forIntern($this->account($request)->stage));
    }

    public function attestationRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'motif_type' => ['required', Rule::in(array_keys(AttestationService::MOTIF_TYPES))],
            'motif'      => ['nullable', 'string', 'max:1000'],
        ]);
        $motif = isset($data['motif']) ? (trim(strip_tags($data['motif'])) ?: null) : null;
        if ($data['motif_type'] === 'autre' && (!$motif || mb_strlen($motif) < 5)) {
            return $this->errorResponse('Précisez votre motif.', 422, 'STAGE_ATT_MOTIF');
        }

        return $this->guard(function () use ($request, $data, $motif) {
            $req = $this->ready($request)->stage;
            $this->attestations->internRequest($req, $data['motif_type'], $motif);

            return $this->successResponse($this->attestations->forIntern($req->fresh()),
                'Demande envoyée. Vous serez prévenu(e) dès que votre attestation sera prête.', 201);
        });
    }

    // ── Rapports ────────────────────────────────────────────────────────

    public function reportsIndex(Request $request): JsonResponse
    {
        return $this->successResponse($this->reports->overview($this->account($request)->stage));
    }

    public function reportsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period_key' => ['required', 'string', 'max:20', 'regex:/^(FINAL|J\d{4}-\d{2}-\d{2}|S\d{4}-W\d{2}|M\d{4}-\d{2})$/'],
            'fichier'    => ['required', 'file', 'mimes:pdf', 'max:' . ReportService::MAX_KB],
            'note'       => ['nullable', 'string', 'max:1000'],
        ], ['fichier.mimes' => 'Le rapport doit être un fichier PDF.', 'fichier.max' => 'Le rapport ne doit pas dépasser 10 Mo.']);
        $note = isset($data['note']) ? (trim(strip_tags($data['note'])) ?: null) : null;

        return $this->guard(function () use ($data, $note, $request) {
            $r = $this->reports->submit($this->ready($request)->stage, $data['period_key'], $request->file('fichier'), $note);

            return $this->successResponse($this->reports->detail($r->load('stage'), false),
                $r->late ? 'Rapport déposé (hors délai).' : 'Rapport déposé. Votre superviseur en est informé.', 201);
        });
    }

    public function reportShow(Request $request, int $id): JsonResponse
    {
        return $this->successResponse($this->reports->detail($this->ownReport($request, $id), false));
    }

    public function reportComment(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $report = $this->ownReport($request, $id);

        return $this->guard(function () use ($report, $data) {
            $this->reports->comment($report, $data['body'], null);

            return $this->successResponse($this->reports->detail($report->fresh('stage'), false), 'Message envoyé.');
        });
    }

    public function reportFile(Request $request, int $id, int $version): JsonResponse
    {
        $report = $this->ownReport($request, $id);
        $path = $this->reports->filePath($report, $version);
        if (!$path) {
            return $this->errorResponse('Fichier introuvable.', 404, 'STAGE_FILE_NOT_FOUND');
        }

        return response()->json([
            'name' => $this->reports->labelFor($report) . " - v{$version}.pdf", 'mime' => 'application/pdf',
            'content' => base64_encode(Storage::disk(StageRequestService::DISK)->get($path)),
        ])->header('Cache-Control', 'private, no-store');
    }

    // ── Interne ─────────────────────────────────────────────────────────

    private function ownReport(Request $request, int $id): StageReport
    {
        $report = StageReport::with('stage')->where('id', $id)->where('stage_request_id', $this->account($request)->stage_request_id)->first();
        if (!$report) {
            abort(404);
        }

        return $report;
    }

    private function account(Request $request): StageInternAccount
    {
        return $request->attributes->get('intern');
    }

    /** Compte dont le règlement en vigueur est accepté. */
    private function ready(Request $request): StageInternAccount
    {
        $account = $this->account($request);
        if ((int) $account->reglement_version !== (int) $this->settings->get('reglement_version')) {
            throw new BusinessException('Lisez et acceptez d\'abord le règlement intérieur.', 'STAGE_REGLEMENT_REQUIRED', 403);
        }

        return $account;
    }

    private function sessionKey(Request $request): string
    {
        return hash('sha256', (string) $request->header(InternAuthService::HEADER));
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
