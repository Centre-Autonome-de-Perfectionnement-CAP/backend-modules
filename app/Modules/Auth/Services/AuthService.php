<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\RH\Models\Professor;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentGroup;
use App\Modules\Inscription\Models\AcademicYear;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AuthService{
  
    public function login(array $credentials): array
    {
        // Vérifier d'abord dans la table users (admins, staff)
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
                    'role_id'           => $user->roles->first()?->id ?? null,
                    'user_type'         => 'user',
                    'classes'           => [], // Pas de classes pour les admins
                ],
            ];
        }

        // Vérifier dans la table professors
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
                    'role_id'           => null,
                    'user_type'         => 'professor',
                    'classes'           => [], // À implémenter si nécessaire
                ],
            ];
        }

        // Vérifier dans la table personal_information (responsables de classe - role_id = 9)
        $personalInfo = PersonalInformation::where('email', $credentials['email'])
            ->where('role_id', 9)
            ->first();

        if ($personalInfo && Hash::check($credentials['password'], $personalInfo->password)) {
            
            $personalInfo->tokens()->delete();
            $token = $personalInfo->createToken('auth_token')->plainTextToken;

            // Récupérer toutes les classes associées à ce responsable
            $classes = $this->getResponsableClasses($personalInfo->id);

            Log::info('Responsable de classe connecté', [
                'personal_information_id' => $personalInfo->id,
                'email'                   => $personalInfo->email,
                'nombre_classes'          => count($classes),
            ]);

            return [
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => [
                    'id'                => $personalInfo->id,
                    'first_name'        => $personalInfo->first_names,
                    'last_name'         => $personalInfo->last_name,
                    'email'             => $personalInfo->email,
                    'phone'             => $this->extractPhoneFromContacts($personalInfo->contacts),
                    'role'              => 'responsable',
                    'role_display_name' => 'Responsable de classe',
                    'role_id'           => $personalInfo->role_id,
                    'user_type'         => 'responsable',
                    'classes'           => $classes,
                ],
            ];
        }

        // Aucun match trouvé
        throw ValidationException::withMessages([
            'email' => ['Les identifiants fournis sont incorrects.'],
        ]);
    }

    /**
     * Récupère toutes les classes associées à un responsable
     */
    private function getResponsableClasses(int $personalInfoId): array
    {
        try {
            // Récupérer toutes les classes où ce responsable est assigné
            // Cette requête dépend de la structure de votre base de données
            // Voici une approche basée sur les relations que vous avez montrées
            
            $classes = ClassGroup::whereHas('studentGroups', function($query) use ($personalInfoId) {
                // Si la table student_groups a une colonne responsable_id
                $query->where('responsable_id', $personalInfoId);
            })
            ->orWhereHas('department', function($query) use ($personalInfoId) {
                // Alternative: si le responsable est lié via le département
                $query->where('responsable_id', $personalInfoId);
            })
            ->with(['academicYear', 'department.cycle'])
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('study_level')
            ->orderBy('group_name')
            ->get();

            if ($classes->isEmpty()) {
                // Si aucune classe trouvée avec les relations ci-dessus,
                // on peut essayer de récupérer toutes les classes et filtrer
                // ou retourner un tableau vide
                return [];
            }

            // Organiser les classes par année académique
            $classesByYear = [];
            foreach ($classes as $class) {
                $yearId = $class->academic_year_id;
                $yearName = $class->academicYear ? $class->academicYear->name : 'Année inconnue';
                
                if (!isset($classesByYear[$yearId])) {
                    $classesByYear[$yearId] = [
                        'academic_year_id' => $yearId,
                        'academic_year_name' => $yearName,
                        'classes' => []
                    ];
                }

                // Compter le nombre d'étudiants dans cette classe
                $studentCount = StudentGroup::where('class_group_id', $class->id)
                    ->whereHas('student')
                    ->count();

                $classesByYear[$yearId]['classes'][] = [
                    'id' => $class->id,
                    'group_name' => $class->group_name,
                    'study_level' => $class->study_level,
                    'filiere' => $class->department->name ?? 'N/A',
                    'cycle' => $class->department->cycle->name ?? 'N/A',
                    'total_etudiants' => $studentCount,
                    'academic_year_id' => $class->academic_year_id,
                    'academic_year_name' => $yearName,
                    'validation_average' => $class->validation_average,
                ];
            }

            // Convertir en tableau indexé
            return array_values($classesByYear);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des classes du responsable', [
                'personal_info_id' => $personalInfoId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Extrait le numéro de téléphone des contacts
     */
    private function extractPhoneFromContacts($contacts): ?string
    {
        if (is_string($contacts)) {
            $contacts = json_decode($contacts, true);
        }
        
        if (is_array($contacts)) {
            return $contacts['phone'] ?? $contacts['telephone'] ?? null;
        }
        
        return null;
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
            'user'         => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => 'etudiant',
                'user_type'  => 'user',
            ],
        ];
    }

    public function logout($user): void
    {
        $user->tokens()->delete();
        Log::info('Utilisateur déconnecté', ['user_id' => $user->id, 'type' => get_class($user)]);
    }

    public function logoutCurrent($user, $currentToken): void
    {
        $currentToken->delete();
        Log::info('Token révoqué', ['user_id' => $user->id, 'type' => get_class($user)]);
    }

    public function me($user): array
    {
        $baseUser = [
            'id'         => $user->id,
            'first_name' => $user instanceof PersonalInformation ? $user->first_names : $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
            'user_type'  => $this->getUserType($user),
        ];

        // Ajouter le téléphone selon le type
        if ($user instanceof PersonalInformation) {
            $baseUser['phone'] = $this->extractPhoneFromContacts($user->contacts);
        } else {
            $baseUser['phone'] = $user->phone ?? null;
        }

        // Ajouter les rôles et classes selon le type
        if ($user instanceof User) {
            $user->load('roles');
            $baseUser['role'] = $user->roles->first()?->slug ?? 'etudiant';
            $baseUser['role_display_name'] = $user->roles->first()?->name ?? 'Étudiant';
            $baseUser['role_id'] = $user->roles->first()?->id ?? null;
            $baseUser['classes'] = [];
        } elseif ($user instanceof PersonalInformation && $user->role_id == 9) {
            $baseUser['role'] = 'responsable';
            $baseUser['role_display_name'] = 'Responsable de classe';
            $baseUser['role_id'] = $user->role_id;
            $baseUser['classes'] = $this->getResponsableClasses($user->id);
        } elseif ($user instanceof Professor) {
            $baseUser['role'] = 'professeur';
            $baseUser['role_display_name'] = 'Professeur';
            $baseUser['role_id'] = null;
            $baseUser['classes'] = [];
        }

        return $baseUser;
    }

    private function getUserType($user): string
    {
        if ($user instanceof User) return 'user';
        if ($user instanceof Professor) return 'professor';
        if ($user instanceof PersonalInformation) return 'responsable';
        return 'unknown';
    }

    public function changePassword($user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
        
        // Supprimer tous les tokens sauf le token actuel si disponible
        if (method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
            $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
        } else {
            $user->tokens()->delete();
            // Recréer un token si nécessaire
            $user->createToken('auth_token')->plainTextToken;
        }

        Log::info('Mot de passe changé', ['user_id' => $user->id, 'type' => get_class($user)]);

        return true;
    }
}