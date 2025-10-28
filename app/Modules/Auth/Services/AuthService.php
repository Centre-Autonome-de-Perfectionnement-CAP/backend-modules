<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthService
{
    /**
     * Authentifier un utilisateur
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects. '],
            ]);
        }

        // Révoquer les anciens tokens
        $user->tokens()->delete();

        // Créer un nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Utilisateur connecté', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // Charger les rôles
        $user->load('roles');
        
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->roles->first()?->name ?? 'etudiant',
                'role_display_name' => $user->roles->first()?->display_name ?? 'Étudiant',
            ],
        ];
    }

    /**
     * Enregistrer un nouvel utilisateur
     */
    public function register(array $data): array
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Nouvel utilisateur enregistré', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }

    /**
     * Déconnecter un utilisateur (révoquer tous ses tokens)
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();

        Log::info('Utilisateur déconnecté', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Déconnecter un utilisateur (révoquer seulement le token actuel)
     */
    public function logoutCurrent(User $user, $currentToken): void
    {
        $currentToken->delete();

        Log::info('Token révoqué', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Récupérer les informations de l'utilisateur authentifié
     */
    public function me(User $user): User
    {
        return $user->load('roles');
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Révoquer tous les tokens sauf le token actuel
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        Log::info('Mot de passe changé', [
            'user_id' => $user->id,
        ]);

        return true;
    }
}
