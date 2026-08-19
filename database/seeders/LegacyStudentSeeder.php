<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\LegacyStudent\Models\LegacyStudent;
use App\Modules\LegacyStudent\Models\LegacyStudentServiceRequest;
use App\Modules\Inscription\Models\Department;

class LegacyStudentSeeder extends Seeder
{
    public function run(): void
    {
        $gc = Department::where('code', 'GC')->first();
        $gei = Department::where('code', 'GEI')->first();
        $gme = Department::where('code', 'GME')->first();

        // Étudiant 1
        $s1 = LegacyStudent::updateOrCreate(
            ['matricule' => '18-0452-EPAC'],
            [
                'last_name' => 'DOSSA',
                'first_name' => 'Jean-Baptiste',
                'email' => 'jean.dossa@gmail.com',
                'phone' => '+229 97 22 33 44',
                'enrollment_year' => 2018,
                'status' => 'pending',
                'department_id' => $gc?->id,
            ]
        );
        if ($gc) $s1->departments()->syncWithoutDetaching([$gc->id]);

        LegacyStudentServiceRequest::updateOrCreate(
            ['matricule' => '18-0452-EPAC', 'service_type' => 'quitus_memoire'],
            [
                'legacy_student_id' => $s1->id,
                'student_name' => 'DOSSA Jean-Baptiste',
                'email' => 'jean.dossa@gmail.com',
                'phone' => '+229 97 22 33 44',
                'service_name' => 'Quitus de Mémoire de Soutenance',
                'filiere_name' => 'Génie Civil',
                'enrollment_year' => 2018,
                'status' => 'pending',
            ]
        );

        LegacyStudentServiceRequest::updateOrCreate(
            ['matricule' => '18-0452-EPAC', 'service_type' => 'attestation_diplome'],
            [
                'legacy_student_id' => $s1->id,
                'student_name' => 'DOSSA Jean-Baptiste',
                'email' => 'jean.dossa@gmail.com',
                'phone' => '+229 97 22 33 44',
                'service_name' => 'Attestation de Diplôme (Rétroactive)',
                'filiere_name' => 'Génie Civil',
                'enrollment_year' => 2018,
                'status' => 'in_progress',
                'processed_by' => 'Secrétariat Scolarité',
            ]
        );

        // Étudiant 2
        $s2 = LegacyStudent::updateOrCreate(
            ['matricule' => '15-0120-CAP'],
            [
                'last_name' => 'HOUNNOU',
                'first_name' => 'Astride',
                'email' => 'astride.hounnou@yahoo.fr',
                'phone' => '+229 96 11 88 77',
                'enrollment_year' => 2015,
                'status' => 'validated',
                'validated_by' => 'Secrétariat Scolarité (Mme SOSSOU)',
                'validated_at' => now()->subDays(2),
                'department_id' => $gei?->id,
            ]
        );
        if ($gei) $s2->departments()->syncWithoutDetaching([$gei->id]);

        LegacyStudentServiceRequest::updateOrCreate(
            ['matricule' => '15-0120-CAP', 'service_type' => 'demande_bulletin'],
            [
                'legacy_student_id' => $s2->id,
                'student_name' => 'HOUNNOU Astride',
                'email' => 'astride.hounnou@yahoo.fr',
                'phone' => '+229 96 11 88 77',
                'service_name' => 'Demande de Relevé de Notes / Bulletin',
                'filiere_name' => 'Génie Électrique et Informatique',
                'enrollment_year' => 2015,
                'status' => 'delivered',
                'processed_by' => 'Scolarité CAP',
                'processed_at' => now()->subDay(),
            ]
        );

        // Étudiant 3
        $s3 = LegacyStudent::updateOrCreate(
            ['matricule' => '20-0899-EPAC'],
            [
                'last_name' => 'ADANHO',
                'first_name' => 'Gilles',
                'email' => 'gilles.adanho@gmail.com',
                'phone' => '+229 95 44 33 22',
                'enrollment_year' => 2020,
                'status' => 'rejected',
                'rejection_reason' => 'Pièce d\'identité non conforme et incohérence sur le matricule déclaré.',
                'department_id' => $gme?->id,
            ]
        );
        if ($gme) $s3->departments()->syncWithoutDetaching([$gme->id]);
    }
}
