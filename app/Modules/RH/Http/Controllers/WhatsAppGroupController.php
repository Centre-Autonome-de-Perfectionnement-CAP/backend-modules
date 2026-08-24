<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\RestrictsToRoles;

class WhatsAppGroupController extends Controller
{
    use ApiResponse, RestrictsToRoles;

    /**
     * AJOUT (audit sécurité) — update/destroy n'avaient AUCUNE protection :
     * n'importe quel compte connecté pouvait changer ou supprimer le lien
     * WhatsApp d'une filière. `index()` n'est pas touché ici — il reste
     * dans son état actuel (aucune authentification requise), comme avant
     * ce correctif. À confirmer si c'est le comportement voulu.
     */
    private const ALLOWED_ROLES = ['admin', 'chef-cap', 'rh'];

    public function __construct()
    {
        $this->restrictToRoles(self::ALLOWED_ROLES, only: ['update', 'destroy']);
    }

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
