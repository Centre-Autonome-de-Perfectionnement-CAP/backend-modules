<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Paiement;
use App\Modules\Finance\Mail\QuittanceConfirmation;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\PersonalInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\ResourceNotFoundException;

class PaiementService
{
    // Plus besoin de FileStorageService
    // Les fichiers seront stockés directement dans storage/app/private/paiements/{matricule}/

    /**
     * Récupérer tous les paiements avec filtres et pagination
     */
    public function getAll(array $filters, int $perPage = 15)
    {
        $query = Paiement::query()->with(['student']);

        // Recherche globale
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('student_id_number', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        // Filtre par statut
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtre par matricule
        if (!empty($filters['student_id_number'])) {
            $query->where('student_id_number', $filters['student_id_number']);
        }

        // Filtre par plage de dates
        if (!empty($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        // Tri par défaut: les plus récents en premier
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Récupérer les informations d'un étudiant par matricule
     */
    public function getStudentInfo(string $matricule): array
    {
        $student = Student::with('pendingStudents.personalInformation')->where('student_id_number', $matricule)->first();
        
        if (!$student) {
            throw new ResourceNotFoundException('Étudiant');
        }

        // Récupérer les informations personnelles via la relation
        $personalInfo = $student->personalInformation;

        // Récupérer les filières/départements via student_pending_student
        $filieres = DB::table('student_pending_student')
            ->join('pending_students', 'student_pending_student.pending_student_id', '=', 'pending_students.id')
            ->join('departments', 'pending_students.department_id', '=', 'departments.id')
            ->where('student_pending_student.student_id', $student->id)
            ->select('departments.id', 'departments.name as nom')
            ->distinct()
            ->get()
            ->toArray();

        $hasNoFilieres = empty($filieres);

        return [
            'id' => $student->id,
            'student_id_number' => $student->student_id_number,
            'nom' => $personalInfo?->last_name ?? null,
            'prenoms' => $personalInfo?->first_names ?? null,
            'email' => $personalInfo?->email ?? null,
            'tel' => $personalInfo && is_array($personalInfo->contacts ?? null) ? ($personalInfo->contacts[0] ?? null) : null,
            'filieres' => $filieres,
            'has_no_filieres' => $hasNoFilieres,
            'message' => $hasNoFilieres ? 'Aucune filière associée à ce matricule. Veuillez d\'abord soumettre une candidature.' : null,
        ];
    }

    /**
     * Créer un nouveau paiement
     */
    public function create(array $data, $quittanceFile): Paiement
    {
        return DB::transaction(function () use ($data, $quittanceFile) {
            // La référence vient de la quittance (extraite par OCR)
            // Elle est déjà validée comme unique dans CreatePaiementRequest

            // Vérifier que l'étudiant existe
            $student = Student::where('student_id_number', $data['matricule'])->first();
            if (!$student) {
                throw new ResourceNotFoundException("Étudiant avec le matricule {$data['matricule']}");
            }

            // Récupérer le student_pending_student_id à partir du matricule et department_id
            // Le department_id est requis pour identifier correctement l'inscription
            if (empty($data['department_id'])) {
                throw new \InvalidArgumentException('Le department_id est requis pour créer un paiement');
            }
            
            $studentPendingStudent = StudentPendingStudent::query()
                ->where('student_id', $student->id)
                ->whereHas('pendingStudent', function ($q) use ($data) {
                    $q->where('department_id', $data['department_id']);
                })
                ->latest('id') // Prendre le plus récent si plusieurs
                ->first();
            
            if (!$studentPendingStudent) {
                throw new ResourceNotFoundException(
                    "Aucune inscription trouvée pour l'étudiant {$data['matricule']} dans le département {$data['department_id']}"
                );
            }
            
            $studentPendingStudentId = $studentPendingStudent->id;
            
            Log::info('Récupération student_pending_student_id', [
                'student_id' => $student->id,
                'department_id' => $data['department_id'],
                'student_pending_student_id' => $studentPendingStudentId,
                'pending_student_id' => $studentPendingStudent->pending_student_id,
            ]);

            // Stockage direct de la quittance
            // Structure: storage/app/private/payments/{matricule}/{année}/{filename}
            $year = date('Y');
            $filename = uniqid('receipt_' . $data['matricule'] . '_') . '.' . $quittanceFile->getClientOriginalExtension();
            $receiptPath = "payments/{$data['matricule']}/{$year}/{$filename}";
            
            // Stocker le fichier
            Storage::disk('local')->put($receiptPath, file_get_contents($quittanceFile->getRealPath()));

            // Créer le paiement
            $paiement = Paiement::create([
                'student_id_number' => $data['matricule'],
                'student_pending_student_id' => $studentPendingStudentId,
                'amount' => $data['montant'],
                'reference' => $data['reference'],
                'account_number' => $data['numero_compte'],
                'payment_date' => $data['date_versement'],
                'purpose' => $data['motif'] ?? null,
                'email' => $data['email'] ?? null,
                'contact' => $data['contact'] ?? null,
                'status' => 'pending',
                'receipt_path' => $receiptPath,
            ]);

            Log::info('Paiement créé avec succès', [
                'paiement_id' => $paiement->id,
                'reference' => $paiement->reference,
                'student_id_number' => $paiement->student_id_number,
            ]);

            // Envoyer un email de confirmation si un email est fourni
            try {
                if (!empty($paiement->email)) {
                    // Charger les informations personnelles pour personnaliser le mail
                    $student->load('pendingStudents.personalInformation');
                    $personalInfo = $student->personalInformation;
                    
                    $mailData = [
                        'reference' => $paiement->reference,
                        'matricule' => $paiement->student_id_number,
                        'montant' => $paiement->amount,
                        'numero_compte' => $paiement->account_number,
                        'date_versement' => $paiement->payment_date,
                        'motif' => $paiement->purpose,
                        'prenoms' => $personalInfo?->first_names ?? 'Étudiant(e)',
                        'nom' => $personalInfo?->last_name ?? '',
                    ];
                    
                    Mail::to($paiement->email)->send(new QuittanceConfirmation($mailData));
                    
                    Log::info('Email de confirmation de quittance envoyé', [
                        'paiement_id' => $paiement->id,
                        'email' => $paiement->email,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Échec lors de l\'envoi du mail de confirmation de quittance', [
                    'paiement_id' => $paiement->id,
                    'error' => $e->getMessage(),
                ]);
                // Ne pas bloquer la création du paiement si l'email échoue
            }

            return $paiement;
        });
    }

    /**
     * Récupérer un paiement par référence
     */
    public function getByReference(string $reference): ?Paiement
    {
        return Paiement::with(['student'])
            ->where('reference', $reference)
            ->first();
    }

    /**
     * Mettre à jour le statut d'un paiement
     */
    public function updateStatus(Paiement $paiement, string $status, ?string $observation = null): Paiement
    {
        $paiement->update([
            'status' => $status,
            'observation' => $observation,
        ]);

        Log::info('Statut du paiement mis à jour', [
            'paiement_id' => $paiement->id,
            'reference' => $paiement->reference,
            'new_status' => $status,
        ]);

        return $paiement->fresh(['student']);
    }

    /**
     * Générer une référence unique pour un paiement
     */
    protected function generateReference(): string
    {
        do {
            $reference = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (Paiement::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Supprimer un paiement
     */
    public function delete(Paiement $paiement): bool
    {
        return DB::transaction(function () use ($paiement) {
            try {
                // Supprimer le fichier de quittance
                if ($paiement->receipt_path && Storage::disk('local')->exists($paiement->receipt_path)) {
                    Storage::disk('local')->delete($paiement->receipt_path);
                }

                $paiement->delete();

                Log::info('Paiement supprimé', [
                    'paiement_id' => $paiement->id,
                    'reference' => $paiement->reference,
                ]);

                return true;
            } catch (\Exception $e) {
                Log::error('Erreur lors de la suppression du paiement', [
                    'paiement_id' => $paiement->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Statistiques des paiements
     */
    public function getStatistics(): array
    {
        return [
            'total' => Paiement::count(),
            'pending' => Paiement::where('status', 'pending')->count(),
            'approved' => Paiement::where('status', 'approved')->count(),
            'rejected' => Paiement::where('status', 'rejected')->count(),
            'total_amount' => Paiement::where('status', 'approved')->sum('amount'),
        ];
    }
}
