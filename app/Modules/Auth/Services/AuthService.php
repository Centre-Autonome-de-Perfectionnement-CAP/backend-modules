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
     * Authentifier un utilisateur ou un professeur
     */
    public function login(array $credentials): array
    {
        // Chercher d'abord dans la table users
        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Utilisateur connecté', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

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
                    'role' => $user->roles->first()?->slug ?? 'etudiant',
                    'role_display_name' => $user->roles->first()?->name ?? 'Étudiant',
                ],
            ];
        }

        // Sinon, chercher dans la table professors
        $professor = \App\Modules\RH\Models\Professor::where('email', $credentials['email'])->first();

        if ($professor && Hash::check($credentials['password'], $professor->password)) {
            $professor->tokens()->delete();
            $token = $professor->createToken('auth_token')->plainTextToken;

            Log::info('Professeur connecté', [
                'professor_id' => $professor->id,
                'email' => $professor->email,
            ]);

            return [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $professor->id,
                    'first_name' => $professor->first_name,
                    'last_name' => $professor->last_name,
                    'email' => $professor->email,
                    'phone' => $professor->phone,
                    'role' => 'professeur',
                    'role_display_name' => 'Professeur',
                    'user_type' => 'professor',
                ],
            ];
        }

        throw ValidationException::withMessages([
            'email' => ['Les identifiants fournis sont incorrects.'],
        ]);
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
     * Déconnecter un utilisateur ou professeur (révoquer tous ses tokens)
     */
    public function logout($user): void
    {
        $user->tokens()->delete();

        Log::info('Utilisateur déconnecté', [
            'user_id' => $user->id,
            'type' => get_class($user),
        ]);
    }

    /**
     * Déconnecter un utilisateur ou professeur (révoquer seulement le token actuel)
     */
    public function logoutCurrent($user, $currentToken): void
    {
        $currentToken->delete();

        Log::info('Token révoqué', [
            'user_id' => $user->id,
            'type' => get_class($user),
        ]);
    }

    /**
     * Récupérer les informations de l'utilisateur authentifié
     */
    public function me($user): array
    {
        if ($user instanceof User) {
            $user->load('roles');
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->roles->first()?->slug ?? 'etudiant',
                'role_display_name' => $user->roles->first()?->name ?? 'Étudiant',
            ];
        }

        // Professeur
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => 'professeur',
            'role_display_name' => 'Professeur',
            'user_type' => 'professor',
        ];
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

        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        Log::info('Mot de passe changé', [
            'user_id' => $user->id,
        ]);

        return true;
    }
}
