<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Créer un utilisateur authentifié pour les tests
     */
    protected function authenticatedUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    /**
     * Créer un utilisateur simple sans authentification
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Obtenir les headers d'authentification avec token
     */
    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('test-token')->plainTextToken;
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Headers JSON standards
     */
    protected function jsonHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Assert qu'une réponse JSON contient une structure
     */
    protected function assertJsonStructure(array $structure, $response): void
    {
        $response->assertJsonStructure($structure);
    }

    /**
     * Assert qu'une réponse contient des erreurs de validation
     */
    protected function assertValidationErrors($response, array $fields): void
    {
        $response->assertstatus(422);
        $response->assertJsonValidationErrors($fields);
    }
}
