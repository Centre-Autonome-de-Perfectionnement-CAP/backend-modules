<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Stockage\Models\Role;
use App\Modules\Stockage\Models\Permission;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = $this->createPermissions();
        
        $roles = [
            [
                'name' => 'Chef CAP',
                'slug' => 'chef-cap',
                'permissions' => ['*'], // Toutes les permissions
            ],
            [
                'name' => 'Chef Division',
                'slug' => 'chef-division',
                'permissions' => [
                    'can_view_dashboard', 'can_export_data',
                    'can_view_users', 'can_create_users', 'can_edit_users', 'can_print_users',
                    'can_view_students', 'can_create_students', 'can_edit_students', 'can_print_students', 'can_export_students',
                    'can_view_inscriptions', 'can_create_inscriptions', 'can_edit_inscriptions', 'can_approve_inscriptions', 'can_print_inscriptions',
                    'can_view_payments', 'can_print_receipts', 'can_export_payments',
                    'can_view_courses', 'can_create_courses', 'can_edit_courses',
                    'can_view_reports', 'can_print_reports',
                ],
            ],
            [
                'name' => 'Secrétaire',
                'slug' => 'secretaire',
                'permissions' => [
                    'can_view_dashboard',
                    'can_view_inscriptions', 'can_create_inscriptions', 'can_edit_inscriptions', 'can_approve_inscriptions', 'can_reject_inscriptions', 'can_print_inscriptions',
                    'can_view_students', 'can_print_students',
                    'can_view_payments', 'can_print_receipts',
                    'can_view_reports', 'can_print_reports',
                ],
            ],
            [
                'name' => 'Comptable',
                'slug' => 'comptable',
                'permissions' => [
                    'can_view_dashboard',
                    'can_view_payments', 'can_create_payments', 'can_edit_payments', 'can_validate_payments', 'can_print_receipts', 'can_export_payments',
                    'can_view_inscriptions', 'can_print_inscriptions',
                    'can_view_students',
                    'can_view_reports', 'can_print_reports', 'can_export_reports',
                ],
            ],
            [
                'name' => 'Soutien Informatique',
                'slug' => 'soutien-informatique',
                'permissions' => [
                    'can_view_dashboard',
                    'can_view_users', 'can_create_users', 'can_edit_users',
                    'can_view_roles', 'can_create_roles', 'can_edit_roles',
                    'can_view_students', 'can_edit_students',
                    'can_view_courses', 'can_edit_courses',
                ],
            ],
            [
                'name' => 'Professeur',
                'slug' => 'professeur',
                'permissions' => [
                    'can_view_dashboard',
                    'can_view_courses', 'can_print_courses',
                    'can_view_students', 'can_print_students',
                    'can_view_grades', 'can_create_grades', 'can_edit_grades', 'can_print_grades', 'can_export_grades',
                ],
            ],
            [
                'name' => 'Étudiant',
                'slug' => 'etudiant',
                'permissions' => [
                    'can_view_dashboard',
                    'can_view_courses',
                    'can_view_grades',
                    'can_view_payments',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionNames = $roleData['permissions'];
            unset($roleData['permissions']);
            
            $role = Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                array_merge($roleData, [
                    'uuid' => Str::uuid()->toString(),
                ])
            );

            // Attacher les permissions
            if (in_array('*', $permissionNames)) {
                $role->permissions()->sync($permissions->pluck('id'));
            } else {
                $permissionIds = $permissions->whereIn('slug', $permissionNames)->pluck('id');
                $role->permissions()->sync($permissionIds);
            }
        }

        $this->command->info('✅ Roles et permissions créés avec succès!');
    }

    /**
     * Créer toutes les permissions
     */
    protected function createPermissions()
    {
        $permissions = [
            // Dashboard
            ['name' => 'Voir le tableau de bord', 'slug' => 'can_view_dashboard', 'category' => 'dashboard'],
            ['name' => 'Imprimer les rapports', 'slug' => 'can_print_reports', 'category' => 'dashboard'],
            ['name' => 'Exporter les données', 'slug' => 'can_export_data', 'category' => 'dashboard'],
            
            // Utilisateurs
            ['name' => 'Voir les utilisateurs', 'slug' => 'can_view_users', 'category' => 'users'],
            ['name' => 'Créer les utilisateurs', 'slug' => 'can_create_users', 'category' => 'users'],
            ['name' => 'Modifier les utilisateurs', 'slug' => 'can_edit_users', 'category' => 'users'],
            ['name' => 'Supprimer les utilisateurs', 'slug' => 'can_delete_users', 'category' => 'users'],
            ['name' => 'Imprimer les utilisateurs', 'slug' => 'can_print_users', 'category' => 'users'],
            
            // Étudiants
            ['name' => 'Voir les étudiants', 'slug' => 'can_view_students', 'category' => 'students'],
            ['name' => 'Créer les étudiants', 'slug' => 'can_create_students', 'category' => 'students'],
            ['name' => 'Modifier les étudiants', 'slug' => 'can_edit_students', 'category' => 'students'],
            ['name' => 'Supprimer les étudiants', 'slug' => 'can_delete_students', 'category' => 'students'],
            ['name' => 'Imprimer les étudiants', 'slug' => 'can_print_students', 'category' => 'students'],
            ['name' => 'Exporter les étudiants', 'slug' => 'can_export_students', 'category' => 'students'],
            
            // Inscriptions
            ['name' => 'Voir les inscriptions', 'slug' => 'can_view_inscriptions', 'category' => 'inscriptions'],
            ['name' => 'Créer les inscriptions', 'slug' => 'can_create_inscriptions', 'category' => 'inscriptions'],
            ['name' => 'Modifier les inscriptions', 'slug' => 'can_edit_inscriptions', 'category' => 'inscriptions'],
            ['name' => 'Supprimer les inscriptions', 'slug' => 'can_delete_inscriptions', 'category' => 'inscriptions'],
            ['name' => 'Approuver les inscriptions', 'slug' => 'can_approve_inscriptions', 'category' => 'inscriptions'],
            ['name' => 'Rejeter les inscriptions', 'slug' => 'can_reject_inscriptions', 'category' => 'inscriptions'],
            ['name' => 'Imprimer les inscriptions', 'slug' => 'can_print_inscriptions', 'category' => 'inscriptions'],
            
            // Paiements
            ['name' => 'Voir les paiements', 'slug' => 'can_view_payments', 'category' => 'finance'],
            ['name' => 'Créer les paiements', 'slug' => 'can_create_payments', 'category' => 'finance'],
            ['name' => 'Modifier les paiements', 'slug' => 'can_edit_payments', 'category' => 'finance'],
            ['name' => 'Supprimer les paiements', 'slug' => 'can_delete_payments', 'category' => 'finance'],
            ['name' => 'Valider les paiements', 'slug' => 'can_validate_payments', 'category' => 'finance'],
            ['name' => 'Imprimer les reçus', 'slug' => 'can_print_receipts', 'category' => 'finance'],
            ['name' => 'Exporter les paiements', 'slug' => 'can_export_payments', 'category' => 'finance'],
            
            // Cours
            ['name' => 'Voir les cours', 'slug' => 'can_view_courses', 'category' => 'courses'],
            ['name' => 'Créer les cours', 'slug' => 'can_create_courses', 'category' => 'courses'],
            ['name' => 'Modifier les cours', 'slug' => 'can_edit_courses', 'category' => 'courses'],
            ['name' => 'Supprimer les cours', 'slug' => 'can_delete_courses', 'category' => 'courses'],
            ['name' => 'Imprimer les cours', 'slug' => 'can_print_courses', 'category' => 'courses'],
            
            // Notes
            ['name' => 'Voir les notes', 'slug' => 'can_view_grades', 'category' => 'grades'],
            ['name' => 'Créer les notes', 'slug' => 'can_create_grades', 'category' => 'grades'],
            ['name' => 'Modifier les notes', 'slug' => 'can_edit_grades', 'category' => 'grades'],
            ['name' => 'Supprimer les notes', 'slug' => 'can_delete_grades', 'category' => 'grades'],
            ['name' => 'Imprimer les notes', 'slug' => 'can_print_grades', 'category' => 'grades'],
            ['name' => 'Exporter les notes', 'slug' => 'can_export_grades', 'category' => 'grades'],
            
            // Rôles et Permissions
            ['name' => 'Voir les rôles', 'slug' => 'can_view_roles', 'category' => 'roles'],
            ['name' => 'Créer les rôles', 'slug' => 'can_create_roles', 'category' => 'roles'],
            ['name' => 'Modifier les rôles', 'slug' => 'can_edit_roles', 'category' => 'roles'],
            ['name' => 'Supprimer les rôles', 'slug' => 'can_delete_roles', 'category' => 'roles'],
            
            // Rapports
            ['name' => 'Voir les rapports', 'slug' => 'can_view_reports', 'category' => 'reports'],
            ['name' => 'Créer les rapports', 'slug' => 'can_create_reports', 'category' => 'reports'],
            ['name' => 'Imprimer les rapports', 'slug' => 'can_print_reports', 'category' => 'reports'],
            ['name' => 'Exporter les rapports', 'slug' => 'can_export_reports', 'category' => 'reports'],
        ];

        $createdPermissions = collect();
        foreach ($permissions as $permData) {
            $permission = Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                array_merge($permData, [
                    'uuid' => Str::uuid()->toString(),
                ])
            );
            $createdPermissions->push($permission);
        }

        return $createdPermissions;
    }
}
