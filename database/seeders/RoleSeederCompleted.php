<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Modules\Stockage\Models\Role;

class RoleSeederCompleted extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          $roles = [
            [
                'name' => 'Responsable Division-Formation à Distance',
                'slug' => 'rd-fad',
            ],
            [
                'name' => 'Responsable Division-Formation Continue',
                'slug' => 'rd-fc',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        
    }
}
}
