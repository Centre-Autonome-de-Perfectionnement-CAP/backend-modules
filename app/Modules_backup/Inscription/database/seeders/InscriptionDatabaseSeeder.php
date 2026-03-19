<?php

namespace App\Modules\Inscription\Database\Seeders;

use Illuminate\Database\Seeder;

class InscriptionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CycleSeeder::class,
            DepartmentSeeder::class,
        ]);
    }
}
