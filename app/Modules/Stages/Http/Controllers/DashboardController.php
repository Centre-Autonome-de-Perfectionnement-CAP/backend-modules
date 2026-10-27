<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\DashboardService;
use App\Modules\Stages\Services\ExportService;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Progiciel : tableau de bord, suivi global et exports Excel. */
class DashboardController extends Controller
{
    use ApiResponse;

    public const SCOPES = ['active', 'current', 'upcoming', 'finished', 'all', 'mine', 'unsupervised'];

    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly ExportService $exports,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        return $this->successResponse($this->dashboard->overview($request->user()));
    }

    public function follow(Request $request): JsonResponse
    {
        $data = $this->followFilters($request);

        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->dashboard->follow($data['scope'], $data['q'], $user),
            'counts'  => app(\App\Modules\Stages\Services\SupervisionService::class)->counts($user),
            'me'      => SupervisionController::me($user),
            'seuil'   => (int) app(\App\Modules\Stages\Services\StageSettingsService::class)->get('seuil_assiduite'),
        ]);
    }

    public function exportInterns(Request $request): JsonResponse
    {
        $data = $this->followFilters($request);

        return $this->file($this->exports->stagiaires($data['scope'], $data['q'], $request->user()));
    }

    public function exportPresences(int $id): JsonResponse
    {
        $req = StageRequest::where('status', StageStatus::ACCEPTED)->findOrFail($id);

        return $this->file($this->exports->presences($req));
    }

    public function exportDemandes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(array_keys(StageStatus::LABELS), ['all', 'to_handle']))],
        ]);

        return $this->file($this->exports->demandes($data['status'] ?? 'all'));
    }

    public function exportAttestations(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['a_valider', 'a_imprimer', 'imprimee', 'remise', 'annulee', 'all'])],
        ]);

        return $this->file($this->exports->attestations($data['status'] ?? 'all', !StageAccess::isLead($request->user())));
    }

    private function followFilters(Request $request): array
    {
        $data = $request->validate([
            'scope' => ['nullable', Rule::in(self::SCOPES)],
            'q'     => ['nullable', 'string', 'max:100'],
        ]);

        return ['scope' => $data['scope'] ?? 'active', 'q' => isset($data['q']) ? (trim($data['q']) ?: null) : null];
    }

    private function file(array $f): JsonResponse
    {
        return response()->json($f)->header('Cache-Control', 'private, no-store');
    }
}
