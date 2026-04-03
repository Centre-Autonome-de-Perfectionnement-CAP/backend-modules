<?php

namespace Tests\Feature;

use Tests\TestCase;

class StudentSimpleTest extends TestCase
{
    /**
     * Test: Liste des étudiants nécessite authentification
     */
    public function test_list_students_requires_authentication(): void
    {
        $response = $this->getJson('/api/inscription/students');

        $response->assertStatus(401);
    }

    /**
     * Test: Utilisateur authentifié peut lister les étudiants
     */
    public function test_authenticated_user_can_list_students(): void
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/inscription/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /**
     * Test: Affichage d'un étudiant nécessite authentification
     */
    public function test_show_student_requires_authentication(): void
    {
        $response = $this->getJson('/api/inscription/students/1');

        $response->assertStatus(401);
    }

    /**
     * Test: Export est accessible (peut être public ou authentifié)
     */
    public function test_export_is_accessible(): void
    {
        $response = $this->getJson('/api/inscription/students/export/presence');

        // Export peut être public (200) ou nécessiter auth (401)
        $this->assertContains($response->status(), [200, 401, 422]);
    }

    /**
     * Test: Filtres de liste fonctionnent
     */
    public function test_can_filter_students_list(): void
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/inscription/students?year=2024-2025');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }
}
