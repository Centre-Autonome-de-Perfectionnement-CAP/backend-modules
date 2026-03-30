<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class WhatsAppGroupController extends Controller
{
    use ApiResponse;

    /**
     * Liste toutes les filières avec leurs liens WhatsApp
     */
    public function index(): JsonResponse
    {
        $departments = Department::with('cycle')
            ->orderBy('cycle_id')
            ->orderBy('name')
            ->get()
            ->map(fn($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
                'abbreviation' => $dept->abbreviation,
                'cycle' => $dept->cycle ? [
                    'id' => $dept->cycle->id,
                    'name' => $dept->cycle->name,
                ] : null,
                'whatsapp_link' => $dept->whatsapp_link,
            ]);

        return $this->successResponse($departments);
    }

    /**
     * Met à jour le lien WhatsApp d'une filière
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $data = $request->validate([
            'whatsapp_link' => 'nullable|url|max:500',
        ]);

        $department->update($data);

        return $this->successResponse([
            'id' => $department->id,
            'name' => $department->name,
            'whatsapp_link' => $department->whatsapp_link,
        ], 'Lien WhatsApp mis à jour avec succès');
    }

    /**
     * Supprime le lien WhatsApp d'une filière
     */
    public function destroy(Department $department): JsonResponse
    {
        $department->update(['whatsapp_link' => null]);

        return $this->successResponse(null, 'Lien WhatsApp supprimé avec succès');
    }
}
