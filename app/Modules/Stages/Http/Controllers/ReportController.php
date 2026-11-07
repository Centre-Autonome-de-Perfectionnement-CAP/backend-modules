<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Core\Support\FilePreview;
use App\Modules\Stages\Models\StageReport;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\ReportService;
use App\Modules\Stages\Services\StageRequestService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Progiciel : rapports des stagiaires (responsables + superviseurs). */
class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['to_review', 'approved', 'rejected', 'all'])],
            'mine'   => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->reports->staffList($data['status'] ?? 'to_review', !empty($data['mine']) ? $request->user() : null)->values(),
            'to_review' => $this->reports->countDistinct(StageReport::whereIn('status', ['submitted', 'supervisor_approved', 'supervisor_rejected'])),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return $this->successResponse($this->reports->detail(StageReport::with('stage')->findOrFail($id), true));
    }

    public function forIntern(int $id): JsonResponse
    {
        return $this->successResponse($this->reports->overview(StageRequest::findOrFail($id)));
    }

    public function file(Request $request, int $id, int $version): JsonResponse
    {
        $report = StageReport::with('stage')->findOrFail($id);
        $path = $this->reports->filePath($report, $version);
        if (!$path) {
            return $this->errorResponse('Fichier introuvable.', 404, 'STAGE_FILE_NOT_FOUND');
        }

        // JSON base64, entier ou par tranches (?from=) pour l'affichage progressif.
        return response()->json(FilePreview::payload($request, Storage::disk(StageRequestService::DISK), $path,
            "{$this->reports->labelFor($report)} - {$report->stage->reference} - v{$version}.pdf", 'application/pdf'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function comment(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $report = StageReport::with('stage')->findOrFail($id);

        return $this->guard(function () use ($report, $data, $request) {
            $this->reports->comment($report, $data['body'], $request->user());

            return $this->successResponse($this->reports->detail($report->fresh('stage'), true), 'Commentaire envoyé.');
        });
    }

    /** Le personnel peut retirer tout commentaire (abus). */
    public function deleteComment(Request $request, int $id, int $comment): JsonResponse
    {
        $report = StageReport::with('stage')->findOrFail($id);

        return $this->guard(function () use ($report, $comment, $request) {
            $this->reports->deleteComment($report, $comment, $request->user());

            return $this->successResponse($this->reports->detail($report->fresh('stage'), true), 'Commentaire supprimé.');
        });
    }

    public function decide(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);
        $comment = isset($data['comment']) ? (trim(strip_tags($data['comment'])) ?: null) : null;
        $report = StageReport::with('stage')->findOrFail($id);

        return $this->guard(function () use ($report, $data, $comment, $request) {
            $r = $this->reports->decide($report, $data['decision'], $comment, $request->user());

            return $this->successResponse($this->reports->detail($r->load('stage'), true),
                $data['decision'] === 'approved' ? 'Rapport validé. Le stagiaire a été prévenu.' : 'Correction demandée. Le stagiaire a été prévenu.');
        });
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
