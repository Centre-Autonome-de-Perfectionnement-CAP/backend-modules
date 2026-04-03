<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * Trait HasPagination
 * 
 * Gestion centralisée de la pagination pour les contrôleurs
 */
trait HasPagination
{
    /**
     * Récupère le nombre d'éléments par page depuis la requête
     * avec validation des limites min/max
     *
     * @param Request $request La requête HTTP
     * @param int $default Nombre par défaut d'éléments par page
     * @param int $max Nombre maximum d'éléments par page
     * @return int Nombre d'éléments par page validé
     */
    protected function getPerPage(Request $request, int $default = 15, int $max = 100): int
    {
        $perPage = (int) $request->input('per_page', $default);
        return min(max($perPage, 1), $max);
    }
    
    /**
     * Récupère les paramètres de tri depuis la requête
     *
     * @param Request $request La requête HTTP
     * @param string $defaultSortBy Colonne de tri par défaut
     * @param string $defaultSortOrder Ordre de tri par défaut (asc ou desc)
     * @param array $allowedColumns Colonnes autorisées pour le tri
     * @return array ['sort_by' => string, 'sort_order' => string]
     */
    protected function getSortParams(
        Request $request, 
        string $defaultSortBy = 'created_at',
        string $defaultSortOrder = 'desc',
        array $allowedColumns = []
    ): array {
        $sortBy = $request->input('sort_by', $defaultSortBy);
        $sortOrder = $request->input('sort_order', $defaultSortOrder);

        if (!empty($allowedColumns) && !in_array($sortBy, $allowedColumns)) {
            $sortBy = $defaultSortBy;
        }
        
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) 
            ? strtolower($sortOrder) 
            : $defaultSortOrder;
        
        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ];
    }
    
    /**
     * Récupère les paramètres de pagination et tri combinés
     *
     * @param Request $request La requête HTTP
     * @param int $defaultPerPage Nombre par défaut d'éléments par page
     * @param int $maxPerPage Nombre maximum d'éléments par page
     * @param string $defaultSortBy Colonne de tri par défaut
     * @param string $defaultSortOrder Ordre de tri par défaut
     * @return array ['per_page' => int, 'sort_by' => string, 'sort_order' => string]
     */
    protected function getPaginationParams(
        Request $request,
        int $defaultPerPage = 15,
        int $maxPerPage = 100,
        string $defaultSortBy = 'created_at',
        string $defaultSortOrder = 'desc'
    ): array {
        return [
            'per_page' => $this->getPerPage($request, $defaultPerPage, $maxPerPage),
            'sort_by' => $request->input('sort_by', $defaultSortBy),
            'sort_order' => $request->input('sort_order', $defaultSortOrder),
        ];
    }
}
