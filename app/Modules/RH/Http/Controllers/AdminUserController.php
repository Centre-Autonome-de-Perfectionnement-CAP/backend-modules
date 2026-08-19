<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\RH\Http\Requests\CreateAdminUserRequest;
use App\Modules\RH\Http\Requests\UpdateAdminUserRequest;
use App\Modules\RH\Http\Resources\AdminUserResource;
use App\Modules\RH\Services\AdminUserService;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    use ApiResponse, HasPagination;

    /**
     * AJOUT (16/08/2026) — contrôle de rôle fin, absent jusqu'ici. Ce
     * contrôleur gère les comptes admin et l'attribution de rôles :
     * n'importe quel compte connecté pouvait auparavant s'attribuer le
     * rôle admin lui-même. Appliqué une seule fois ici (middleware de
     * closure, exécuté après auth:sanctum) plutôt que répété dans
     * chaque méthode.
     *
     * ⚠️ 'admin' et 'rh' n'ont été retrouvés dans AUCUN seeder de rôles
     * de ce dépôt — à vérifier en base avant mise en production.
     */
    private const ALLOWED_ROLES = ['admin', 'responsable-division', 'rh'];

    public function __construct(
        protected AdminUserService $adminUserService
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            $slug = $request->user()?->roles->first()?->slug;
            if (!in_array($slug, self::ALLOWED_ROLES, true)) {
                abort(403, 'Accès réservé aux rôles autorisés (admin, responsable-division, rh).');
            }
            return $next($request);
        });
    }

    /**
     * Liste des utilisateurs administratifs
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'role_id', 'sort_by', 'sort_order']);
        $perPage = $this->getPerPage($request);
        
        $users = $this->adminUserService->getAll($filters, $perPage);
        
        $users->setCollection(
            AdminUserResource::collection($users->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $users,
            'Utilisateurs récupérés avec succès'
        );
    }

    /**
     * Créer un nouvel utilisateur administratif
     */
    public function store(CreateAdminUserRequest $request): JsonResponse
    {
        $data = $request->except(['rib', 'ifu', 'photo']);
        
        $user = $this->adminUserService->create(
            $data,
            auth()->id(),
            $request->file('rib'),
            $request->file('ifu'),
            $request->file('photo')
        );

        return $this->createdResponse(
            new AdminUserResource($user->load('roles')),
            'Utilisateur créé avec succès'
        );
    }

    /**
     * Afficher un utilisateur
     */
    public function show(User $adminUser): JsonResponse
    {
        return $this->successResponse(
            new AdminUserResource($adminUser->load('roles')),
            'Utilisateur récupéré avec succès'
        );
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(UpdateAdminUserRequest $request, User $adminUser): JsonResponse
    {
        $data = $request->except(['rib', 'ifu', 'photo']);
        
        $user = $this->adminUserService->update(
            $adminUser,
            $data,
            auth()->id(),
            $request->file('rib'),
            $request->file('ifu'),
            $request->file('photo')
        );

        return $this->updatedResponse(
            new AdminUserResource($user),
            'Utilisateur mis à jour avec succès'
        );
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $adminUser): JsonResponse
    {
        $this->adminUserService->delete($adminUser);
        return $this->deletedResponse('Utilisateur supprimé avec succès');
    }

    /**
     * Attacher un rôle à un utilisateur
     */
    public function attachRole(Request $request, User $adminUser): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $this->adminUserService->attachRole($adminUser, $request->role_id);
        return $this->successResponse(null, 'Rôle attaché avec succès');
    }

    /**
     * Détacher un rôle d'un utilisateur
     */
    public function detachRole(Request $request, User $adminUser): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $this->adminUserService->detachRole($adminUser, $request->role_id);
        return $this->successResponse(null, 'Rôle détaché avec succès');
    }

    /**
     * Récupérer les statistiques des utilisateurs
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->adminUserService->getStatistics();
        return $this->successResponse($stats, 'Statistiques récupérées avec succès');
    }
}
