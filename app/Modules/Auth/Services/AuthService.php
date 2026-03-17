<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\RH\Models\Professor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Connexion — cherche dans users, puis professors, puis personal_information
     */
    public function login(array $credentials): array
    {
        // ── 1. Table users (admins, staff...) ──────────────────────────────
        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Utilisateur connecté', ['user_id' => $user->id, 'email' => $user->email]);

            $user->load('roles');

            return [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => [
                    'id'                => $user->id,
                    'first_name'        => $user->first_name,
                    'last_name'         => $user->last_name,
                    'email'             => $user->email,
                    'phone'             => $user->phone,
                    'role'              => $user->roles->first()?->slug ?? 'etudiant',
                    'role_display_name' => $user->roles->first()?->name ?? 'Étudiant',
                    'user_type'         => 'user',
                ],
            ];
        }

        // ── 2. Table professors ─────────────────────────────────────────────
        $professor = Professor::where('email', $credentials['email'])->first();

        if ($professor && Hash::check($credentials['password'], $professor->password)) {
            $professor->tokens()->delete();
            $token = $professor->createToken('auth_token')->plainTextToken;

            Log::info('Professeur connecté', ['professor_id' => $professor->id, 'email' => $professor->email]);

            return [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => [
                    'id'                => $professor->id,
                    'first_name'        => $professor->first_name,
                    'last_name'         => $professor->last_name,
                    'email'             => $professor->email,
                    'phone'             => $professor->phone,
                    'role'              => 'professeur',
                    'role_display_name' => 'Professeur',
                    'user_type'         => 'professor',
                ],
            ];
        }

        // ── 3. Table personal_information (responsables de classe) ──────────
        // ✅ Filtre sur role_id = 9 : seuls les responsables peuvent se connecter ici
        $personalInfo = PersonalInformation::where('email', $credentials['email'])
            ->where('role_id', 9)
            ->first();

        if ($personalInfo && Hash::check($credentials['password'], $personalInfo->password)) {
            // ✅ Fonctionne maintenant car PersonalInformation étend Authenticatable
            //    et utilise le trait HasApiTokens
            $personalInfo->tokens()->delete();
            $token = $personalInfo->createToken('auth_token')->plainTextToken;

            Log::info('Responsable de classe connecté', [
                'personal_information_id' => $personalInfo->id,
                'email'                   => $personalInfo->email,
            ]);

            return [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => [
                    'id'                => $personalInfo->id,
                    // ✅ PersonalInformation utilise first_names (pluriel) et last_name
                    'first_name'        => $personalInfo->first_names,
                    'last_name'         => $personalInfo->last_name,
                    'email'             => $personalInfo->email,
                    'phone'             => data_get(
                        is_array($personalInfo->contacts)
                            ? $personalInfo->contacts
                            : json_decode($personalInfo->contacts ?? '{}', true),
                        'phone'
                    ),
                    'role'              => 'responsable',
                    'role_display_name' => 'Responsable de classe',
                    'user_type'         => 'responsable',
                ],
            ];
        }

        // Aucun match trouvé
        throw ValidationException::withMessages([
            'email' => ['Les identifiants fournis sont incorrects.'],
        ]);
    }

    /**
     * Enregistrer un nouvel utilisateur (table users uniquement)
     */
    public function register(array $data): array
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'phone'      => $data['phone'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Nouvel utilisateur enregistré', ['user_id' => $user->id, 'email' => $user->email]);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ];
    }

    /**
     * Déconnecter (révoquer tous les tokens)
     */
    public function logout($user): void
    {
        $user->tokens()->delete();
        Log::info('Utilisateur déconnecté', ['user_id' => $user->id, 'type' => get_class($user)]);
    }

    /**
     * Déconnecter (révoquer uniquement le token actuel)
     */
    public function logoutCurrent($user, $currentToken): void
    {
        $currentToken->delete();
        Log::info('Token révoqué', ['user_id' => $user->id, 'type' => get_class($user)]);
    }

    /**
     * Retourner les infos de l'utilisateur authentifié
     * Gère les 3 types : User, Professor, PersonalInformation
     */
    public function me($user): array
    {
        // ── Responsable de classe ──
        if ($user instanceof PersonalInformation) {
            return [
                'id'                => $user->id,
                'first_name'        => $user->first_names,
                'last_name'         => $user->last_name,
                'email'             => $user->email,
                'phone'             => data_get(
                    is_array($user->contacts)
                        ? $user->contacts
                        : json_decode($user->contacts ?? '{}', true),
                    'phone'
                ),
                'role'              => 'responsable',
                'role_display_name' => 'Responsable de classe',
                'user_type'         => 'responsable',
            ];
        }

        // ── Utilisateur standard (admin, staff...) ──
        if ($user instanceof User) {
            $user->load('roles');
            return [
                'id'                => $user->id,
                'first_name'        => $user->first_name,
                'last_name'         => $user->last_name,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'role'              => $user->roles->first()?->slug ?? 'etudiant',
                'role_display_name' => $user->roles->first()?->name ?? 'Étudiant',
                'user_type'         => 'user',
            ];
        }

        // ── Professeur ──
        return [
            'id'                => $user->id,
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'role'              => 'professeur',
            'role_display_name' => 'Professeur',
            'user_type'         => 'professor',
        ];
    }

    /**
     * Changer le mot de passe (users uniquement)
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        Log::info('Mot de passe changé', ['user_id' => $user->id]);

        return true;
    }
}