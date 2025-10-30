<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Paiement;
use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\PersonalInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ResourceNotFoundException;

class PaiementService
{
    public function __construct(
        protected FileStorageService $fileStorageService
    ) {}

    /**
     * Récupérer tous les paiements avec filtres et pagination
     */
    public function getAll(array $filters, int $perPage = 15)
    {
        $query = Paiement::query()->with(['student', 'quittanceFile']);

        // Recherche globale
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%")
                  ->orWhere('numero_compte', 'like', "%{$search}%");
            });
        }

        // Filtre par statut
        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        // Filtre par matricule
        if (!empty($filters['matricule'])) {
            $query->where('matricule', $filters['matricule']);
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
        $student = Student::where('student_id_number', $matricule)->first();
        
        if (!$student) {
            throw new ResourceNotFoundException('Étudiant');
        }

        // Chercher les informations personnelles
        $personalInfo = PersonalInformation::where(function($query) use ($matricule) {
            $query->whereJsonContains('contacts', $matricule)
                  ->orWhereJsonContains('contacts', [$matricule]);
        })->first();

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
            'email' => $personalInfo?->email ?? $student->email ?? null,
            'tel' => $personalInfo && is_array($personalInfo->contacts ?? null) ? ($personalInfo->contacts[0] ?? null) : $matricule,
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
            // Générer une référence unique
            $data['reference'] = $this->generateReference();

            // Vérifier que l'étudiant existe
            $student = Student::where('student_id_number', $data['matricule'])->first();
            if (!$student) {
                throw new ResourceNotFoundException("Étudiant avec le matricule {$data['matricule']}");
            }

            // Upload de la quittance (userId = null pour les soumissions publiques)
            $uploadedQuittance = $this->fileStorageService->uploadFile(
                uploadedFile: $quittanceFile,
                userId: null, // Pas d'utilisateur authentifié pour les paiements publics
                visibility: 'private',
                collection: 'quittances',
                moduleName: 'Finance',
                moduleResourceType: 'Paiement',
                metadata: [
                    'matricule' => $data['matricule'],
                    'montant' => $data['montant'],
                    'student_id' => $student->id, // ID de l'étudiant pour référence
                ]
            );

            // Créer le paiement
            $paiement = Paiement::create([
                'matricule' => $data['matricule'],
                'montant' => $data['montant'],
                'reference' => $data['reference'],
                'numero_compte' => $data['numero_compte'],
                'date_versement' => $data['date_versement'],
                'motif' => $data['motif'] ?? null,
                'email' => $data['email'] ?? null,
                'contact' => $data['contact'] ?? null,
                'statut' => 'attente',
                'quittance_id' => $uploadedQuittance->id,
            ]);

            // Mettre à jour la relation du fichier
            $uploadedQuittance->update(['module_resource_id' => $paiement->id]);

            Log::info('Paiement créé avec succès', [
                'paiement_id' => $paiement->id,
                'reference' => $paiement->reference,
                'matricule' => $paiement->matricule,
            ]);

            return $paiement;
        });
    }

    /**
     * Récupérer un paiement par référence
     */
    public function getByReference(string $reference): ?Paiement
    {
        return Paiement::with(['student', 'quittanceFile'])
            ->where('reference', $reference)
            ->first();
    }

    /**
     * Mettre à jour le statut d'un paiement
     */
    public function updateStatus(Paiement $paiement, string $status, ?string $observation = null): Paiement
    {
        $paiement->update([
            'statut' => $status,
            'observation' => $observation,
        ]);

        Log::info('Statut du paiement mis à jour', [
            'paiement_id' => $paiement->id,
            'reference' => $paiement->reference,
            'new_status' => $status,
        ]);

        return $paiement->fresh(['student', 'quittanceFile']);
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
                if ($paiement->quittanceFile) {
                    $this->fileStorageService->forceDeleteFile($paiement->quittanceFile, $paiement->student?->id ?? 1);
                }

                $paiement->delete();

                Log::info('Paiement supprimé', [
                    'paiement_id' => $paiement->id,
                    'reference' => $paiement->reference,
                ]);

                return true;
            } catch (Exception $e) {
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
            'attente' => Paiement::where('statut', 'attente')->count(),
            'accepte' => Paiement::where('statut', 'accepte')->count(),
            'rejete' => Paiement::where('statut', 'rejete')->count(),
            'montant_total' => Paiement::where('statut', 'accepte')->sum('montant'),
        ];
    }
}
