<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Centralise tous les envois de notifications du module Demandes.
 *
 * Règle : Email + WhatsApp toujours en parallèle, jamais l'un à la place de l'autre.
 * Un échec WhatsApp ne bloque jamais le workflow.
 *
 * Déclencheurs :
 *
 * → Étudiant :
 *   sendSoumission()           soumission demande         email + WhatsApp
 *   sendComplementEtudiant()   soumission complément      email + WhatsApp
 *   sendRejected()             rejet définitif            email + WhatsApp
 *   sendReady()                document prêt              email + WhatsApp
 *   sendDelivered()            document remis             email + WhatsApp
 *
 * → Secrétaire :
 *   sendSoumission()           notifie aussi la secrétaire à chaque soumission
 *   sendComplementSecretaire() notifie la secrétaire à chaque complément
 *
 * → Acteurs internes :
 *   notifyNextActor()          à chaque transmission de dossier (quel que soit le sens)
 */
class NotificationService
{
    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    // ═════════════════════════════════════════════════════════════════════════
    // NOTIFICATIONS ÉTUDIANT
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Soumission d'une demande.
     * Notifie : étudiant (email + WhatsApp) + secrétaire (email + WhatsApp).
     */
    public function sendSoumission(object $demande): void
    {
        $typeLabel    = WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);
        $matricule    = $etudiantInfo->matricule ?? '';

        // ── Email étudiant ────────────────────────────────────────────────────
        $this->sendMailToStudent($demande, [
            'view'    => 'core::emails.demande-soumission',
            'subject' => "Demande reçue — Réf : {$demande->reference}",
            'vars'    => [
                'reference'   => $demande->reference,
                'typeLabel'   => $typeLabel,
                'submittedAt' => now()->format('d/m/Y à H:i'),
                'email'       => $demande->email ?? '',
            ],
        ]);

        // ── WhatsApp étudiant ─────────────────────────────────────────────────
        if (!empty($demande->demandeur_whatsapp)) {
            $this->whatsApp->send(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateSoumission(
                    $demande->reference,
                    $typeLabel,
                    $demande->email ?? ''
                ),
                "soumission:{$demande->reference}"
            );
        }

        // ── Secrétaire : email + WhatsApp ─────────────────────────────────────
        $secretaires = $this->findUsersWithRole('secretaire', null);

        foreach ($secretaires as $sec) {
            // Email
            try {
                Mail::send(
                    'core::emails.dossier-transmis',
                    [
                        'destinataireNom'   => $sec->name,
                        'destinataireRole'  => 'Secrétaire',
                        'expediteurNom'     => 'Portail étudiant',
                        'expediteurRole'    => '',
                        'reference'         => $demande->reference,
                        'typeDocument'      => $typeLabel,
                        'etudiantNom'       => $etudiantNom,
                        'etudiantMatricule' => $matricule,
                        'dateTransmission'  => now()->format('d/m/Y à H:i'),
                        'commentaire'       => null,
                        'urlEspace'         => config('app.url') . '/dashboard',
                        'etablissement'     => config('app.name', 'CAP-EPAC'),
                    ],
                    fn($m) => $m->to($sec->email, $sec->name)
                               ->subject("Nouvelle demande — Réf : {$demande->reference}")
                );
            } catch (\Exception $e) {
                Log::error('[Notification] Erreur email secrétaire soumission', [
                    'error' => $e->getMessage(),
                    'ref'   => $demande->reference,
                ]);
            }

            // WhatsApp
            if (!empty($sec->phone)) {
                $this->whatsApp->send(
                    $sec->phone,
                    $this->whatsApp->templateNouvelleDemandeSecretaire(
                        destinataireNom: $sec->name,
                        reference:       $demande->reference,
                        typeDocument:    $typeLabel,
                        nomEtudiant:     $etudiantNom,
                        matricule:       $matricule,
                    ),
                    "soumission-secretaire:{$demande->reference}"
                );
            }
        }
    }

    /**
     * Rejet définitif du dossier.
     * Notifie : étudiant (email + WhatsApp).
     */
    public function sendRejected(object $demande, string $motif): void
    {
        $typeLabel = WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;

        $this->sendMailToStudent($demande, [
            'view'    => 'core::emails.demande-rejected',
            'subject' => "Votre demande a été rejetée — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => $typeLabel,
                'motif'     => $motif,
            ],
        ]);

        if (!empty($demande->demandeur_whatsapp)) {
            $this->whatsApp->send(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateRejete($demande->reference, $typeLabel, $motif),
                "rejet:{$demande->reference}"
            );
        }
    }

    /**
     * Dossier validé sous réserve.
     * Notifie : étudiant (email + WhatsApp).
     */
    public function sendSousReserve(object $demande, string $motif): void
    {
        $typeLabel = WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;

        $this->sendMailToStudent($demande, [
            'view'    => 'core::emails.demande-sous-reserve',
            'subject' => "Action requise : Votre dossier est sous réserve — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => $typeLabel,
                'motif'     => $motif,
            ],
        ]);

        if (!empty($demande->demandeur_whatsapp)) {
            $this->whatsApp->send(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateSousReserve($demande->reference, $typeLabel, $motif),
                "sous_reserve:{$demande->reference}"
            );
        }
    }

    /**
     * Document prêt à être retiré.
     * Notifie : étudiant (email + WhatsApp).
     */
    public function sendReady(object $demande): void
    {
        $typeLabel = WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;

        $this->sendMailToStudent($demande, [
            'view'    => 'core::emails.demande-ready',
            'subject' => "Votre document est prêt — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => $typeLabel,
            ],
        ]);

        if (!empty($demande->demandeur_whatsapp)) {
            $this->whatsApp->send(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templatePret($demande->reference, $typeLabel),
                "pret:{$demande->reference}"
            );
        }
    }

    /**
     * Document remis physiquement à l'étudiant.
     * Notifie : étudiant (email + WhatsApp).
     */
    public function sendDelivered(object $demande): void
    {
        $typeLabel = WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;

        $this->sendMailToStudent($demande, [
            'view'    => 'core::emails.demande-delivered',
            'subject' => "Votre document vous a été remis — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => $typeLabel,
            ],
        ]);

        if (!empty($demande->demandeur_whatsapp)) {
            $this->whatsApp->send(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateRemis($demande->reference, $typeLabel),
                "remis:{$demande->reference}"
            );
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    // NOTIFICATION COMPLÉMENT DE DOSSIER
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Dépôt d'un complément de dossier par l'étudiant.
     * Notifie : étudiant (email + WhatsApp) + secrétaire (email + WhatsApp).
     *
     * @param string      $etudiantEmail    Email saisi dans le formulaire
     * @param array       $vars             Variables du complément (nomComplet, matricule, reference, dateComplement, piecesList)
     * @param string|null $whatsappEtudiant Numéro WhatsApp de l'étudiant (normalisé)
     */
    public function sendComplementSecretaire(
        string  $etudiantEmail,
        array   $vars,
        ?string $whatsappEtudiant = null,
    ): void {
        $reference  = $vars['reference']  ?? '—';
        $nomComplet = $vars['nomComplet'] ?? 'L\'étudiant(e)';
        $piecesList = $vars['piecesList'] ?? [];

        // ── Email étudiant ────────────────────────────────────────────────────
        try {
            Mail::send(
                'core::emails.complement-confirmation',
                $vars,
                fn($m) => $m->to($etudiantEmail)
                            ->subject("Complément de dossier reçu — Réf : {$reference}")
            );
        } catch (\Exception $e) {
            Log::error('[Notification] Erreur email complément étudiant', [
                'error' => $e->getMessage(),
                'ref'   => $reference,
            ]);
        }

        // ── WhatsApp étudiant ─────────────────────────────────────────────────
        if (!empty($whatsappEtudiant)) {
            $this->whatsApp->send(
                $whatsappEtudiant,
                $this->whatsApp->templateComplementEtudiant($reference, $piecesList),
                "complement-etudiant:{$reference}"
            );
        }

        // ── Email secrétaire ──────────────────────────────────────────────────
        $secretaires = $this->findUsersWithRole('secretaire', null);

        foreach ($secretaires as $sec) {
            try {
                Mail::send(
                    'core::emails.complement-notification-secretariat',
                    array_merge($vars, ['email' => $etudiantEmail]),
                    fn($m) => $m->to($sec->email, $sec->name)
                               ->subject("Nouveau complément — Réf : {$reference}")
                );
            } catch (\Exception $e) {
                Log::error('[Notification] Erreur email complément secrétariat', [
                    'error' => $e->getMessage(),
                    'ref'   => $reference,
                ]);
            }

            // ── WhatsApp secrétaire ───────────────────────────────────────────
            if (!empty($sec->phone)) {
                $this->whatsApp->send(
                    $sec->phone,
                    $this->whatsApp->templateComplementSecretaire(
                        destinataireNom: $sec->name,
                        reference:       $reference,
                        nomEtudiant:     $nomComplet,
                        nbPieces:        count($piecesList),
                    ),
                    "complement-secretaire:{$reference}"
                );
            }
        }
    }

    /**
     * Notifie la secrétaire que le Directeur a signé et que le dossier
     * est en attente de finalisation (secretary_final_review).
     * Déclenche : email + WhatsApp à chaque secrétaire.
     */
    public function notifySecretaireAfterDirecteurSign(object $demande): void
    {
        $secretaires  = $this->findUsersWithRole('secretaire', null);
        $typeLabel    = WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);
        $matricule    = $etudiantInfo->matricule ?? '';

        foreach ($secretaires as $sec) {
            // ── Email ─────────────────────────────────────────────────────────
            try {
                Mail::send(
                    'core::emails.dossier-transmis',
                    [
                        'destinataireNom'   => $sec->name,
                        'destinataireRole'  => 'Secrétaire',
                        'expediteurNom'     => 'Directeur',
                        'expediteurRole'    => 'Directeur',
                        'reference'         => $demande->reference,
                        'typeDocument'      => $typeLabel,
                        'etudiantNom'       => $etudiantNom,
                        'etudiantMatricule' => $matricule,
                        'dateTransmission'  => now()->format('d/m/Y à H:i'),
                        'commentaire'       => "Le Directeur a signé ce dossier. Veuillez préparer le document et le marquer comme prêt à retirer.",
                        'urlEspace'         => config('app.url') . '/dashboard',
                        'etablissement'     => config('app.name', 'CAP-EPAC'),
                    ],
                    fn($m) => $m->to($sec->email, $sec->name)
                               ->subject("✅ Dossier signé par le Directeur — À finaliser — Réf : {$demande->reference}")
                );
            } catch (\Exception $e) {
                Log::error('[Notification] Erreur email secrétaire après signature directeur', [
                    'error' => $e->getMessage(),
                    'ref'   => $demande->reference,
                ]);
            }

            // ── WhatsApp ──────────────────────────────────────────────────────
            if (!empty($sec->phone)) {
                $this->whatsApp->send(
                    $sec->phone,
                    $this->whatsApp->templateDirecteurSigne(
                        destinataireNom: $sec->name,
                        nomEtudiant:     $etudiantNom,
                        reference:       $demande->reference,
                        typeDocument:    $typeLabel,
                        matricule:       $matricule,
                    ),
                    "directeur-signe-secretaire:{$demande->reference}"
                );
            }
        }
    }

    /**
     * Notifie la secrétaire qu'un dossier entre en Direction.
     */
    public function notifySecretaryOfDirectionTransmission(object $demande): void
    {
        $secretaires = $this->findUsersWithRole('secretaire', null);
        $typeLabel   = WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);
        $matricule    = $etudiantInfo->matricule ?? '';

        foreach ($secretaires as $sec) {
            // Email
            try {
                Mail::send(
                    'core::emails.dossier-transmis',
                    [
                        'destinataireNom'   => $sec->name,
                        'destinataireRole'  => 'Secrétaire',
                        'expediteurNom'     => 'Chef CAP',
                        'expediteurRole'    => 'Chef CAP',
                        'reference'         => $demande->reference,
                        'typeDocument'      => $typeLabel,
                        'etudiantNom'       => $etudiantNom,
                        'etudiantMatricule' => $matricule,
                        'dateTransmission'  => now()->format('d/m/Y à H:i'),
                        'commentaire'       => "Ce dossier est maintenant en cours de signature (Direction). Veuillez préparer et transmettre les documents physiques correspondants.",
                        'urlEspace'         => config('app.url') . '/dashboard',
                        'etablissement'     => config('app.name', 'CAP-EPAC'),
                    ],
                    fn($m) => $m->to($sec->email, $sec->name)
                               ->subject("Dossier à transmettre physiquement (Direction) — Réf : {$demande->reference}")
                );
            } catch (\Exception $e) {
                Log::error('[Notification] Erreur email secrétaire transmission direction', [
                    'error' => $e->getMessage(),
                    'ref'   => $demande->reference,
                ]);
            }

            // WhatsApp
            if (!empty($sec->phone)) {
                $this->whatsApp->send(
                    $sec->phone,
                    $this->whatsApp->templateDossierDirection(
                        destinataireNom: $sec->name,
                        nomEtudiant:     $etudiantNom,
                        reference:       $demande->reference,
                    ),
                    "direction-secretaire:{$demande->reference}"
                );
            }
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    // NOTIFICATION ACTEURS INTERNES
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Notifie le prochain acteur qu'un dossier lui a été transmis.
     * Email + WhatsApp pour chaque acteur concerné.
     * Fonctionne dans les deux sens (transmission normale ou retour correction).
     */
    public function notifyNextActor(
        object  $demande,
        string  $newStatus,
        object  $expediteurUser,
        ?string $expediteurRole,
        ?string $chefDivisionType = null,
        ?string $commentaire      = null,
    ): void {
        $targetRoleSlug = WorkflowConstants::STATUS_TO_ROLE[$newStatus] ?? null;
        if (!$targetRoleSlug) {
            return;
        }

        $destinataires = $this->findUsersWithRole($targetRoleSlug, $chefDivisionType);

        if ($destinataires->isEmpty()) {
            Log::warning('[Notification] Aucun utilisateur pour le rôle', [
                'role'   => $targetRoleSlug,
                'status' => $newStatus,
                'ref'    => $demande->reference,
            ]);
            return;
        }

        $etudiantInfo      = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom       = $this->buildNom($etudiantInfo);
        $matricule         = $etudiantInfo->matricule ?? '';
        $expediteurNomRole = WorkflowConstants::ROLE_LABELS[$expediteurRole] ?? $expediteurRole ?? 'Acteur';
        $typeDocument      = WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type;
        $destinataireRole  = WorkflowConstants::ROLE_LABELS[$targetRoleSlug] ?? $targetRoleSlug;
        $isCorrection      = $demande->is_in_correction_circuit ?? false;

        foreach ($destinataires as $dest) {

            // ── Email ─────────────────────────────────────────────────────────
            try {
                Mail::send(
                    'core::emails.dossier-transmis',
                    [
                        'destinataireNom'   => $dest->name,
                        'destinataireRole'  => $destinataireRole,
                        'expediteurNom'     => $expediteurUser->name ?? 'Acteur',
                        'expediteurRole'    => $expediteurNomRole,
                        'reference'         => $demande->reference,
                        'typeDocument'      => $typeDocument,
                        'etudiantNom'       => $etudiantNom,
                        'etudiantMatricule' => $matricule,
                        'dateTransmission'  => now()->format('d/m/Y à H:i'),
                        'commentaire'       => $commentaire,
                        'urlEspace'         => config('app.url') . '/dashboard',
                        'etablissement'     => config('app.name', 'CAP-EPAC'),
                    ],
                    fn($m) => $m->to($dest->email, $dest->name)
                               ->subject("Dossier à traiter — Réf : {$demande->reference}")
                );
            } catch (\Exception $e) {
                Log::error('[Notification] Erreur email acteur', [
                    'error' => $e->getMessage(),
                    'dest'  => $dest->email,
                    'ref'   => $demande->reference,
                ]);
            }

            // ── WhatsApp ──────────────────────────────────────────────────────
            if (!empty($dest->phone)) {
                if ($targetRoleSlug === 'secretaire' && $isCorrection) {
                    $message = $this->whatsApp->templateCorrectionCircuit(
                        destinataireNom: $dest->name,
                        expediteurNom:   $expediteurUser->name ?? 'Acteur',
                        expediteurRole:  $expediteurNomRole,
                        reference:       $demande->reference,
                        typeDocument:    $typeDocument,
                        etudiantNom:     $etudiantNom,
                        matricule:       $matricule,
                        commentaire:     $commentaire,
                    );
                } else {
                    $message = $this->whatsApp->templateActeurDossier(
                        destinataireNom:  $dest->name,
                        destinataireRole: $destinataireRole,
                        expediteurNom:    $expediteurUser->name ?? 'Acteur',
                        expediteurRole:   $expediteurNomRole,
                        reference:        $demande->reference,
                        typeDocument:     $typeDocument,
                        etudiantNom:      $etudiantNom,
                        matricule:        $matricule,
                        commentaire:      $commentaire,
                    );
                }

                $this->whatsApp->send(
                    $dest->phone,
                    $message,
                    "acteur:{$targetRoleSlug}:{$demande->reference}"
                );
            }
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ═════════════════════════════════════════════════════════════════════════

    private function sendMailToStudent(object $demande, array $mailData): void
    {
        if (empty($demande->email)) {
            return;
        }

        try {
            Mail::send(
                $mailData['view'],
                $mailData['vars'],
                fn($m) => $m->to($demande->email)->subject($mailData['subject'])
            );
        } catch (\Exception $e) {
            Log::error('[Notification] Erreur email étudiant', [
                'error' => $e->getMessage(),
                'ref'   => $demande->reference,
            ]);
        }
    }

    private function findUsersWithRole(string $roleSlug, ?string $chefDivisionType): \Illuminate\Support\Collection
    {
        $query = DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('r.slug', $roleSlug)
            ->whereNotNull('u.email')
            ->whereNull('u.deleted_at')
            ->select(
                'u.id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as name"),
                'u.email',
                'u.phone',
                'u.chef_division_type'
            );

        if ($roleSlug === 'chef-division' && $chefDivisionType) {
            $query->where('u.chef_division_type', $chefDivisionType);
        }

        return $query->get();
    }

    private function fetchEtudiantInfo(int $demandeId): ?object
    {
        return DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->where('dr.id', $demandeId)
            ->select(
                'pi.last_name',
                'pi.first_names',
                DB::raw("(SELECT s.student_id_number FROM students s
                          JOIN student_pending_student sps2 ON sps2.student_id = s.id
                          WHERE sps2.id = dr.student_pending_student_id LIMIT 1) as matricule")
            )
            ->first();
    }

    private function buildNom(?object $info): string
    {
        if (!$info) return 'Étudiant(e)';
        return trim(($info->first_names ?? '') . ' ' . ($info->last_name ?? '')) ?: 'Étudiant(e)';
    }
}
