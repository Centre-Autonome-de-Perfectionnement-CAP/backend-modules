<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\WorkflowConstants;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Modules\Demandes\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CORRECTIF (v2) — basé sur le TransitionService réel fourni.
 *
 * IMPORTANT : buildUpdate() est conservé À L'IDENTIQUE de l'original
 * (même ordre des if/elseif, mêmes valeurs de statut, mêmes champs
 * mis à jour) — aucune réécriture du contenu métier, uniquement
 * l'ajout demandé par le Sprint B2 :
 *
 *   B2.5 — Transaction courte : apply() enveloppe désormais lecture +
 *          update + historique dans une transaction DB::transaction(),
 *          alors que l'original ne transactionnait rien du tout (3 requêtes
 *          séquentielles non atomiques : SELECT, UPDATE, lecture historique).
 *          Les notifications (email/WhatsApp) restent HORS transaction,
 *          exactement comme dans l'original (déjà correct : elles sont
 *          après le `return $fresh` implicite de la logique de notif).
 *
 *   B2.6 — lockForUpdate() ajouté sur le SELECT initial de la ligne
 *          document_requests, pour empêcher deux transitions concurrentes
 *          sur le même dossier (ex: deux clics rapides, ou deux acteurs
 *          qui valident en même temps un dossier déjà transmis). L'original
 *          ne verrouillait pas la ligne — risque réel de double-transition
 *          ou d'état incohérent en cas de requêtes simultanées.
 *
 * Aucun changement sur : assertActionAllowed(), buildUpdate(),
 * resolveResponsableDivisionType(), requireMotif(), requireComment().
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
        $role = WorkflowConstants::canonicalRole($role);
        $user = Auth::user();

        // ── B2.5 + B2.6 : transaction courte avec verrou de ligne ─────────────
        // Lecture, vérification, update et écriture d'historique sont désormais
        // atomiques. lockForUpdate() empêche une seconde transition concurrente
        // de lire un état périmé pendant que la première est en cours d'écriture.
        [$update, $newStatus, $mailTrigger, $demande] = DB::transaction(function () use ($id, $action, $payload, $role, $user) {

            $demande = DB::table('document_requests')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$demande) {
                abort(404, 'Demande introuvable.');
            }

            $this->assertActionAllowed($role, $action, $demande->status);

            [$update, $newStatus, $mail] = $this->buildUpdate($action, $payload, $demande, $user, $role);

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

            return [$update, $newStatus, $mail, $demande];
        });

        // ── Mails (hors transaction, comme dans l'original) ───────────────────
        // JOIN academic_years : document_requests n'a qu'un academic_year_id (FK),
        // pas de colonne academic_year directe. Sans cette jointure,
        // $fresh->academic_year est toujours null (ex: WorkflowConstants::typeLabel()
        // qui en a besoin pour afficher "Bulletin Annuel 2024-2025").
        $fresh = DB::table('document_requests as dr')
            ->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id')
            ->where('dr.id', $id)
            ->select(['dr.*', 'ay.academic_year'])
            ->first();

        match ($mailTrigger) {
            'rejected'                          => $this->notificationService->sendRejected($fresh, $payload['motif'] ?? ''),
            'ready_for_pickup'                  => $this->notificationService->sendReady($fresh),
            'picked_up'                         => $this->notificationService->sendDelivered($fresh),
            'direction_transmission'            => $this->notificationService->notifySecretaryOfDirectionTransmission($fresh),
            'directeur_signed_notify_secretaire'=> $this->notificationService->notifySecretaireAfterDirecteurSign($fresh),
            default                             => null,
        };

        if ($newStatus && $mailTrigger !== 'directeur_signed_notify_secretaire') {
            // CORRECTIF : sur directeur_sign / directeur_sign_flagged, la secrétaire
            // recevait DEUX messages redondants — "Signature validée" (ci-dessus,
            // via notifySecretaireAfterDirecteurSign) ET le message générique
            // "Dossier à traiter" de notifyNextActor (elle est le prochain acteur
            // après la signature du directeur). Le premier message contient déjà
            // l'appel à l'action ("Merci de préparer... et de le marquer prêt"),
            // donc on n'envoie plus le second dans ce cas précis.
            $this->notificationService->notifyNextActor(
                demande:                 $fresh,
                newStatus:               $newStatus,
                expediteurUser:          $user,
                expediteurRole:          $role,
                responsableDivisionType: $update['responsable_division_type'] ?? ($demande->responsable_division_type ?? null),
                commentaire:             $payload['motif'] ?? $payload['comment'] ?? null,
            );
        }

        return $fresh;
    }

    // ── Constructeur d'update (INCHANGÉ — copie fidèle de l'original) ─────────

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

        $roleToStatus = [
            'comptable'           => 'accounting_review',
            'responsable-division'=> 'division_manager_review',
            'chef-cap'            => 'cap_manager_review',
            'sec-da'              => 'deputy_director_secretary_review',
            'directrice-adjointe' => 'deputy_director_review',
            'sec-dir'             => 'director_secretary_review',
            'directeur'           => 'director_review',
        ];

        // ── CLEAR FLAG ────────────────────────────────────────────────────────
        if ($action === 'clear_flag') {
            $update['has_flag'] = false;
            return [$update, null, null, false];
        }

        // ── SECRÉTAIRE ────────────────────────────────────────────────────────
        if ($action === 'secretaire_validate') {
            $newStatus        = 'accounting_review';
            $update['status'] = $newStatus;
        }

        elseif (in_array($action, ['secretaire_reject', 'secretaire_reject_final'])) {
            $this->requireMotif($p);
            $update['status']                   = 'rejected';
            $update['rejected_reason']          = $p['motif'];
            $update['rejected_by']              = 'Secrétaire';
            // Un dossier rejeté définitivement sort du circuit de correction :
            // sinon il reste marqué is_in_correction_circuit = true (navette active)
            // en plus d'apparaître dans "rejeté" — double affichage incohérent.
            $update['is_in_correction_circuit'] = false;
            $update['correction_origin_role']   = null;
            $update['correction_origin_status'] = null;
            $mail = 'rejected';
        }

        elseif ($action === 'secretaire_resend') {
            $resendTo = WorkflowConstants::canonicalRole($p['resend_to'] ?? '') ?? '';

            if ($resendTo === 'origin') {
                $originRole   = WorkflowConstants::canonicalRole($demande->correction_origin_role);
                $originStatus = $demande->correction_origin_status
                    ?? ($roleToStatus[$originRole] ?? null);

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
                $newStatus = $roleToStatus[$resendTo] ?? null;
                if (!$newStatus) {
                    abort(422, 'Destination de renvoi invalide.');
                }
                $update['status']                   = $newStatus;
                $update['is_in_correction_circuit'] = true;

                if ($resendTo === 'responsable-division') {
                    $update['responsable_division_type'] = $this->resolveResponsableDivisionType($demande->id);
                }
            }
        }

        elseif ($action === 'secretaire_deliver') {
            $update['status']       = 'picked_up';
            $update['delivered_at'] = now();
            $mail = 'picked_up';
        }

        elseif ($action === 'secretaire_mark_ready') {
            $update['status'] = 'ready_for_pickup';
            $mail = 'ready_for_pickup';
        }

        // ── COMPTABLE ─────────────────────────────────────────────────────────
        elseif (in_array($action, ['comptable_validate', 'comptable_validate_flagged'])) {
            $newStatus                           = 'division_manager_review';
            $update['status']                    = $newStatus;
            $update['responsable_division_type'] = $this->resolveResponsableDivisionType($demande->id);

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
        // RESPONSABLE DIVISION
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['responsable_division_validate', 'responsable_division_validate_flagged'])) {
            $newStatus        = 'cap_manager_review';
            $update['status'] = $newStatus;

            if ($isFlagged) {
                $update['has_flag'] = true;
            }
        }

        elseif ($action === 'responsable_division_reject') {
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
        // ═════════════════════════════════════════════════════════════════════

        elseif (in_array($action, ['chef_cap_validate', 'chef_cap_validate_flagged',
                                    'chef_cap_sign',    'chef_cap_sign_flagged'])) {
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
        }

        // ── SEC. DIRECTRICE ADJOINTE ──────────────────────────────────────────
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

        // ── DIRECTRICE ADJOINTE ───────────────────────────────────────────────
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

        // ── SEC. DIRECTEUR ────────────────────────────────────────────────────
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

        // ── DIRECTEUR ─────────────────────────────────────────────────────────
        elseif (in_array($action, ['directeur_sign', 'directeur_sign_flagged'])) {
            $update['signature_type'] = $p['signature_type'] ?? 'signature';
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
        // RETOUR À LA SECRÉTAIRE
        // ═════════════════════════════════════════════════════════════════════

        elseif ($action === 'return_to_secretaire') {
            $this->requireComment($p);
            $newStatus        = 'secretary_correction';
            $update['status'] = $newStatus;
        }

        else {
            abort(422, "Action inconnue : {$action}");
        }

        return [$update, $newStatus, $mail, $isFlagged];
    }

    // ── Auto-détection du Responsable Division (INCHANGÉ) ─────────────────────

    private function resolveResponsableDivisionType(int $demandeId): string
    {
        $cycleName = DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('departments as dept', 'ps.department_id', '=', 'dept.id')
            ->join('cycles as c', 'dept.cycle_id', '=', 'c.id')
            ->where('dr.id', $demandeId)
            ->value('c.name');

        return ($cycleName === 'Licence Professionnelle') ? 'formation_distance' : 'formation_continue';
    }

    // ── Assertions (INCHANGÉ) ──────────────────────────────────────────────────

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

    private function requireComment(array $p): void
    {
        if (empty($p['comment']) && empty($p['motif'])) {
            abort(422, 'Un commentaire est obligatoire pour un retour à la secrétaire.');
        }
    }
}
