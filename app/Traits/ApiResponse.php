<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Retourne une réponse JSON de succès
     */
    protected function successResponse(
        $data = null,
        string $message = 'Opération réussie',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Retourne une réponse JSON de succès avec des données paginées
     */
    protected function successPaginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Données récupérées avec succès'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
    
    /**
     * Retourne une réponse JSON d'erreur
     */
    protected function errorResponse(
        string $message = 'Une erreur est survenue',
        int $statusCode = 500,
        ?string $errorCode = null,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Retourne une réponse JSON de création réussie
     */
    protected function createdResponse(
        $data = null,
        string $message = 'Ressource créée avec succès'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }
    
    /**
     * Retourne une réponse JSON de suppression réussie
     */
    protected function deletedResponse(
        string $message = 'Ressource supprimée avec succès'
    ): JsonResponse {
        return $this->successResponse(null, $message);
    }
    
    /**
     * Retourne une réponse JSON de mise à jour réussie
     */
    protected function updatedResponse(
        $data = null,
        string $message = 'Ressource mise à jour avec succès'
    ): JsonResponse {
        return $this->successResponse($data, $message);
    }
    
    /**
     * Retourne une réponse JSON pour une ressource non trouvée
     */
    protected function notFoundResponse(
        string $message = 'Ressource introuvable'
    ): JsonResponse {
        return $this->errorResponse($message, 404, 'RESOURCE_NOT_FOUND');
    }
    
    /**
     * Retourne une réponse JSON pour une validation échouée
     */
    protected function validationErrorResponse(
        string $message = 'Les données fournies sont invalides',
        array $errors = []
    ): JsonResponse {
        return $this->errorResponse($message, 422, 'VALIDATION_ERROR', $errors);
    }
    
    /**
     * Retourne une réponse JSON pour une authentification échouée
     */
    protected function unauthenticatedResponse(
        string $message = 'Authentification requise'
    ): JsonResponse {
        return $this->errorResponse($message, 401, 'AUTHENTICATION_REQUIRED');
    }
    
    /**
     * Retourne une réponse JSON pour une autorisation refusée
     */
    protected function unauthorizedResponse(
        string $message = 'Non autorisé'
    ): JsonResponse {
        return $this->errorResponse($message, 403, 'UNAUTHORIZED');
    }
}
