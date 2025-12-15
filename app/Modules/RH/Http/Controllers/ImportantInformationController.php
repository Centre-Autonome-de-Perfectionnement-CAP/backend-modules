<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Models\ImportantInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class ImportantInformationController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $informations = ImportantInformation::with('file')
            ->active()
            ->ordered()
            ->get()
            ->map(fn($info) => [
                'id' => $info->id,
                'title' => $info->title,
                'description' => $info->description,
                'icon' => $info->icon,
                'color' => $info->color,
                'link' => $info->link,
                'file' => $info->file ? [
                    'id' => $info->file->id,
                    'name' => $info->file->original_name,
                    'url' => $info->file->url,
                ] : null,
            ]);

        return $this->successResponse($informations);
    }

    public function indexAdmin(): JsonResponse
    {
        $informations = ImportantInformation::with('file')
            ->ordered()
            ->get()
            ->map(fn($info) => [
                'id' => $info->id,
                'title' => $info->title,
                'description' => $info->description,
                'icon' => $info->icon,
                'color' => $info->color,
                'link' => $info->link,
                'file_id' => $info->file_id,
                'file' => $info->file ? [
                    'id' => $info->file->id,
                    'name' => $info->file->original_name,
                    'url' => $info->file->url,
                ] : null,
                'is_active' => $info->is_active,
                'order' => $info->order,
                'created_at' => $info->created_at,
            ]);

        return $this->successResponse($informations);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'required|string',
            'color' => 'required|in:primary,success,info,warning,danger',
            'link' => 'nullable|string',
            'file_id' => 'nullable|exists:files,id',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        $information = ImportantInformation::create($data);
        return $this->successResponse($information, 'Information créée avec succès', 201);
    }

    public function update(Request $request, ImportantInformation $important_information): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'icon' => 'sometimes|string',
            'color' => 'sometimes|in:primary,success,info,warning,danger',
            'link' => 'nullable|string',
            'file_id' => 'nullable|exists:files,id',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        $important_information->update($data);
        return $this->successResponse($important_information, 'Information mise à jour');
    }

    public function destroy(ImportantInformation $important_information): JsonResponse
    {
        $important_information->delete();
        return $this->successResponse(null, 'Information supprimée');
    }
}
