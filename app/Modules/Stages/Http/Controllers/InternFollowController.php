<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageInternRequest;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\AttendanceService;
use App\Modules\Stages\Services\InternActivationService;
use App\Modules\Stages\Services\InternAuthService;
use App\Modules\Stages\Services\InternRequestService;
use App\Modules\Stages\Services\InternWebAuthnService;
use App\Modules\Stages\Services\StageNotifier;
use App\Modules\Stages\Services\StageRequestService;
use App\Modules\Stages\Support\StageAccess;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Progiciel : assiduité des stagiaires, appareil (empreinte), demandes des
 * stagiaires (permissions, prolongations, raccourcissements).
 * Accès : `stages.manage` (responsables + superviseurs).
 */
class InternFollowController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly InternWebAuthnService $webauthn,
        private readonly InternAuthService $auth,
        private readonly InternRequestService $requests,
        private readonly InternActivationService $activation,
        private readonly StageNotifier $notifier,
    ) {}

    public function presence(int $id): JsonResponse
    {
        $req     = StageRequest::findOrFail($id);
        $account = StageInternAccount::where('stage_request_id', $req->id)->first();
        $report  = $this->attendance->report($req);

        return $this->successResponse([
            'account' => $account ? [
                'activated_at'          => $req->activated_at?->toIso8601String(),
                'last_login_at'         => $account->last_login_at?->toIso8601String(),
                'reglement_accepted_at' => $account->reglement_accepted_at?->toIso8601String(),
                'reglement_version'     => $account->reglement_version,
                'devices' => DB::table('stage_webauthn_credentials')->where('account_id', $account->id)->orderByDesc('id')
                    ->get(['id', 'device_label', 'created_at', 'last_used_at', 'revoked_at'])->values(),
            ] : null,
            'activation' => [
                'sent_at'   => $req->activation_sent_at?->toIso8601String(),
                'reminders' => (int) $req->activation_reminders,
            ],
            'date_debut_reelle' => $req->date_debut_reelle?->toDateString(),
            'shift' => $req->date_debut_reelle && $req->date_debut && $req->date_debut_reelle->toDateString() !== $req->date_debut->toDateString() ? [
                'planned' => $req->date_debut->toDateString(),
                'real'    => $req->date_debut_reelle->toDateString(),
                'suggested_end' => $this->activation->shiftedEnd($req),
                'applied' => $this->activation->shiftedEnd($req) === $req->date_fin?->toDateString()
                    || StageHistory::where('stage_request_id', $req->id)->where('action', 'rectified')
                        ->where('created_at', '>=', $req->activated_at)->exists(),
            ] : null,
            'today'    => $req->date_debut ? $this->attendance->today($req) : null,
            'summary'  => $report['summary'],
            'seuil'    => (int) app(\App\Modules\Stages\Services\StageSettingsService::class)->get('seuil_assiduite'),
            'days'     => $report['days'],
            'failures' => DB::table('stage_attendance_failures')->where('stage_request_id', $req->id)
                ->where('created_at', '>=', now()->subDays(30))->groupBy('reason')
                ->pluck(DB::raw('COUNT(*)'), 'reason'),
        ]);
    }

    /**
     * Changement de téléphone : le téléphone de stage est désactivé et les
     * sessions fermées ; le stagiaire enregistre le nouveau à sa connexion.
     */
    public function resetDevice(Request $request, int $id): JsonResponse
    {
        $req     = StageRequest::findOrFail($id);
        $account = StageInternAccount::where('stage_request_id', $req->id)->first();
        if (!$account) {
            return $this->errorResponse("Ce stagiaire n'a pas encore activé son espace.", 409, 'STAGE_NO_ACCOUNT');
        }
        $n = $this->webauthn->revokeAll($account->id, $request->user()->id);
        if ($n === 0) {
            return $this->errorResponse('Aucun téléphone de stage à réinitialiser.', 409, 'STAGE_NO_DEVICE');
        }
        $this->auth->closeAll($account);
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => 'device_reset', 'from_status' => $req->status,
            'to_status' => $req->status, 'actor' => 'staff', 'user_id' => $request->user()->id,
            'comment' => 'Téléphone de stage réinitialisé (changement de téléphone)',
        ]);

        $this->notifier->toDemandeur($req, 'Téléphone de stage réinitialisé', [
            'Votre téléphone de stage a été réinitialisé. Connectez-vous depuis votre nouveau téléphone et enregistrez-le avec votre empreinte : il deviendra votre seul téléphone de stage.',
        ], [], ctaLabel: 'Ouvrir mon espace', ctaUrl: $this->notifier->spaceUrl(), context: 'stage-appareil');

        if (!StageAccess::isLead($request->user())) {
            $who = trim($request->user()->first_name . ' ' . $request->user()->last_name);
            $this->notifier->toManagers($req, 'Action d\'un superviseur', ["{$who} (superviseur) a réinitialisé le téléphone de stage du stagiaire."],
                ['Stagiaire' => $req->fullName()], 'stage-superviseur');
        }

        return $this->successResponse(null, 'Téléphone de stage réinitialisé. Le stagiaire a été prévenu.');
    }

    public function requestsIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'cancelled', 'all'])],
            'mine'   => ['nullable', 'boolean'],
        ]);
        $q = StageInternRequest::with('stage')->orderByDesc('id');
        if (($data['status'] ?? 'pending') !== 'all') {
            $q->where('status', $data['status'] ?? 'pending');
        }
        if (!empty($data['mine'])) {
            $q->whereIn('stage_request_id', DB::table('stage_supervisions')->where('user_id', $request->user()->id)->select('stage_request_id'));
        }

        return response()->json([
            'success' => true,
            'data'    => $q->limit(300)->get()->map(fn ($i) => $this->requests->present($i, true))->values(),
            'pending' => StageInternRequest::where('status', 'pending')->count(),
        ]);
    }

    public function decide(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment'  => ['nullable', 'string', 'max:1000'],
        ]);
        $comment = isset($data['comment']) ? (trim(strip_tags($data['comment'])) ?: null) : null;

        try {
            $item = $this->requests->decide($id, $data['decision'], $comment, $request->user());
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return $this->successResponse(
            $this->requests->present($item->load('stage'), true),
            $data['decision'] === 'approved'
                ? ($item->kind === 'permission' ? 'Permission accordée. Le stagiaire a été prévenu.' : 'Demande acceptée : note rectificative émise et envoyée.')
                : 'Demande refusée. Le stagiaire a été prévenu.',
        );
    }

    public function requestFile(Request $request, int $id)
    {
        $item = StageInternRequest::with('stage')->findOrFail($id);
        $path = $this->requests->filePath($item);
        if (!$path) {
            return $this->errorResponse('Fichier introuvable.', 404, 'STAGE_FILE_NOT_FOUND');
        }
        $disk = Storage::disk(StageRequestService::DISK);
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $name = 'Justificatif - ' . $item->stage->reference . '.' . pathinfo($path, PATHINFO_EXTENSION);

        return response()->json(['name' => $name, 'mime' => $mime, 'content' => base64_encode($disk->get($path))])
            ->header('Cache-Control', 'private, no-store');
    }
}
