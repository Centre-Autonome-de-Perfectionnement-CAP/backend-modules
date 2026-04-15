<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Centralise tous les envois de mail du module Demandes.
 *  - Mails vers l'étudiant (rejet, prêt, remis)
 *  - Notifications inter-acteurs (transmission dossier)
 */
class NotificationService
{
    // ── Mails étudiant ────────────────────────────────────────────────────────

    public function sendRejected(object $demande, string $motif): void
    {
        $this->sendToStudent($demande, [
            'view'    => 'core::emails.demande-rejected',
            'subject' => "Votre demande a été rejetée — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type,
                'motif'     => $motif,
            ],
        ]);
    }

    public function sendReady(object $demande): void
    {
        $this->sendToStudent($demande, [
            'view'    => 'core::emails.demande-ready',
            'subject' => "Votre document est prêt — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type,
            ],
        ]);
    }

    public function sendDelivered(object $demande): void
    {
        $this->sendToStudent($demande, [
            'view'    => 'core::emails.demande-delivered',
            'subject' => "Votre document vous a été remis — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type,
            ],
        ]);
    }

    // ── Notification inter-acteurs ────────────────────────────────────────────

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
            Log::warning('notifyNextActor: aucun utilisateur pour le rôle', [
                'role'   => $targetRoleSlug,
                'status' => $newStatus,
                'ref'    => $demande->reference,
            ]);
            return;
        }

        $etudiantInfo      = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom       = trim(($etudiantInfo->first_names ?? '') . ' ' . ($etudiantInfo->last_name ?? '')) ?: 'Étudiant(e)';
        $expediteurNomRole = (WorkflowConstants::ROLE_LABELS[$expediteurRole] ?? $expediteurRole ?? 'Acteur');

        foreach ($destinataires as $dest) {
            try {
                Mail::send(
                    'core::emails.dossier-transmis',
                    [
                        'destinataireNom'   => $dest->name,
                        'destinataireRole'  => WorkflowConstants::ROLE_LABELS[$targetRoleSlug] ?? $targetRoleSlug,
                        'expediteurNom'     => $expediteurUser->name ?? 'Acteur',
                        'expediteurRole'    => $expediteurNomRole,
                        'reference'         => $demande->reference,
                        'typeDocument'      => WorkflowConstants::TYPE_LABELS[$demande->type] ?? $demande->type,
                        'etudiantNom'       => $etudiantNom,
                        'etudiantMatricule' => $etudiantInfo->matricule ?? null,
                        'dateTransmission'  => now()->format('d/m/Y à H:i'),
                        'commentaire'       => $commentaire,
                        'urlEspace'         => config('app.url') . '/dashboard',
                        'etablissement'     => config('app.name', 'CAP-EPAC'),
                    ],
                    fn($m) => $m->to($dest->email, $dest->name)
                               ->subject("Dossier à traiter — Réf : {$demande->reference}")
                );

                Log::info('Notification inter-acteurs envoyée', [
                    'dest' => $dest->email,
                    'role' => $targetRoleSlug,
                    'ref'  => $demande->reference,
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur mail acteur interne', [
                    'error' => $e->getMessage(),
                    'dest'  => $dest->email,
                    'ref'   => $demande->reference,
                ]);
            }
        }
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    private function sendToStudent(object $demande, array $mailData): void
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
            Log::error('Erreur mail étudiant', [
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
            ->select(
                'u.id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as name"),
                'u.email',
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
                DB::raw("(SELECT student_id_number FROM students s
                          JOIN student_pending_student sps2 ON sps2.student_id = s.id
                          WHERE sps2.id = dr.student_pending_student_id LIMIT 1) as matricule")
            )
            ->first();
    }
}
