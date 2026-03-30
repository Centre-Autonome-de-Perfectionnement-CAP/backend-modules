<?php

namespace App\Modules\Inscription\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class StudentListPdfService
{
    /**
     * Génère un PDF avec la liste des étudiants (Nom, Prénom, Téléphone)
     * 
     * @param array $students
     * @param string $departmentName
     * @return string Chemin du fichier PDF généré
     */
    public function generateStudentListPdf(array $students, string $departmentName): string
    {
        // Préparer les données pour le PDF
        $studentList = [];
        foreach ($students as $student) {
            $personalInfo = $student['personal_information'] ?? null;
            
            if ($personalInfo) {
                // Extraire le numéro de téléphone
                $telephone = 'N/A';
                if (is_array($personalInfo)) {
                    $contacts = $personalInfo['contacts'] ?? null;
                    if (is_array($contacts) && !empty($contacts)) {
                        // Prendre le premier contact
                        $telephone = $contacts[0] ?? 'N/A';
                    } elseif (is_string($contacts)) {
                        $telephone = $contacts;
                    }
                    // Sinon essayer phone_number
                    if ($telephone === 'N/A') {
                        $telephone = $personalInfo['phone_number'] ?? 'N/A';
                    }
                } else {
                    $contacts = $personalInfo->contacts ?? null;
                    if (is_array($contacts) && !empty($contacts)) {
                        $telephone = $contacts[0] ?? 'N/A';
                    } elseif (is_string($contacts)) {
                        $telephone = $contacts;
                    }
                    if ($telephone === 'N/A') {
                        $telephone = $personalInfo->phone_number ?? 'N/A';
                    }
                }

                $studentList[] = [
                    'nom' => is_array($personalInfo) 
                        ? ($personalInfo['last_name'] ?? 'N/A') 
                        : ($personalInfo->last_name ?? 'N/A'),
                    'prenom' => is_array($personalInfo) 
                        ? ($personalInfo['first_names'] ?? 'N/A') 
                        : ($personalInfo->first_names ?? 'N/A'),
                    'telephone' => $telephone,
                ];
            }
        }

        // Trier par nom
        usort($studentList, function($a, $b) {
            return strcmp($a['nom'], $b['nom']);
        });

        // Générer le PDF
        $pdf = Pdf::loadView('core::pdfs.student-list', [
            'students' => $studentList,
            'departmentName' => $departmentName,
            'totalStudents' => count($studentList),
            'generatedAt' => now()->format('d/m/Y à H:i'),
        ]);

        // Sauvegarder temporairement le PDF
        $filename = 'liste_etudiants_' . \Str::slug($departmentName) . '_' . time() . '.pdf';
        $path = storage_path('app/temp/' . $filename);
        
        // Créer le dossier temp s'il n'existe pas
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $pdf->save($path);

        return $path;
    }

    /**
     * Supprime un fichier PDF temporaire
     * 
     * @param string $path
     */
    public function deleteTempPdf(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
