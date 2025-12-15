<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stockage\Models\File;
use App\Modules\RH\Services\DocumentManagementService;
use App\Modules\RH\Http\Requests\StoreDocumentRequest;
use App\Modules\RH\Http\Requests\UpdateDocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class DocumentManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DocumentManagementService $documentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $documents = $this->documentService->getAll($request->query('categorie'));
        return $this->successResponse($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $file = $this->documentService->create(
            $request->validated(),
            $request->file('file'),
            auth()->id()
        );

        return $this->successResponse(
            $this->documentService->formatDocument($file),
            'Document créé avec succès',
            201
        );
    }

    public function update(UpdateDocumentRequest $request, File $file): JsonResponse
    {
        if (!$file->is_official_document) {
            return $this->errorResponse('Ce fichier n\'est pas un document officiel', 404);
        }

        $updatedFile = $this->documentService->update($file, $request->validated());

        return $this->successResponse(
            $this->documentService->formatDocument($updatedFile),
            'Document mis à jour'
        );
    }

    public function destroy(File $file): JsonResponse
    {
        if (!$file->is_official_document) {
            return $this->errorResponse('Ce fichier n\'est pas un document officiel', 404);
        }

        $this->documentService->delete($file, auth()->id());
        return $this->successResponse(null, 'Document supprimé');
    }
}
