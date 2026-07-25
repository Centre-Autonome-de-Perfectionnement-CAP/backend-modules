<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;


class AuthController extends Controller{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService
    ) {}
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Connexion",
     *     description="Authentifie un utilisateur et renvoie un token Sanctum",
     *     operationId="authLogin",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides ou identifiants incorrects")
     * )
     */
    public function login(LoginRequest $request) {
        $result = $this->authService->login($request->validated());
        return $this->successResponse($result, 'Connexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Inscription",
     *     description="Crée un nouvel utilisateur et renvoie un token Sanctum",
     *     operationId="authRegister",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minimum=8),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());
        return $this->successResponse($result, 'Inscription réussie');
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Déconnexion",
     *     description="Révoque le token d'authentification courant",
     *     operationId="authLogout",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $this->authService->logoutCurrent($user, $user->currentAccessToken());
        }
        return $this->successResponse(null, 'Déconnexion réussie');
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Utilisateur courant",
     *     description="Retourne l'utilisateur authentifié",
     *     operationId="authMe",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur courant",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function me(Request $request)
    {
        $user = $this->authService->me($request->user());
        return $this->successResponse($user, 'Utilisateur récupéré avec succès');
    }
}
