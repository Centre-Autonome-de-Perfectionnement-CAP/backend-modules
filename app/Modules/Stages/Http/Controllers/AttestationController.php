<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Models\StageAttestation;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\AttestationService;
use App\Modules\Stages\Services\StageRequestService;
use App\Modules\Stages\Support\StageAccess;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Progiciel : attestations de stage.
 *  - responsables : préparer, corriger, valider, attestation exceptionnelle, annuler ;
 *  - secrétariat (et responsables) : imprimer (numéro), remettre, scanner.
 */
class AttestationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AttestationService $attestations,
        private readonly \App\Modules\Stages\Services\StagePdfService $pdfs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['a_valider', 'a_imprimer', 'imprimee', 'remise', 'annulee', 'all'])],
        ]);
        $secretariat = !StageAccess::isLead($request->user());
        $default = $secretariat ? 'a_imprimer' : 'a_valider';

        return response()->json([
            'success' => true,
            'data'    => $this->attestations->staffList($data['status'] ?? $default, $secretariat)->values(),
            'counts'  => $this->attestations->counts($secretariat),
            'lead'    => !$secretariat,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $a = $this->find($request, $id);

        return $this->successResponse($this->attestations->present($a));
    }

    /** Fiche stagiaire (responsables + superviseurs) : conditions + attestations. */
    public function forStage(int $id): JsonResponse
    {
        return $this->successResponse($this->attestations->forStage(StageRequest::findOrFail($id)));
    }

    public function prepare(Request $request, int $id): JsonResponse
    {
        $req = StageRequest::findOrFail($id);

        return $this->guard(function () use ($req, $request) {
            $a = $this->attestations->prepare($req, 'normale', $request->user());

            return $this->successResponse($this->attestations->present($a->load('stage')), 'Attestation pré-remplie.');
        });
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(collect(AttestationService::EDITABLE)
            ->mapWithKeys(fn ($max, $k) => [$k => ['sometimes', 'nullable', 'string', "max:{$max}"]])->all());
        $a = $this->find($request, $id);

        return $this->guard(fn () => $this->successResponse(
            $this->attestations->present($this->attestations->edit($a, $data, $request->user())->load('stage')),
            'Modifications enregistrées.',
        ));
    }

    public function validateAttestation(Request $request, int $id): JsonResponse
    {
        $a = $this->find($request, $id);

        return $this->guard(fn () => $this->successResponse(
            $this->attestations->present($this->attestations->validate($a, $request->user())->load('stage')),
            'Attestation validée. Le secrétariat a été prévenu pour l\'impression.',
        ));
    }

    public function exceptional(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'max:255'],
            'motif'    => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $req   = StageRequest::findOrFail($id);
        $motif = trim(strip_tags($data['motif']));
        if (mb_strlen($motif) < 10) {
            return $this->errorResponse('Précisez le motif (10 caractères au moins).', 422, 'STAGE_ATT_MOTIF');
        }

        return $this->guard(fn () => $this->successResponse(
            $this->attestations->present($this->attestations->exceptional($req, $request->user(), $data['password'], $motif)->load('stage')),
            'Attestation exceptionnelle validée. Le secrétariat a été prévenu pour l\'impression.',
        ));
    }

    public function printed(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'numero' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[A-Za-z0-9\/\-_. ]+$/'],
        ]);
        $a = $this->find($request, $id);

        return $this->guard(fn () => $this->successResponse(
            $this->attestations->present($this->attestations->markPrinted($a, $data['numero'], $request->user())->load('stage')),
            $a->status === 'a_imprimer' ? 'Attestation marquée imprimée. Le stagiaire est prévenu qu\'il peut la retirer.' : 'Numéro modifié.',
        ));
    }

    public function delivered(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $a = $this->find($request, $id);

        return $this->guard(fn () => $this->successResponse(
            $this->attestations->present($this->attestations->markDelivered($a, $data['date'], $request->user())->load('stage')),
            'Remise enregistrée.',
        ));
    }

    public function scan(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:' . AttestationService::SCAN_MAX_KB],
        ]);
        $a = $this->find($request, $id);

        return $this->guard(fn () => $this->successResponse(
            $this->attestations->present($this->attestations->attachScan($a, $request->file('file'), $request->user())),
            'Scan enregistré.',
        ));
    }

    public function scanFile(Request $request, int $id): JsonResponse
    {
        $a = $this->find($request, $id);
        $path = $this->attestations->scanPath($a);
        if (!$path) {
            return $this->errorResponse('Fichier introuvable.', 404, 'STAGE_FILE_NOT_FOUND');
        }
        $disk = Storage::disk(StageRequestService::DISK);
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return response()->json([
            'name'    => 'Attestation ' . ($a->numero ? str_replace('/', '-', $a->numero) . ' ' : '') . "- {$a->stage->reference}.{$ext}",
            'mime'    => match ($ext) { 'pdf' => 'application/pdf', 'png' => 'image/png', default => 'image/jpeg' },
            'content' => base64_encode($disk->get($path)),
        ])->header('Cache-Control', 'private, no-store');
    }

    /** PDF de l'attestation (aperçu tant qu'elle n'est pas validée). */
    public function pdf(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'numero' => ['nullable', 'string', 'min:2', 'max:60', 'regex:/^[A-Za-z0-9\/\-_. ]+$/'],
        ]);
        $a = $this->find($request, $id);
        if ($a->status === 'annulee') {
            return $this->errorResponse('Cette attestation est annulée.', 409, 'STAGE_ATT_STATUS');
        }
        // Le numéro saisi n'est pris en compte qu'avant l'impression ; ensuite, celui enregistré.
        $numero = $a->status === 'a_imprimer'
            ? (isset($data['numero']) ? trim($data['numero']) : ($a->numero ?? $this->attestations->nextNumber()))
            : $a->numero;
        $f = $this->pdfs->attestation($a, $numero);

        return response()->json(['name' => $f['name'], 'mime' => 'application/pdf', 'content' => base64_encode($f['content'])])
            ->header('Cache-Control', 'private, no-store');
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['motif' => ['required', 'string', 'min:5', 'max:1000']]);
        $a = $this->find($request, $id);

        return $this->guard(fn () => $this->successResponse(
            $this->attestations->present($this->attestations->cancel($a, trim(strip_tags($data['motif'])), $request->user())->load('stage')),
            'Attestation annulée.',
        ));
    }

    /** Le secrétariat ne voit pas les attestations encore à valider. */
    private function find(Request $request, int $id): StageAttestation
    {
        $a = StageAttestation::with('stage')->findOrFail($id);
        if (!StageAccess::isLead($request->user()) && in_array($a->status, ['a_valider', 'annulee'], true)) {
            abort(404);
        }

        return $a;
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
