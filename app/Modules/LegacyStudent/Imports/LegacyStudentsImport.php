<?php

namespace App\Modules\LegacyStudent\Imports;

use App\Modules\Inscription\Models\Department;
use App\Modules\LegacyStudent\Models\LegacyStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import de masse des fiches d'anciens étudiants (< 2023) depuis Excel.
 */
class LegacyStudentsImport implements ToCollection, WithHeadingRow
{
    /** @var array Lignes importées avec succès */
    public array $created = [];

    /** @var array Lignes ignorées car déjà existantes */
    public array $duplicates = [];

    /** @var array Lignes invalides avec détail de l'erreur */
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $this->processRow($row, $lineNumber);
        }
    }

    protected function processRow(Collection $row, int $lineNumber): void
    {
        $matricule = strtoupper(trim((string) ($row['matricule'] ?? '')));
        $lastName  = strtoupper(trim((string) ($row['nom'] ?? $row['last_name'] ?? '')));
        $firstName = trim((string) ($row['prenom'] ?? $row['prenoms'] ?? $row['first_name'] ?? ''));
        $email     = trim((string) ($row['email'] ?? ''));
        $phone     = trim((string) ($row['telephone'] ?? $row['phone'] ?? $row['contact'] ?? ''));
        $year      = $row['annee_inscription'] ?? $row['enrollment_year'] ?? $row['promo'] ?? null;
        $filiere   = trim((string) ($row['filiere'] ?? $row['departement'] ?? ''));
        $cycle     = trim((string) ($row['cycle'] ?? '')) ?: null;
        $dob       = $row['date_naissance'] ?? $row['date_of_birth'] ?? null;
        $pob       = trim((string) ($row['lieu_naissance'] ?? $row['place_of_birth'] ?? '')) ?: null;
        $notes     = trim((string) ($row['notes'] ?? $row['notes_admin'] ?? '')) ?: null;

        $data = [
            'matricule'       => $matricule,
            'last_name'       => $lastName,
            'first_name'      => $firstName,
            'date_of_birth'   => $dob,
            'place_of_birth'  => $pob,
            'cycle'           => $cycle,
            'email'           => $email,
            'phone'           => $phone,
            'enrollment_year' => $year,
            'filiere_name'    => $filiere,
            'notes_admin'     => $notes,
        ];

        // 1. Validation
        $validator = Validator::make($data, [
            'matricule'       => ['required', 'string', 'max:50'],
            'last_name'       => ['required', 'string', 'max:100'],
            'first_name'      => ['required', 'string', 'max:100'],
            'email'           => ['nullable', 'email', 'max:150'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'enrollment_year' => ['required', 'integer', 'min:1970', 'max:2022'],
            'filiere_name'    => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            $this->errors[] = [
                'line'   => $lineNumber,
                'reason' => $validator->errors()->first(),
            ];
            return;
        }

        // 2. Gestion anti-doublon
        $existing = LegacyStudent::where('matricule', $matricule)->first();
        if ($existing) {
            $this->duplicates[] = [
                'line'      => $lineNumber,
                'matricule' => $matricule,
            ];
            return;
        }

        // 3. Résolution filière
        $department = null;
        if (!empty($filiere)) {
            $department = Department::where('name', 'like', "%{$filiere}%")
                ->orWhere('abbreviation', 'like', "%{$filiere}%")
                ->first();
        }

        // 4. Insertion en base
        try {
            DB::transaction(function () use ($data, $department) {
                $student = LegacyStudent::create([
                    'matricule'       => $data['matricule'],
                    'last_name'       => $data['last_name'],
                    'first_name'      => $data['first_name'],
                    'date_of_birth'   => !empty($data['date_of_birth']) ? date('Y-m-d', strtotime($data['date_of_birth'])) : null,
                    'place_of_birth'  => $data['place_of_birth'],
                    'cycle'           => $data['cycle'],
                    'email'           => $data['email'] ?: ($data['matricule'] . '@cap-epac.bj'),
                    'phone'           => $data['phone'] ?: 'N/A',
                    'enrollment_year' => (int) $data['enrollment_year'],
                    'notes_admin'     => $data['notes_admin'] ?: 'Importé via fichier Excel',
                    'status'          => 'validated',
                    'validated_by'    => 'Import Excel Scolarité',
                    'validated_at'    => now(),
                ]);

                if ($department) {
                    $student->departments()->attach($department->id, [
                        'cycle_id' => $department->cycle_id ?? null,
                    ]);
                }

                $this->created[] = $student->matricule;
            });
        } catch (\Throwable $e) {
            $this->errors[] = [
                'line'   => $lineNumber,
                'reason' => 'Erreur lors de l insertion : ' . $e->getMessage(),
            ];
        }
    }
}
