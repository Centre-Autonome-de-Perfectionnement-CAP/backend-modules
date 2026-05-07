<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Progi CAP API",
 *     version="1.0.0",
 *     description="API documentation for Progi CAP application modules"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Bearer"
 * )
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     title="Pagination Meta",
 *     description="Métadonnées de pagination",
 *     @OA\Property(property="total", type="integer", description="Nombre total d'éléments"),
 *     @OA\Property(property="per_page", type="integer", description="Nombre d'éléments par page"),
 *     @OA\Property(property="current_page", type="integer", description="Page actuelle"),
 *     @OA\Property(property="last_page", type="integer", description="Dernière page"),
 *     @OA\Property(property="from", type="integer", description="Premier élément de la page"),
 *     @OA\Property(property="to", type="integer", description="Dernier élément de la page")
 * )
 */
class Controller extends BaseController
{
   use AuthorizesRequests, ValidatesRequests;
}
