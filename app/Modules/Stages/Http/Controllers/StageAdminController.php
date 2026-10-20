<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\StageRequestService;
use App\Modules\Stages\Services\StageNotifier;
use App\Modules\Stages\Services\StageSettingsService;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Progiciel : gestion des demandes de stage par le Responsable de division
 * (formation continue) et l'administrateur. Accès : gate `stages.manage`.
 */
class StageAdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly StageRequestService $service,
        private readonly StageSettingsService $settings,
        private readonly StageNotifier $notifier,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status'   => ['nullable', Rule::in(array_merge(array_keys(StageStatus::LABELS), ['all', 'to_handle']))],
            'q'        => ['nullable', 'string', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = StageRequest::query();
        $status = $data['status'] ?? 'to_handle';
        if ($status === 'to_handle') {
            $query->whereIn('status', [StageStatus::SUBMITTED, StageStatus::COUNTER_PROPOSED]);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if (!empty($data['q'])) {
            $q = '%' . addcslashes(trim($data['q']), '%_\\') . '%';
            $query->where(fn ($w) => $w->where('reference', 'like', $q)->orWhere('nom', 'like', $q)
                ->orWhere('prenoms', 'like', $q)->orWhere('email', 'like', $q)->orWhere('whatsapp', 'like', $q)
                ->orWhere('etablissement_origine', 'like', $q));
        }

        $page = $query->orderByRaw('CASE WHEN status IN (?, ?) THEN 0 ELSE 1 END', [StageStatus::SUBMITTED, StageStatus::COUNTER_PROPOSED])
            ->orderByDesc('updated_at')
            ->paginate($data['per_page'] ?? 20);

        $counts = StageRequest::query()->select('status', DB::raw('COUNT(*) as n'))->groupBy('status')->pluck('n', 'status');

        return response()->json([
            'success' => true,
            'data'    => collect($page->items())->map(fn (StageRequest $r) => $this->listRow($r))->values(),
            'meta'    => [
                'total' => $page->total(), 'per_page' => $page->perPage(),
                'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
            ],
            'counts'  => array_merge(array_fill_keys(array_keys(StageStatus::LABELS), 0), $counts->map(fn ($n) => (int) $n)->all()),
            'labels'  => StageStatus::LABELS,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $req = $this->findOrFail($id);

        return $this->successResponse($this->detail($req));
    }

    /** Pièce du demandeur : flux inline, ou JSON base64 (?format=json) pour le visualiseur. */
    public function file(Request $request, string $id, string $key)
    {
        if (!array_key_exists($key, StageRequestService::FILE_KEYS)) {
            return $this->errorResponse('Pièce inconnue.', 404, 'STAGE_FILE_NOT_FOUND');
        }
        $req  = $this->findOrFail($id);
        $path = $this->service->filePath($req, $key);
        if (!$path) {
            return $this->errorResponse('Fichier introuvable.', 404, 'STAGE_FILE_NOT_FOUND');
        }

        $disk = Storage::disk(StageRequestService::DISK);
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $name = StageRequestService::FILE_KEYS[$key] . ' - ' . $req->reference . '.' . pathinfo($path, PATHINFO_EXTENSION);

        if ($request->query('format') === 'json') {
            return response()->json([
                'name' => $name, 'mime' => $mime, 'content' => base64_encode($disk->get($path)),
            ])->header('Cache-Control', 'private, no-store');
        }

        return response($disk->get($path), 200, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline; filename="' . addcslashes($name, '"\\') . '"',
            'Cache-Control'          => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function propose(Request $request, string $id): JsonResponse
    {
        $terms = $this->validatedTerms($request);
        $message = $this->cleanText($request->validate(['message' => ['nullable', 'string', 'max:1000']])['message'] ?? null);

        return $this->act(fn () => $this->service->propose($this->findOrFail($id), $terms, $message, $request->user()), function (StageRequest $r) {
            return $r->status === StageStatus::ACCEPTED
                ? 'Les dates correspondent à la demande : la note de service a été émise et envoyée.'
                : 'Proposition envoyée au demandeur par WhatsApp et email.';
        });
    }

    public function acceptCounter(Request $request, string $id): JsonResponse
    {
        return $this->act(
            fn () => $this->service->acceptCounter($this->findOrFail($id), $request->user()),
            fn () => 'Contre-proposition acceptée : la note de service a été émise et envoyée.',
        );
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $motif = $this->cleanText($request->validate(['motif' => ['required', 'string', 'max:1000']])['motif']);
        if (!$motif) {
            throw ValidationException::withMessages(['motif' => 'Le motif est obligatoire.']);
        }

        return $this->act(
            fn () => $this->service->reject($this->findOrFail($id), $motif, $request->user()),
            fn () => 'Demande non retenue. Le demandeur a été informé avec le motif.',
        );
    }

    public function rectify(Request $request, string $id): JsonResponse
    {
        $terms = $this->validatedTerms($request);
        $motif = $this->cleanText($request->validate(['motif' => ['required', 'string', 'max:1000']])['motif']);
        if (!$motif) {
            throw ValidationException::withMessages(['motif' => 'Le motif est obligatoire.']);
        }

        return $this->act(
            fn () => $this->service->rectify($this->findOrFail($id), $terms, $motif, $request->user()),
            fn () => 'Note de service rectificative émise et envoyée au stagiaire.',
        );
    }

    public function getSettings(): JsonResponse
    {
        return $this->successResponse($this->settings->all());
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requests_open'            => ['sometimes', 'boolean'],
            'closed_message'           => ['sometimes', 'string', 'min:5', 'max:500'],
            'default_rejection_reason' => ['sometimes', 'string', 'min:5', 'max:1000'],
            'default_service'          => ['sometimes', 'string', 'min:2', 'max:150'],
            'default_lieu'             => ['sometimes', 'string', 'min:2', 'max:150'],
            'default_horaires'         => ['sometimes', 'array'],
            'default_horaires.arrivee'     => ['required_with:default_horaires', 'date_format:H:i'],
            'default_horaires.pause_debut' => ['required_with:default_horaires', 'date_format:H:i'],
            'default_horaires.pause_fin'   => ['required_with:default_horaires', 'date_format:H:i'],
            'default_horaires.sortie'      => ['required_with:default_horaires', 'date_format:H:i'],
            'lieu_position'            => ['sometimes', 'nullable', 'array'],
            'lieu_position.lat'        => ['required_with:lieu_position', 'numeric', 'between:-90,90'],
            'lieu_position.lng'        => ['required_with:lieu_position', 'numeric', 'between:-180,180'],
            'lieu_rayon'               => ['sometimes', 'integer', 'min:20', 'max:500'],
            'reglement_text'           => ['sometimes', 'string', 'min:50', 'max:10000'],
            'permission_delay_days'    => ['sometimes', 'integer', 'min:0', 'max:15'],
            'report_frequency'         => ['sometimes', Rule::in(\App\Modules\Stages\Support\ReportPlan::FREQUENCIES)],
            'report_grace_days'        => ['sometimes', 'integer', 'min:0', 'max:10'],
            'final_report_open_days'   => ['sometimes', 'integer', 'min:3', 'max:60'],
        ]);
        if (array_key_exists('lieu_position', $data) && $data['lieu_position'] !== null) {
            $data['lieu_position'] = ['lat' => round((float) $data['lieu_position']['lat'], 7), 'lng' => round((float) $data['lieu_position']['lng'], 7)];
        }
        if (isset($data['reglement_text'])) {
            $data['reglement_text'] = trim(strip_tags($data['reglement_text']));
        }

        foreach (['closed_message', 'default_rejection_reason', 'default_service', 'default_lieu'] as $k) {
            if (isset($data[$k])) {
                $data[$k] = $this->cleanText($data[$k]);
            }
        }
        if (isset($data['default_horaires'])) {
            $data['default_horaires'] = $this->orderedHoraires($data['default_horaires'], 'default_horaires');
        }

        return $this->successResponse($this->settings->update($data, $request->user()?->id), 'Réglages enregistrés.');
    }

    // ── Interne ─────────────────────────────────────────────────────────

    private function act(callable $action, callable $message): JsonResponse
    {
        try {
            $req = $action();
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        $text = $message($req);

        // Action d'un superviseur : le responsable de division en est informé.
        $user = request()->user();
        if ($user && !StageAccess::isLead($user)) {
            $who = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $this->notifier->toManagers($req, 'Action d\'un superviseur', [
                "{$who} (superviseur) a agi sur cette demande de stage : {$text}",
            ], ['Référence' => $req->reference, 'Demandeur' => $req->fullName(), 'Statut' => StageStatus::label($req->status)], 'stage-superviseur');
        }

        return $this->successResponse($this->detail($req->fresh()), $text);
    }

    private function validatedTerms(Request $request): array
    {
        $d = $request->validate([
            'service'              => ['required', 'string', 'min:2', 'max:150'],
            'lieu'                 => ['required', 'string', 'min:2', 'max:150'],
            'date_debut'           => ['required', 'date_format:Y-m-d'],
            'date_fin'             => ['required', 'date_format:Y-m-d'],
            'horaires'             => ['required', 'array'],
            'horaires.arrivee'     => ['required', 'date_format:H:i'],
            'horaires.pause_debut' => ['required', 'date_format:H:i'],
            'horaires.pause_fin'   => ['required', 'date_format:H:i'],
            'horaires.sortie'      => ['required', 'date_format:H:i'],
        ]);

        return [
            'service'    => $this->cleanText($d['service']),
            'lieu'       => $this->cleanText($d['lieu']),
            'date_debut' => $d['date_debut'],
            'date_fin'   => $d['date_fin'],
            'horaires'   => $this->orderedHoraires($d['horaires'], 'horaires'),
        ];
    }

    private function orderedHoraires(array $h, string $field): array
    {
        $h = [
            'arrivee' => $h['arrivee'], 'pause_debut' => $h['pause_debut'],
            'pause_fin' => $h['pause_fin'], 'sortie' => $h['sortie'],
        ];
        if (!($h['arrivee'] < $h['pause_debut'] && $h['pause_debut'] <= $h['pause_fin'] && $h['pause_fin'] < $h['sortie'])) {
            throw ValidationException::withMessages([$field => "Les horaires doivent se suivre : arrivée, début de pause, fin de pause, sortie."]);
        }

        return $h;
    }

    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(strip_tags($value));

        return $value === '' ? null : $value;
    }

    private function findOrFail(string $id): StageRequest
    {
        return ctype_digit($id)
            ? StageRequest::findOrFail((int) $id)
            : StageRequest::where('reference', strtoupper($id))->firstOrFail();
    }

    private function listRow(StageRequest $r): array
    {
        return [
            'id'            => $r->id,
            'reference'     => $r->reference,
            'status'        => $r->status,
            'status_label'  => StageStatus::label($r->status),
            'nom_complet'   => trim($r->civilite . ' ' . $r->fullName()),
            'etablissement' => $r->etablissement_origine,
            'formation'     => $r->filiere_niveau,
            'date_debut_souhaitee' => $r->date_debut_souhaitee?->toDateString(),
            'duree_souhaitee' => $this->service->dureeLabel($r->duree_souhaitee),
            'date_debut'    => $r->date_debut?->toDateString(),
            'date_fin'      => $r->date_fin?->toDateString(),
            'created_at'    => $r->created_at?->toIso8601String(),
            'updated_at'    => $r->updated_at?->toIso8601String(),
        ];
    }

    private function detail(StageRequest $r): array
    {
        $r->load(['proposals', 'notes', 'history']);
        $userIds = $r->proposals->pluck('user_id')->merge($r->history->pluck('user_id'))
            ->merge($r->notes->pluck('issued_by'))->filter()->unique();
        $names = $userIds->isEmpty() ? collect() : DB::table('users')->whereIn('id', $userIds)
            ->get(['id', 'first_name', 'last_name'])->mapWithKeys(fn ($u) => [$u->id => trim($u->first_name . ' ' . $u->last_name)]);

        $files = [];
        foreach (StageRequestService::FILE_KEYS as $key => $label) {
            if ($this->service->filePath($r, $key)) {
                $files[] = ['key' => $key, 'label' => $label, 'ext' => strtolower(pathinfo($r->files[$key], PATHINFO_EXTENSION))];
            }
        }

        $open = in_array($r->status, StageStatus::OPEN_FOR_DECISION, true);

        return $this->listRow($r) + [
            'civilite'        => $r->civilite,
            'nom'             => $r->nom,
            'prenoms'         => $r->prenoms,
            'date_naissance'  => $r->date_naissance?->toDateString(),
            'lieu_naissance'  => $r->lieu_naissance,
            'nationalite'     => $r->nationalite,
            'theme_souhaite'  => $r->theme_souhaite,
            'email'           => $r->email,
            'whatsapp'        => $r->whatsapp,
            'files'           => $files,
            'terms'           => $r->date_debut ? $this->service->currentTerms($r) : null,
            'date_debut_reelle' => $r->date_debut_reelle?->toDateString(),
            'activated_at'    => $r->activated_at?->toIso8601String(),
            'activation_sent_at' => $r->activation_sent_at?->toIso8601String(),
            'rejection_reason' => $r->rejection_reason,
            'proposal_sent_at' => $r->proposal_sent_at?->toIso8601String(),
            'proposal_expires_at' => $r->status === StageStatus::PROPOSAL_SENT
                ? $r->proposal_sent_at?->copy()->addDays((int) $this->settings->get('proposal_expiry_days'))->toIso8601String() : null,
            'exchanges'       => $r->proposals->map(fn ($p) => [
                'id' => $p->id, 'author' => $p->author, 'author_name' => $p->user_id ? ($names[$p->user_id] ?? null) : null,
                'kind' => $p->kind, 'service' => $p->service, 'lieu' => $p->lieu,
                'date_debut' => $p->date_debut?->toDateString(), 'date_fin' => $p->date_fin?->toDateString(),
                'horaires' => $p->horaires, 'message' => $p->message, 'at' => $p->created_at?->toIso8601String(),
            ])->values(),
            'notes'           => $r->notes->map(fn ($n) => [
                'id' => $n->id, 'number' => $n->number, 'kind' => $n->kind, 'version' => $n->version,
                'motif' => $n->motif, 'issued_by' => $n->issued_by ? ($names[$n->issued_by] ?? null) : null,
                'issued_at' => $n->issued_at?->toIso8601String(), 'has_pdf' => (bool) $n->pdf_path,
            ])->values(),
            'history'         => $r->history->map(fn ($h) => [
                'id' => $h->id, 'action' => $h->action, 'from_status' => $h->from_status, 'to_status' => $h->to_status,
                'actor' => $h->actor, 'user_name' => $h->user_id ? ($names[$h->user_id] ?? null) : null,
                'comment' => $h->comment, 'meta' => $h->meta, 'at' => $h->created_at?->toIso8601String(),
            ])->values(),
            'suggested_terms' => $open ? $this->service->suggestedTerms($r) : null,
            'default_rejection_reason' => $this->settings->get('default_rejection_reason'),
            'can' => [
                'propose'        => $open,
                'reject'         => $open,
                'accept_counter' => $r->status === StageStatus::COUNTER_PROPOSED,
                'rectify'        => $r->status === StageStatus::ACCEPTED,
            ],
        ];
    }
}
