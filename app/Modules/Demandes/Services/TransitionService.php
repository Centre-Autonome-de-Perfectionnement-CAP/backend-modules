<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\WorkflowConstants;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Modules\Demandes\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Machine à états du workflow Demandes.
 * Reçoit une action + payload, applique la transition, déclenche les effets de bord.
 * Ne fait jamais de lecture listing — seulement fetch par ID et update.
 */
class TransitionService
{
    public function __construct(
        protected DocumentRequestHistoryService $historyService,
        protected NotificationService           $notificationService,
    ) {}

    // ── Point d'entrée ────────────────────────────────────────────────────────

    /**
     * Applique la transition demandée et retourne la demande mise à jour.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function apply(int $id, string $action, array $payload, string $role): object
    {
        $demande = DB::table('document_requests')->where('id', $id)->first();
        if (!$demande) {
            abort(404, 'Demande introuvable.');
        }

        $this->assertActionAllowed($role, $action, $demande->status);

        $user        = Auth::user();
        [$update, $newStatus, $mailTrigger, $isFlagged] = $this->buildUpdate($action, $payload, $demande, $user, $role);

        $update['updated_at'] = now();

        DB::table('document_requests')->where('id', $id)->update($update);

        // ── Historique ────────────────────────────────────────────────────────
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

        // ── Mails ─────────────────────────────────────────────────────────────
        $fresh = DB::table('document_requests')->where('id', $id)->first();

        match ($mailTrigger) {
            'rejected'  => $this->notificationService->sendRejected($fresh, $payload['motif'] ?? ''),
            'ready'     => $this->notificationService->sendReady($fresh),
            'delivered' => $this->notificationService->sendDelivered($fresh),
            default     => null,
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
     * @return array{0: array, 1: string|null, 2: string|null, 3: bool}
     *         [champs à mettre à jour, nouveau statut|null, déclencheur mail|null, est flagged]
     */
    private function buildUpdate(string $action, array $p, object $demande, object $user, string $role): array
    {
        $isFlagged = str_ends_with($action, '_flagged');
        $update    = [];
        $newStatus = null;
        $mail      = null;

        // ── CLEAR FLAG ────────────────────────────────────────────────────────
        if ($action === 'clear_flag') {
            $update['has_flag'] = false;
            return [$update, null, null, false];
        }

        // ── SECRÉTAIRE ────────────────────────────────────────────────────────
        if ($action === 'secretaire_validate') {
            $newStatus             = 'comptable_review';
            $update['status']      = $newStatus;
            $update['processed_by_secretaire_id'] = $user->id;
        }

        elseif (in_array($action, ['secretaire_reject', 'secretaire_reject_final'])) {
            $this->requireMotif($p);
            $update['status']             = 'rejected';
            $update['rejected_reason']    = $p['motif'];
            $update['secretaire_comment'] = $p['motif'];
            $update['rejected_by']        = 'Secrétaire';
            $mail = 'rejected';
        }

        elseif ($action === 'secretaire_resend') {
            $map = [
                'comptable'           => 'comptable_review',
                'chef_division'       => 'chef_division_review',
                'chef_cap'            => 'chef_cap_review',
                'sec_da'              => 'sec_dir_adjointe_review',
                'directrice_adjointe' => 'directrice_adjointe_review',
                'sec_directeur'       => 'sec_directeur_review',
                'directeur'           => 'directeur_review',
            ];
            $newStatus = $map[$p['resend_to'] ?? ''] ?? null;
            if (!$newStatus) {
                abort(422, 'Destination de renvoi invalide.');
            }
            $update['status']          = $newStatus;
            $update['rejected_by']     = null;
            $update['rejected_reason'] = null;

            if (($p['resend_to'] ?? '') === 'chef_division' && !empty($p['chef_division_type'])) {
                $update['chef_division_type'] = $p['chef_division_type'];
            }
        }

        elseif ($action === 'secretaire_deliver') {
            $update['status']       = 'delivered';
            $update['delivered_at'] = now();
            $mail = 'delivered';
        }

        // ── COMPTABLE ─────────────────────────────────────────────────────────
        elseif (in_array($action, ['comptable_validate', 'comptable_validate_flagged'])) {
            if (empty($p['chef_division_type'])) {
                abort(422, 'Vous devez sélectionner le Responsable Division.');
            }
            $newStatus                           = 'chef_division_review';
            $update['status']                    = $newStatus;
            $update['chef_division_type']        = $p['chef_division_type'];
            $update['comptable_reviewed_at']     = now();
            $update['processed_by_comptable_id'] = $user->id;
            if ($isFlagged) {
                $update['has_flag']          = true;
                $update['comptable_comment'] = $p['motif'] ?? $p['comment'] ?? null;
            }
        }

        elseif ($action === 'comptable_reject') {
            $this->requireMotif($p);
            $newStatus                           = 'secretaire_correction';
            $update['status']                    = $newStatus;
            $update['comptable_comment']         = $p['motif'];
            $update['rejected_reason']           = $p['motif'];
            $update['rejected_by']               = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['comptable_reviewed_at']     = now();
            $update['processed_by_comptable_id'] = $user->id;
        }

        // ── CHEF DIVISION ─────────────────────────────────────────────────────
        elseif (in_array($action, ['chef_division_validate', 'chef_division_validate_flagged'])) {
            $newStatus                               = 'chef_cap_review';
            $update['status']                        = $newStatus;
            $update['chef_division_reviewed_at']     = now();
            $update['processed_by_chef_division_id'] = $user->id;
            if ($isFlagged) {
                $update['has_flag']               = true;
                $update['chef_division_comment']  = $p['motif'] ?? $p['comment'] ?? null;
            }
        }

        elseif ($action === 'chef_division_reject') {
            $this->requireMotif($p);
            $newStatus                               = 'secretaire_correction';
            $update['status']                        = $newStatus;
            $update['chef_division_comment']         = $p['motif'];
            $update['rejected_reason']               = $p['motif'];
            $update['rejected_by']                   = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['chef_division_reviewed_at']     = now();
            $update['processed_by_chef_division_id'] = $user->id;
        }

        // ── CHEF CAP ──────────────────────────────────────────────────────────
        elseif (in_array($action, ['chef_cap_sign', 'chef_cap_sign_flagged'])) {
            $sigType                            = $p['signature_type'] ?? 'signature';
            $update['signature_type']           = $sigType;
            $update['chef_cap_reviewed_at']     = now();
            $update['processed_by_chef_cap_id'] = $user->id;
            if ($isFlagged) {
                $update['has_flag'] = true;
            }
            if ($sigType === 'paraphe') {
                $newStatus        = 'sec_dir_adjointe_review';
                $update['status'] = $newStatus;
            } else {
                $update['status'] = 'ready';
                $mail = 'ready';
            }
        }

        elseif ($action === 'chef_cap_reject') {
            $this->requireMotif($p);
            $newStatus                          = 'secretaire_correction';
            $update['status']                   = $newStatus;
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['chef_cap_reviewed_at']     = now();
            $update['processed_by_chef_cap_id'] = $user->id;
        }

        // ── SEC. DIRECTRICE ADJOINTE ──────────────────────────────────────────
        elseif (in_array($action, ['sec_da_transmit', 'sec_da_transmit_flagged'])) {
            $newStatus                    = 'directrice_adjointe_review';
            $update['status']             = $newStatus;
            $update['sec_da_reviewed_at'] = now();
            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'sec_da_reject') {
            $this->requireMotif($p);
            $newStatus                    = 'secretaire_correction';
            $update['status']             = $newStatus;
            $update['rejected_reason']    = $p['motif'];
            $update['rejected_by']        = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['sec_da_reviewed_at'] = now();
        }

        // ── DIRECTRICE ADJOINTE ───────────────────────────────────────────────
        elseif (in_array($action, ['directrice_adjointe_sign', 'directrice_adjointe_sign_flagged'])) {
            $newStatus                                 = 'sec_directeur_review';
            $update['status']                          = $newStatus;
            $update['directrice_adjointe_reviewed_at'] = now();
            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'directrice_adjointe_reject') {
            $this->requireMotif($p);
            $newStatus                                 = 'secretaire_correction';
            $update['status']                          = $newStatus;
            $update['rejected_reason']                 = $p['motif'];
            $update['rejected_by']                     = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['directrice_adjointe_reviewed_at'] = now();
        }

        // ── SEC. DIRECTEUR ────────────────────────────────────────────────────
        elseif (in_array($action, ['sec_directeur_transmit', 'sec_directeur_transmit_flagged'])) {
            $newStatus                           = 'directeur_review';
            $update['status']                    = $newStatus;
            $update['sec_directeur_reviewed_at'] = now();
            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'sec_directeur_reject') {
            $this->requireMotif($p);
            $newStatus                           = 'secretaire_correction';
            $update['status']                    = $newStatus;
            $update['rejected_reason']           = $p['motif'];
            $update['rejected_by']               = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
            $update['sec_directeur_reviewed_at'] = now();
        }

        // ── DIRECTEUR ─────────────────────────────────────────────────────────
        elseif (in_array($action, ['directeur_sign', 'directeur_sign_flagged'])) {
            $update['signature_type']        = $p['signature_type'] ?? 'signature';
            $update['directeur_reviewed_at'] = now();
            $update['status']                = 'ready';
            $mail = 'ready';
            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'directeur_reject') {
            $this->requireMotif($p);
            $newStatus                 = 'secretaire_correction';
            $update['status']          = $newStatus;
            $update['rejected_reason'] = $p['motif'];
            $update['rejected_by']     = WorkflowConstants::ROLE_LABELS[$role] ?? $role;
        }

        else {
            abort(422, "Action inconnue : {$action}");
        }

        return [$update, $newStatus, $mail, $isFlagged];
    }

    // ── Assertions ────────────────────────────────────────────────────────────

    private function assertActionAllowed(?string $role, string $action, string $currentStatus): void
    {
        if ($role === 'admin') {
            return;
        }

        // clear_flag : secrétaire uniquement, n'importe quel statut
        if ($action === 'clear_flag') {
            if ($role !== 'secretaire') {
                abort(403, 'Seul la secrétaire peut lever une réserve.');
            }
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
}
