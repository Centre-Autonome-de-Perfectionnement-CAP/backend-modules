<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            AdministrationSeeder::class,
            // \App\Modules\Inscription\Database\Seeders\InscriptionDatabaseSeeder::class,
            CycleSeeder::class,
            DepartmentSeeder::class,
            AcademicYearCompleteSeeder::class,  // Années académiques avec périodes complètes
            // QuickAcademicYearSeeder::class,  // Alternative rapide pour développement
        ]);
    }
}
