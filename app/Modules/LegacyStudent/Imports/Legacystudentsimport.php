<?php

namespace App\Modules\LegacyStudent\Imports;

use App\Modules\Inscription\Models\Department;
use App\Models\LegacyStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Lit un fichier Excel d'anciens étudiants et les importe en base.
 *
 * On utilise ToCollection (plutôt que ToModel) pour garder le contrôle
 * total ligne par ligne : valider, chercher la filière par nom, détecter
 * les doublons, et continuer même si une ligne échoue - au lieu de laisser
 * le package Excel gérer ça tout seul et bloquer sur la première erreur.
 */
class LegacyStudentsImport implements ToCollection, WithHeadingRow
{
    /** @var array Lignes importées avec succès */
    public array $created = [];

    /** @var array Lignes ignorées car le matricule existe déjà */
    public array $duplicates = [];

    /** @var array Lignes invalides, avec le détail de l'erreur */
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // +2 car WithHeadingRow retire la ligne d'en-têtes,
            // et Excel compte à partir de 1, pas de 0.
            $lineNumber = $index + 2;

            $this->processRow($row, $lineNumber);
        }
    }

    protected function processRow(Collection $row, int $lineNumber): void
    {
        $data = [
            'matricule'         => trim((string) ($row['matricule'] ?? '')),
            'first_name'        => trim((string) ($row['prenom'] ?? '')),
            'last_name'         => trim((string) ($row['nom'] ?? '')),
            'email'             => trim((string) ($row['email'] ?? '')),
            'phone'             => trim((string) ($row['telephone'] ?? '')),
            'enrollment_year'   => $row['annee_inscription'] ?? null,
            'filiere_name'      => trim((string) ($row['filiere'] ?? '')),
            'notes_admin'       => trim((string) ($row['notes'] ?? '')) ?: null,
        ];

        // 1. Validation des champs de base (mêmes règles que le formulaire public)
        $validator = Validator::make($data, [
            'matricule'         => ['required', 'string', 'max:50'],
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'email'             => ['required', 'email', 'max:150'],
            'phone'             => ['required', 'string', 'max:20'],
            'enrollment_year'   => ['required', 'integer', 'min:1970', 'max:2022'],
            'filiere_name'      => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            $this->errors[] = [
                'line'   => $lineNumber,
                'reason' => $validator->errors()->first(),
            ];
            return;
        }

        // 2. Doublon : le matricule existe déjà en base ?
        if (LegacyStudent::where('matricule', $data['matricule'])->exists()) {
            $this->duplicates[] = [
                'line'      => $lineNumber,
                'matricule' => $data['matricule'],
            ];
            return;
        }

        // 3. Traduire le nom de la filière en UUID (colonne 'filiere' du fichier
        //    contient un nom humain, pas l'id technique)
        $department = Department::where('name', $data['filiere_name'])->first();

        if (! $department) {
            $this->errors[] = [
                'line'   => $lineNumber,
                'reason' => "Filière introuvable : \"{$data['filiere_name']}\"",
            ];
            return;
        }

        // 4. Tout est bon, on crée le dossier
        try {
            DB::transaction(function () use ($data, $department) {
                $student = LegacyStudent::create([
                    'matricule'       => $data['matricule'],
                    'first_name'      => $data['first_name'],
                    'last_name'       => $data['last_name'],
                    'email'           => $data['email'],
                    'phone'           => $data['phone'],
                    'enrollment_year' => $data['enrollment_year'],
                    'notes_admin'     => $data['notes_admin'],
                    'status'          => 'pending',
                ]);

                $student->departments()->attach($department->id, [
                    'cycle_id' => $department->cycle_id,
                ]);

                $this->created[] = $student->matricule;
            });
        } catch (\Throwable $e) {
            $this->errors[] = [
                'line'   => $lineNumber,
                'reason' => 'Erreur lors de la création : ' . $e->getMessage(),
            ];
        }
    }
}