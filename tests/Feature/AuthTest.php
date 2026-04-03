<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * Test: Login réussi avec des identifiants valides
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'role',
                        'role_display_name',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ]);
    }

    /**
     * Test: Login échoue avec un mot de passe incorrect
     */
    public function test_login_fails_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error_code',
                'errors',
            ])
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR',
            ]);
    }

    /**
     * Test: Login échoue avec un email inexistant
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error_code',
                'errors',
            ])
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR',
            ]);
    }

    /**
     * Test: Validation échoue si l'email est manquant
     */
    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error_code',
                'errors',
            ])
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR',
            ]);
    }

    /**
     * Test: Validation échoue si le mot de passe est manquant
     */
    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error_code',
                'errors',
            ])
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR',
            ]);
    }

    /**
     * Test: Récupération des informations de l'utilisateur authentifié
     */
    public function test_authenticated_user_can_get_their_info(): void
    {
        $user = $this->authenticatedUser([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'test@example.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ]);
    }

    /**
     * Test: Utilisateur non authentifié ne peut pas accéder à /me
     */
    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
            ]);
    }

    /**
     * Test: Logout réussi pour un utilisateur authentifié
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->authenticatedUser();

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
            ]);
        
        // Vérifier que le token a été révoqué
        $this->assertCount(0, $user->tokens);
    }

    /**
     * Test: Utilisateur non authentifié ne peut pas se déconnecter
     */
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
                'success',
                'error_code',
            ])
            ->assertJson([
                'success' => false,
                'error_code' => 'AUTHENTICATION_REQUIRED',
            ]);
    }

    /**
     * Test: Récupération de la liste de l'administration
     */
    public function test_can_get_administration_list(): void
    {
        $response = $this->getJson('/api/auth/administration');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'photo',
                        'roles' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Utilisateurs administratifs récupérés avec succès',
            ]);
    }

    /**
     * Test: Récupération du soutien informatique
     */
    public function test_can_get_support_info(): void
    {
        $response = $this->getJson('/api/auth/soutien-informatique');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'photo',
                        'roles' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Membres du soutien informatique récupérés avec succès',
            ]);
    }
}
