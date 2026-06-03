<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\WorkflowConstants;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Modules\Demandes\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Machine à états du workflow Demandes.
 *
 * ─── CIRCUIT DE CORRECTION ────────────────────────────────────────────────────
 *
 * Déclencheur : n'importe quel acteur rejette → status = 'secretaire_correction'
 *   + is_in_correction_circuit = true
 *   + correction_origin_role   = slug du rôle (ex: 'comptable')
 *   + correction_origin_status = statut avant rejet (ex: 'comptable_review')
 *
 * Boucle :
 *   Secrétaire → renvoie à un acteur (secretaire_resend, resend_to ≠ 'origin')
 *   L'acteur ne peut faire que return_to_secretaire → revient à secretaire_correction
 *
 * Sortie :
 *   Secrétaire → secretaire_resend, resend_to = 'origin'
 *   → is_in_correction_circuit = false
 *   → status = correction_origin_status (retour exact au bon niveau)
 *
 * ─── COLONNES SUPPRIMÉES ──────────────────────────────────────────────────────
 *
 * Les writes suivants ont été retirés car redondants avec document_request_histories :
 *   - *_comment       → disponible via histories.comment + actor_role
 *   - *_reviewed_at   → disponible via histories.created_at + actor_role
 *   - processed_by_*  → disponible via histories.actor_id + actor_role
 */
class TransitionService
{
    public function __construct(
        protected DocumentRequestHistoryService $historyService,
        protected NotificationService           $notificationService,
    ) {}

    // ── Point d'entrée ────────────────────────────────────────────────────────

    public function apply(int $id, string $action, array $payload, string $role): object
    {
        $demande = DB::table('document_requests')->where('id', $id)->first();
        if (!$demande) {
            abort(404, 'Demande introuvable.');
        }

        $this->assertActionAllowed($role, $action, $demande);

        $user = Auth::user();
        [$update, $newStatus, $mailTrigger] = $this->buildUpdate($action, $payload, $demande, $user, $role);

        $update['updated_at'] = now();

        DB::table('document_requests')->where('id', $id)->update($update);

        // Historique
        if ($action === 'clear_flag') {
            $this->historyService->recordFlagCleared($id, $demande->status);
        } else {
            $this->historyService->record(
                documentRequestId: $id,
                action:            $action,
                statusBefore:      $demande->status,
                statusAfter:       $update['status'] ?? $demande->status,
                comment:           $payload['motif'] ?? $payload['comment'] ?? null,
            );
        }

        // Mails
        $fresh = DB::table('document_requests')->where('id', $id)->first();

        if (str_ends_with($action, '_flagged')) {
            $this->notificationService->sendSousReserve($fresh, $payload['motif'] ?? '');
        }

        match ($mailTrigger) {
            'rejected'                          => $this->notificationService->sendRejected($fresh, $payload['motif'] ?? ''),
            'ready_for_pickup'                  => $this->notificationService->sendReady($fresh),
            'picked_up'                         => $this->notificationService->sendDelivered($fresh),
            'direction_transmission'            => $this->notificationService->notifySecretaryOfDirectionTransmission($fresh),
            'directeur_signed_notify_secretaire'=> $this->notificationService->notifySecretaireAfterDirecteurSign($fresh),
            default                             => null,
        };

        if ($newStatus) {
            $this->notificationService->notifyNextActor(
                demande:          $fresh,
                newStatus:        $newStatus,
                expediteurUser:   $user,
                expediteurRole:   $role,
                chefDivisionType: $update['chef_division_type'] ?? ($demande->chef_division_type ?? null),
                commentaire:      $payload['motif'] ?? $payload['comment'] ?? null,
            );
        }

        return $fresh;
    }

    // ── Constructeur d'update ─────────────────────────────────────────────────

    /**
     * @return array{0: array, 1: string|null, 2: string|null}
     *         [champs à mettre à jour, nouveau statut|null, déclencheur mail|null]
     */
    private function buildUpdate(string $action, array $p, object $demande, object $user, string $role): array
    {
        $isFlagged = str_ends_with($action, '_flagged');
        $update    = [];
        $newStatus = null;
        $mail      = null;

        $roleToStatus = [
            'comptable'           => 'accounting_review',
            'chef-division'       => 'division_manager_review',
            'chef-cap'            => 'cap_manager_review',
            'sec-da'              => 'deputy_director_secretary_review',
            'directrice-adjointe' => 'deputy_director_review',
            'sec-dir'             => 'director_secretary_review',
            'directeur'           => 'director_review',
        ];

        // ── CLEAR FLAG ────────────────────────────────────────────────────────
        if ($action === 'clear_flag') {
            $update['has_flag'] = false;
            return [$update, null, null];
        }

        // ═════════════════════════════════════════════════════════════════════
        // SECRÉTAIRE
        // ═════════════════════════════════════════════════════════════════════

        if ($action === 'secretaire_validate') {
            $newStatus        = 'accounting_review';
            $update['status'] = $newStatus;
        }

        elseif (in_array($action, ['secretaire_reject', 'secretaire_reject_final'])) {
            $this->requireMotif($p);
            $update['status']          = 'rejected';
            $update['rejected_reason'] = $p['motif'];
            $update['rejected_by']     = 'Secrétaire';
            $mail = 'rejected';
        }

        elseif ($action === 'secretaire_resend') {
            $resendTo = $p['resend_to'] ?? '';

            if ($resendTo === 'origin') {
                // ── SORTIE DU CIRCUIT ─────────────────────────────────────────
                $originStatus = $demande->correction_origin_status
                    ?? ($roleToStatus[$demande->correction_origin_role] ?? null);

                if (!$originStatus) {
                    abort(422, 'Impossible de déterminer le statut de retour.');
                }

                $newStatus                          = $originStatus;
                $update['status']                   = $newStatus;
                $update['is_in_correction_circuit'] = false;
                $update['correction_origin_role']   = null;
                $update['correction_origin_status'] = null;
                $update['rejected_by']              = null;
                $update['rejected_reason']          = null;

            } else {
                // ── RENVOI EN BOUCLE ──────────────────────────────────────────
                $newStatus = $roleToStatus[$resendTo] ?? null;
                if (!$newStatus) {
                    abort(422, 'Destination de renvoi invalide.');
                }
                $update['status']                   = $newStatus;
                $update['is_in_correction_circuit'] = true;

                if ($resendTo === 'chef-division' && !empty($p['chef_division_type'])) {
                    $update['chef_division_type'] = $p['chef_division_type'];
                }
            }
        }

        elseif ($action === 'secretaire_deliver') {
            $update['status']       = 'picked_up';
            $update['delivered_at'] = now();
            $mail = 'picked_up';
        }

        // ── FINALISATION SECRÉTAIRE (après signature Directeur) ───────────────
        elseif ($action === 'secretaire_mark_ready') {
            $update['status'] = 'ready_for_pickup';
            $mail = 'ready_for_pickup';
        }

        // ═════════════════════════════════════════════════════════════════════
        // COMPTABLE
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['comptable_validate', 'comptable_validate_flagged'])) {
            if (empty($p['chef_division_type'])) {
                abort(422, 'Vous devez sélectionner le Responsable Division.');
            }
            $newStatus                    = 'division_manager_review';
            $update['status']             = $newStatus;
            $update['chef_division_type'] = $p['chef_division_type'];

            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'comptable_reject') {
            $this->requireMotif($p);
            $newStatus                          = 'secretary_correction';
            $update['status']                   = $newStatus;
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['correction_origin_role']   = $role;
            $update['correction_origin_status'] = $demande->status;
            $update['is_in_correction_circuit'] = true;
        }

        // ═════════════════════════════════════════════════════════════════════
        // CHEF DIVISION
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['chef_division_validate', 'chef_division_validate_flagged'])) {
            $newStatus        = 'cap_manager_review';
            $update['status'] = $newStatus;

            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'chef_division_reject') {
            $this->requireMotif($p);
            $newStatus                          = 'secretary_correction';
            $update['status']                   = $newStatus;
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['correction_origin_role']   = $role;
            $update['correction_origin_status'] = $demande->status;
            $update['is_in_correction_circuit'] = true;
        }

        // ═════════════════════════════════════════════════════════════════════
        // CHEF CAP
        // Le Chef CAP ne paraphe ni ne signe plus directement.
        // Il valide simplement → transmission vers Sec. Dir. Adjointe (circuit Direction).
        // Les anciens slugs chef_cap_sign / chef_cap_sign_flagged sont conservés
        // pour compatibilité avec l'historique existant mais redirigent vers Direction.
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['chef_cap_validate', 'chef_cap_validate_flagged',
                                    'chef_cap_sign',    'chef_cap_sign_flagged'])) {
            // Toujours vers le circuit Direction (Sec. Dir. Adjointe)
            $newStatus        = 'deputy_director_secretary_review';
            $update['status'] = $newStatus;
            $mail             = 'direction_transmission';

            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'chef_cap_reject') {
            $this->requireMotif($p);
            $newStatus                          = 'secretary_correction';
            $update['status']                   = $newStatus;
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['correction_origin_role']   = $role;
            $update['correction_origin_status'] = $demande->status;
            $update['is_in_correction_circuit'] = true;
        }

        // ═════════════════════════════════════════════════════════════════════
        // SEC. DIRECTRICE ADJOINTE
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['sec_da_transmit', 'sec_da_transmit_flagged'])) {
            $newStatus        = 'deputy_director_review';
            $update['status'] = $newStatus;

            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'sec_da_reject') {
            $this->requireMotif($p);
            $newStatus                          = 'secretary_correction';
            $update['status']                   = $newStatus;
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['correction_origin_role']   = $role;
            $update['correction_origin_status'] = $demande->status;
            $update['is_in_correction_circuit'] = true;
        }

        // ═════════════════════════════════════════════════════════════════════
        // DIRECTRICE ADJOINTE
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['directrice_adjointe_sign', 'directrice_adjointe_sign_flagged'])) {
            $newStatus        = 'director_secretary_review';
            $update['status'] = $newStatus;

            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'directrice_adjointe_reject') {
            $this->requireMotif($p);
            $newStatus                          = 'secretary_correction';
            $update['status']                   = $newStatus;
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['correction_origin_role']   = $role;
            $update['correction_origin_status'] = $demande->status;
            $update['is_in_correction_circuit'] = true;
        }

        // ═════════════════════════════════════════════════════════════════════
        // SEC. DIRECTEUR
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['sec_directeur_transmit', 'sec_directeur_transmit_flagged'])) {
            $newStatus        = 'director_review';
            $update['status'] = $newStatus;

            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'sec_directeur_reject') {
            $this->requireMotif($p);
            $newStatus                          = 'secretary_correction';
            $update['status']                   = $newStatus;
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['correction_origin_role']   = $role;
            $update['correction_origin_status'] = $demande->status;
            $update['is_in_correction_circuit'] = true;
        }

        // ═════════════════════════════════════════════════════════════════════
        // DIRECTEUR
        // Après signature, le dossier revient à la secrétaire pour finalisation
        // (secretary_final_review). La secrétaire déclenche ensuite
        // secretaire_mark_ready → ready_for_pickup.
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['directeur_sign', 'directeur_sign_flagged'])) {
            $update['signature_type'] = $p['signature_type'] ?? 'signature';
            // NOUVEAU : retour secrétaire pour finalisation (plus de ready_for_pickup direct)
            $newStatus        = 'secretary_final_review';
            $update['status'] = $newStatus;
            $mail             = 'directeur_signed_notify_secretaire';

            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'directeur_reject') {
            $this->requireMotif($p);
            $newStatus                          = 'secretary_correction';
            $update['status']                   = $newStatus;
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['correction_origin_role']   = $role;
            $update['correction_origin_status'] = $demande->status;
            $update['is_in_correction_circuit'] = true;
        }

        // ═════════════════════════════════════════════════════════════════════
        // RETOUR À LA SECRÉTAIRE (acteur en circuit de correction)
        // ═════════════════════════════════════════════════════════════════════

        elseif ($action === 'return_to_secretaire') {
            $this->requireComment($p);
            $newStatus        = 'secretary_correction';
            $update['status'] = $newStatus;
        }

        else {
            abort(422, "Action inconnue : {$action}");
        }

        return [$update, $newStatus, $mail];
    }

    // ── Assertions ────────────────────────────────────────────────────────────

    private function assertActionAllowed(?string $role, string $action, object $demande): void
    {
        $currentStatus = $demande->status;

        if ($role === 'admin') {
            return;
        }

        if ($action === 'clear_flag') {
            if ($role !== 'secretaire') {
                abort(403, 'Seul la secrétaire peut lever une réserve.');
            }
            return;
        }

        if (
            $demande->is_in_correction_circuit
            && $role !== 'secretaire'
            && $action !== 'return_to_secretaire'
        ) {
            abort(403, "Ce dossier est en circuit de correction. Seule l'action « Renvoyer à la Secrétaire » est autorisée.");
        }

        if ($action === 'return_to_secretaire' && $demande->is_in_correction_circuit) {
            return;
        }

        $matrix          = WorkflowConstants::ACTION_MATRIX[$role] ?? [];
        $allowedStatuses = $matrix[$action] ?? null;

        if ($allowedStatuses === null || !in_array($currentStatus, $allowedStatuses)) {
            abort(403, "Action « {$action} » non autorisée pour le rôle « {$role} » depuis « {$currentStatus} ».");
        }
    }

    private function requireMotif(array $p): void
    {
        if (empty($p['motif'])) {
            abort(422, 'Un motif est obligatoire pour cette action.');
        }
    }

    private function requireComment(array $p): void
    {
        $comment = trim($p['comment'] ?? $p['motif'] ?? '');
        if (strlen($comment) < 5) {
            abort(422, 'Un commentaire (minimum 5 caractères) est obligatoire avant de renvoyer à la Secrétaire.');
        }
    }
}
