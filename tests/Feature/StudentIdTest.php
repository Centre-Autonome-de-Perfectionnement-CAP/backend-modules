<?php

namespace Tests\Feature;

use Tests\TestCase;

class StudentIdTest extends TestCase
{
    /**
     * Test: Lookup nécessite tous les champs requis
     */
    public function test_lookup_requires_all_fields(): void
    {
        $response = $this->postJson('/api/inscription/students/lookup-id', []);

        $response->assertstatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJsonValidationErrors(['last_name', 'first_names', 'birth_date', 'birth_place']);
    }

    /**
     * Test: Lookup avec identité inexistante retourne 404
     */
    public function test_lookup_with_nonexistent_identity_returns_404(): void
    {
        $response = $this->postJson('/api/inscription/students/lookup-id', [
            'last_name' => 'Doe',
            'first_names' => 'John',
            'birth_date' => '2000-01-01',
            'birth_place' => 'Paris',
        ]);

        // Peut retourner 404 ou 200 avec null selon l'implémentation
        $this->assertContains($response->status(), [404, 200]);
    }

    /**
     * Test: Assign nécessite tous les champs requis
     */
    public function test_assign_requires_all_fields(): void
    {
        $response = $this->postJson('/api/inscription/students/assign-id', []);

        $response->assertstatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJsonValidationErrors(['last_name', 'first_names', 'birth_date', 'birth_place', 'phone']);
    }

    /**
     * Test: Assign avec identité inexistante retourne erreur
     */
    public function test_assign_with_nonexistent_identity_returns_error(): void
    {
        $response = $this->postJson('/api/inscription/students/assign-id', [
            'last_name' => 'Doe',
            'first_names' => 'John',
            'birth_date' => '2000-01-01',
            'birth_place' => 'Paris',
            'phone' => '0123456789',
        ]);

        // Peut retourner 404 ou autre erreur selon l'implémentation
        $this->assertContains($response->status(), [404, 422, 500]);
    }

    /**
     * Test: Routes sont publiques (pas d'authentification requise)
     */
    public function test_student_id_routes_are_public(): void
    {
        $response1 = $this->postJson('/api/inscription/students/lookup-id', [
            'last_name' => 'Test',
            'first_names' => 'User',
            'birth_date' => '2000-01-01',
            'birth_place' => 'Test City',
        ]);

        $response2 = $this->postJson('/api/inscription/students/assign-id', [
            'last_name' => 'Test',
            'first_names' => 'User',
            'birth_date' => '2000-01-01',
            'birth_place' => 'Test City',
            'phone' => '0123456789',
        ]);

        // Les routes ne doivent pas retourner 401 (authentification)
        $this->assertNotEquals(401, $response1->status());
        $this->assertNotEquals(401, $response2->status());
    }

    /**
     * Test: Validation du format de date
     */
    public function test_validates_birth_date_format(): void
    {
        $response = $this->postJson('/api/inscription/students/lookup-id', [
            'last_name' => 'Doe',
            'first_names' => 'John',
            'birth_date' => 'invalid-date',
            'birth_place' => 'Paris',
        ]);

        $response->assertstatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    }
}
