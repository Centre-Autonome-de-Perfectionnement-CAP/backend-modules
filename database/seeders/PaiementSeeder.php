<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Finance\Models\Paiement;
use App\Modules\Inscription\Models\Student;
use Illuminate\Support\Str;

class PaiementSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::limit(3)->get();
        
        if ($students->isEmpty()) {
            $this->command->warn('⚠️  Aucun étudiant trouvé. Exécutez StudentSeeder d\'abord.');
            return;
        }

        $paiements = [];
        
        foreach ($students as $index => $student) {
            // Frais d'inscription
            $paiements[] = [
                'reference' => 'PAY-' . strtoupper(Str::random(8)),
                'matricule' => $student->student_id_number,
                'montant' => 50000,
                'date_versement' => now()->subDays(90 + $index),
                'motif' => 'Frais d\'inscription',
                'statut' => 'en_attente',
                'email' => $student->email,
                'contact' => '+225 07 00 00 ' . sprintf('%02d', $index + 1),
                'numero_compte' => 'CI01234567890' . sprintf('%03d', $index + 1),
            ];

            // Premier semestre
            $paiements[] = [
                'reference' => 'PAY-' . strtoupper(Str::random(8)),
                'matricule' => $student->student_id_number,
                'montant' => 250000,
                'date_versement' => now()->subDays(60 + $index),
                'motif' => 'Frais de scolarité 1er semestre',
                'statut' => 'en_attente',
                'email' => $student->email,
                'contact' => '+225 07 00 00 ' . sprintf('%02d', $index + 1),
                'numero_compte' => 'CI01234567890' . sprintf('%03d', $index + 1),
            ];

            // Deuxième semestre (en attente pour certains)
            $statut = $index === 0 ? 'en_attente' : 'accepte';
            $paiements[] = [
                'reference' => 'PAY-' . strtoupper(Str::random(8)),
                'matricule' => $student->student_id_number,
                'montant' => 250000,
                'date_versement' => now()->subDays(30 + $index),
                'motif' => 'Frais de scolarité 2ème semestre',
                'statut' => $statut,
                'email' => $student->email,
                'contact' => '+225 07 00 00 ' . sprintf('%02d', $index + 1),
                'numero_compte' => 'CI01234567890' . sprintf('%03d', $index + 1),
                'observation' => $statut === 'en_attente' ? 'En attente de validation' : null,
            ];

            // Frais d'examen
            if ($index < 2) {
                $paiements[] = [
                    'reference' => 'PAY-' . strtoupper(Str::random(8)),
                    'matricule' => $student->student_id_number,
                    'montant' => 25000,
                    'date_versement' => now()->subDays(10 + $index),
                    'motif' => 'Frais d\'examen',
                    'statut' => 'en_attente',
                    'email' => $student->email,
                    'contact' => '+225 07 00 00 ' . sprintf('%02d', $index + 1),
                    'numero_compte' => 'CI01234567890' . sprintf('%03d', $index + 1),
                ];
            }
        }

        foreach ($paiements as $paiementData) {
            Paiement::updateOrCreate(
                ['reference' => $paiementData['reference']],
                array_merge($paiementData, ['uuid' => Str::uuid()->toString()])
            );
        }

        $this->command->info('✅ Paiements créés avec succès!');
        $this->command->info("💰 Total: " . count($paiements) . " paiements créés");
    }
}
