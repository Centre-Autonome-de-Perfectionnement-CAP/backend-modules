<?php

namespace App\Modules\Demandes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * DocumentRequestNotificationService
 *
 * Responsabilité unique : envoyer les emails du workflow.
 *   - notifyStudent()   → étudiant (rejet, prêt, remis)
 *   - notifyNextActor() → prochain maillon interne du workflow
 */
class DocumentRequestNotificationService
{
    private const ROLE_LABELS = [
        'chef-division'       => 'Chef Division',
        'comptable'           => 'Comptable',
        'chef-cap'            => 'Chef CAP',
        'sec-da'              => 'Secrétaire Directrice Adjointe',
        'directrice-adjointe' => 'Directrice Adjointe',
        'sec-dir'             => 'Secrétaire Directeur',
        'directeur'           => 'Directeur',
        'secretaire'          => 'Secrétaire',
    ];

    private const TYPE_LABELS = [
        'attestation_passage'     => 'Attestation de Passage',
        'attestation_definitive'  => 'Attestation Définitive',
        'attestation_inscription' => "Attestation d'Inscription",
        'bulletin_notes'          => 'Bulletin de Notes',
    ];

    /**
     * Mapping statut → rôle destinataire de la notification interne.
     * Les statuts terminaux (ready, delivered, rejected) n'ont pas de destinataire interne.
     */
    private const STATUS_TO_ROLE = [
        'comptable_review'           => 'comptable',
        'chef_division_review'       => 'chef-division',
        'chef_cap_review'            => 'chef-cap',
        'sec_dir_adjointe_review'    => 'sec-da',
        'directrice_adjointe_review' => 'directrice-adjointe',
        'sec_directeur_review'       => 'sec-dir',
        'directeur_review'           => 'directeur',
        'secretaire_correction'      => 'secretaire',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // MAILS ÉTUDIANT
    // ─────────────────────────────────────────────────────────────────────────

    public function sendRejectedMail(object $demande, string $motif): ?array
    {
        if (!$demande->email) return null;
        $mail = [
            'view'    => 'core::emails.demande-rejected',
            'subject' => "Votre demande a été rejetée — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => self::TYPE_LABELS[$demande->type] ?? $demande->type,
                'motif'     => $motif,
            ],
        ];
        $this->sendMail($demande->email, $mail);
        return $mail;
    }

    public function sendReadyMail(object $demande): ?array
    {
        if (!$demande->email) return null;
        $mail = [
            'view'    => 'core::emails.demande-ready',
            'subject' => "Votre document est prêt — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => self::TYPE_LABELS[$demande->type] ?? $demande->type,
            ],
        ];
        $this->sendMail($demande->email, $mail);
        return $mail;
    }

    public function sendDeliveredMail(object $demande): ?array
    {
        if (!$demande->email) return null;
        $mail = [
            'view'    => 'core::emails.demande-delivered',
            'subject' => "Votre document vous a été remis — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => self::TYPE_LABELS[$demande->type] ?? $demande->type,
            ],
        ];
        $this->sendMail($demande->email, $mail);
        return $mail;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NOTIFICATION INTER-ACTEURS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Notifie le prochain acteur du workflow après une transition.
     *
     * @param  object      $demande
     * @param  string      $newStatus         Nouveau statut après transition
     * @param  object      $expediteurUser    Utilisateur Auth connecté
     * @param  string|null $expediteurRole    Slug de son rôle
     * @param  string|null $chefDivisionType  Filtre pour chef-division si applicable
     * @param  string|null $commentaire       Motif / commentaire optionnel
     * @return string[]    Sujets des emails envoyés (pour l'historique)
     */
    public function notifyNextActor(
        object  $demande,
        string  $newStatus,
        object  $expediteurUser,
        ?string $expediteurRole,
        ?string $chefDivisionType = null,
        ?string $commentaire      = null,
    ): array {
        $targetRoleSlug = self::STATUS_TO_ROLE[$newStatus] ?? null;
        if (!$targetRoleSlug) {
            return [];
        }

        // Récupérer le(s) utilisateur(s) destinataires
        $query = DB::table('users as u')
            ->join('role_user as mhr', 'mhr.user_id', '=', 'u.id')
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
            ->where('r.slug', $targetRoleSlug)
            ->whereNotNull('u.email')
            ->select(
                'u.id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as name"),
                'u.email',
                'u.chef_division_type'
            );

        if ($targetRoleSlug === 'chef-division' && $chefDivisionType) {
            $query->where('u.chef_division_type', $chefDivisionType);
        }

        $destinataires = $query->get();

        if ($destinataires->isEmpty()) {
            Log::warning("notifyNextActor : aucun utilisateur pour le rôle [{$targetRoleSlug}]", [
                'ref' => $demande->reference,
            ]);
            return [];
        }

        // Infos étudiant pour le corps du mail
        $etudiantInfo = DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->where('dr.id', $demande->id)
            ->select(
                'pi.last_name', 'pi.first_names',
                DB::raw("(SELECT student_id_number FROM students s
                          JOIN student_pending_student sps2 ON sps2.student_id = s.id
                          WHERE sps2.id = dr.student_pending_student_id LIMIT 1) as matricule")
            )
            ->first();

        $etudiantNom       = trim(($etudiantInfo?->first_names ?? '') . ' ' . ($etudiantInfo?->last_name ?? '')) ?: 'Étudiant(e)';
        $etudiantMatricule = $etudiantInfo?->matricule ?? null;
        $expediteurNom     = $expediteurUser->name ?? trim(($expediteurUser->first_name ?? '') . ' ' . ($expediteurUser->last_name ?? ''));
        $expediteurLabel   = self::ROLE_LABELS[$expediteurRole] ?? ($expediteurRole ?? 'Acteur');

        $subject  = "📂 Dossier à traiter — Réf : {$demande->reference}";
        $subjects = [];

        foreach ($destinataires as $destinataire) {
            try {
                Mail::send(
                    'core::emails.dossier-transmis',
                    [
                        'destinataireNom'   => $destinataire->name,
                        'destinataireRole'  => self::ROLE_LABELS[$targetRoleSlug] ?? $targetRoleSlug,
                        'expediteurNom'     => $expediteurNom,
                        'expediteurRole'    => $expediteurLabel,
                        'reference'         => $demande->reference,
                        'typeDocument'      => self::TYPE_LABELS[$demande->type] ?? $demande->type,
                        'etudiantNom'       => $etudiantNom,
                        'etudiantMatricule' => $etudiantMatricule,
                        'dateTransmission'  => now()->format('d/m/Y à H:i'),
                        'commentaire'       => $commentaire,
                        'urlEspace'         => config('app.url') . '/dashboard',
                        'etablissement'     => config('app.name', 'CAP-EPAC'),
                    ],
                    fn($m) => $m->to($destinataire->email, $destinataire->name)->subject($subject)
                );
                $subjects[] = $subject . " → {$destinataire->email}";
                Log::info("Notification inter-acteurs envoyée", [
                    'destinataire' => $destinataire->email,
                    'role'         => $targetRoleSlug,
                    'ref'          => $demande->reference,
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi mail acteur interne', [
                    'error'        => $e->getMessage(),
                    'destinataire' => $destinataire->email,
                    'ref'          => $demande->reference,
                ]);
            }
        }

        return $subjects;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER INTERNE
    // ─────────────────────────────────────────────────────────────────────────

    private function sendMail(string $to, array $mail): void
    {
        try {
            Mail::send($mail['view'], $mail['vars'], fn($m) => $m->to($to)->subject($mail['subject']));
        } catch (\Exception $e) {
            Log::error('Erreur envoi mail étudiant', [
                'error' => $e->getMessage(),
                'to'    => $to,
            ]);
        }
    }
}
