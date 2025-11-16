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
                'student_id_number' => $student->student_id_number,
                'amount' => 50000,
                'payment_date' => now()->subDays(90 + $index),
                'purpose' => 'Frais d\'inscription',
                'status' => 'pending',
                'email' => $student->email,
                'contact' => '+225 07 00 00 ' . sprintf('%02d', $index + 1),
                'account_number' => 'CI01234567890' . sprintf('%03d', $index + 1),
            ];

            // Premier semestre
            $paiements[] = [
                'reference' => 'PAY-' . strtoupper(Str::random(8)),
                'student_id_number' => $student->student_id_number,
                'amount' => 250000,
                'payment_date' => now()->subDays(60 + $index),
                'purpose' => 'Frais de scolarité 1er semestre',
                'status' => 'pending',
                'email' => $student->email,
                'contact' => '+225 07 00 00 ' . sprintf('%02d', $index + 1),
                'account_number' => 'CI01234567890' . sprintf('%03d', $index + 1),
            ];

            // Deuxième semestre (en attente pour certains)
            $statut = $index === 0 ? 'pending' : 'approved';
            $paiements[] = [
                'reference' => 'PAY-' . strtoupper(Str::random(8)),
                'student_id_number' => $student->student_id_number,
                'amount' => 250000,
                'payment_date' => now()->subDays(30 + $index),
                'purpose' => 'Frais de scolarité 2ème semestre',
                'status' => $statut,
                'email' => $student->email,
                'contact' => '+225 07 00 00 ' . sprintf('%02d', $index + 1),
                'account_number' => 'CI01234567890' . sprintf('%03d', $index + 1),
                'observation' => $statut === 'pending' ? 'En attente de validation' : null,
            ];

            // Frais d'examen
            if ($index < 2) {
                $paiements[] = [
                    'reference' => 'PAY-' . strtoupper(Str::random(8)),
                    'student_id_number' => $student->student_id_number,
                    'amount' => 25000,
                    'payment_date' => now()->subDays(10 + $index),
                    'purpose' => 'Frais d\'examen',
                    'status' => 'pending',
                    'email' => $student->email,
                    'contact' => '+225 07 00 00 ' . sprintf('%02d', $index + 1),
                    'account_number' => 'CI01234567890' . sprintf('%03d', $index + 1),
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
