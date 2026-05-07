<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentRequestSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'pending',
            'secretaire_review',
            'comptable_review',
            'chef_division_review',
            'chef_cap_review',
            'secretaire_da_review',
            'directrice_adjointe_review',
            'secretaire_directeur_review',
            'directeur_review',
            'validation_finale',
            'ready',
            'rejected',
        ];

        $types = [
            'Attestation de scolarité',
            'Relevé de notes',
            'Certificat de réussite',
            'Diplôme',
            'Attestation de stage',
        ];

        $chefDivisionTypes = ['formation_distance', 'formation_continue', null];

        for ($i = 1; $i <= 20; $i++) {
            $status = $statuses[array_rand($statuses)];
            $createdAt = Carbon::now()->subDays(rand(0, 30));

            DB::table('document_requests')->insert([
                'nom'                    => fake()->lastName(),
                'prenom'                 => fake()->firstName(),
                'email'                  => fake()->unique()->safeEmail(),
                'demandeur_whatsapp'     => '+229' . rand(90000000, 99999999),
                'type_document'          => $types[array_rand($types)],
                'motif'                  => fake()->sentence(),
                'nombre_exemplaires'     => rand(1, 3),
                'status'                 => $status,
                'has_flag'               => rand(0, 5) === 0, // 1 chance sur 6
                'chef_division_type'     => $chefDivisionTypes[array_rand($chefDivisionTypes)],
                'complement_files'       => null,
                'complement_submitted_at' => null,
                'is_in_correction_circuit' => false,
                'correction_origin_role'  => null,
                'correction_origin_status' => null,
                'processed_by_secretaire_da_id'          => null,
                'processed_by_directrice_adjointe_id'    => null,
                'processed_by_secretaire_directeur_id'   => null,
                'processed_by_directeur_id'              => null,
                'secretaire_da_reviewed_at'              => null,
                'directrice_adjointe_reviewed_at'        => null,
                'secretaire_directeur_reviewed_at'       => null,
                'directeur_reviewed_at_new'              => null,
                'validation_finale_at'                   => $status === 'ready' ? $createdAt->copy()->addHours(rand(1, 48)) : null,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt->copy()->addHours(rand(1, 24)),
            ]);
        }
    }
}