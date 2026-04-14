<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Models\DocumentRequestHistory as H;
use App\Modules\Demandes\Services\DocumentRequestNotificationService;
use App\Modules\Demandes\Traits\RecordsDocumentHistory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DocumentRequestTransitionController
 *
 * Responsabilité : exécuter les transitions de statut du workflow.
 *
 * À chaque action :
 *   1. Validation de la requête + vérification de la matrice d'autorisation
 *   2. Calcul de $update (champs à écrire dans document_requests)
 *   3. Persistance en base
 *   4. Enregistrement dans l'historique (RecordsDocumentHistory)
 *   5. Envoi des emails via DocumentRequestNotificationService
 *
 * Règle "validation_flagged" :
 *   Si un acteur valide AVEC un commentaire, action_type = validation_flagged.
 *   Le dossier avance normalement, mais la secrétaire voit dans l'historique
 *   qu'une réserve a été formulée — sans état bloquant supplémentaire.
 */
class DocumentRequestTransitionController extends Controller
{
    use ApiResponse, RecordsDocumentHistory;

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

    public function __construct(
        private readonly DocumentRequestNotificationService $notif
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POINT D'ENTRÉE UNIQUE
    // ─────────────────────────────────────────────────────────────────────────

    public function transition(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action'             => 'required|string',
            'motif'              => 'nullable|string|max:1000',
            'signature_type'     => 'nullable|in:paraphe,signature',
            'chef_division_type' => 'nullable|in:formation_distance,formation_continue',
            'resend_to'          => 'nullable|string',
        ]);

        $user   = Auth::user();
        $role   = $user->roles->first()?->slug ?? null;
        $action = $request->action;
        $motif  = $request->motif;

        $demande = DB::table('document_requests')->where('id', $id)->first();
        if (!$demande) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        if (!$this->isActionAllowed($role, $action, $demande->status)) {
            return response()->json([
                'message' => "Action « {$action} » non autorisée pour le rôle « {$role} » depuis le statut « {$demande->status} ».",
            ], 403);
        }

        $statusBefore   = $demande->status;
        $update         = ['updated_at' => now()];
        $historyAction  = null;
        $historyComment = $motif;
        $newStatus      = null;   // statut après transition (pour notifyNextActor)

        // ── Dispatch ─────────────────────────────────────────────────────────

        switch ($action) {

            // ── SECRÉTAIRE ────────────────────────────────────────────────────

            case 'secretaire_validate':
                $newStatus                            = 'comptable_review';
                $update['status']                     = $newStatus;
                $update['processed_by_secretaire_id'] = $user->id;
                $historyAction = $motif ? H::ACTION_VALIDATION_FLAGGED : H::ACTION_VALIDATION;
                break;

            case 'secretaire_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire pour rejeter.'], 422);
                }
                $update['status']             = 'rejected';
                $update['rejected_reason']    = $motif;
                $update['secretaire_comment'] = $motif;
                $update['rejected_by']        = 'Secrétaire';
                $historyAction = H::ACTION_REJET_DEFINITIF;
                break;

            case 'secretaire_reject_final':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $update['status']             = 'rejected';
                $update['rejected_reason']    = $motif;
                $update['secretaire_comment'] = $motif;
                $update['rejected_by']        = 'Secrétaire';
                $historyAction = H::ACTION_REJET_DEFINITIF;
                break;

            case 'secretaire_resend':
                $statusMap = [
                    'comptable'           => 'comptable_review',
                    'chef_division'       => 'chef_division_review',
                    'chef_cap'            => 'chef_cap_review',
                    'sec_da'              => 'sec_dir_adjointe_review',
                    'directrice_adjointe' => 'directrice_adjointe_review',
                    'sec_directeur'       => 'sec_directeur_review',
                    'directeur'           => 'directeur_review',
                ];
                $newStatus = $statusMap[$request->resend_to] ?? null;
                if (!$newStatus) {
                    return response()->json(['message' => 'Destination invalide.'], 422);
                }
                $update['status']          = $newStatus;
                $update['rejected_by']     = null;
                $update['rejected_reason'] = null;
                if ($request->resend_to === 'chef_division' && $request->filled('chef_division_type')) {
                    $update['chef_division_type'] = $request->chef_division_type;
                }
                $historyAction  = H::ACTION_CORRECTION;
                $historyComment = $motif ?? "Dossier corrigé et renvoyé vers : {$request->resend_to}";
                break;

            case 'secretaire_deliver':
                $update['status']       = 'delivered';
                $update['delivered_at'] = now();
                $historyAction = H::ACTION_LIVRAISON;
                break;

            // ── COMPTABLE ─────────────────────────────────────────────────────

            case 'comptable_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire.'], 422);
                }
                $newStatus                           = 'secretaire_correction';
                $update['status']                    = $newStatus;
                $update['comptable_comment']         = $motif;
                $update['rejected_reason']           = $motif;
                $update['rejected_by']               = self::ROLE_LABELS[$role] ?? $role;
                $update['comptable_reviewed_at']     = now();
                $update['processed_by_comptable_id'] = $user->id;
                $historyAction = H::ACTION_REJET_PARTIEL;
                break;

            case 'comptable_validate':
                if (!$request->filled('chef_division_type')) {
                    return response()->json(['message' => 'Vous devez sélectionner le Responsable Division.'], 422);
                }
                $newStatus                           = 'chef_division_review';
                $update['status']                    = $newStatus;
                $update['chef_division_type']        = $request->chef_division_type;
                $update['comptable_reviewed_at']     = now();
                $update['processed_by_comptable_id'] = $user->id;
                $historyAction = $motif ? H::ACTION_VALIDATION_FLAGGED : H::ACTION_VALIDATION;
                break;

            // ── CHEF DIVISION ─────────────────────────────────────────────────

            case 'chef_division_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire.'], 422);
                }
                $newStatus                               = 'secretaire_correction';
                $update['status']                        = $newStatus;
                $update['chef_division_comment']         = $motif;
                $update['rejected_reason']               = $motif;
                $update['rejected_by']                   = self::ROLE_LABELS[$role] ?? $role;
                $update['chef_division_reviewed_at']     = now();
                $update['processed_by_chef_division_id'] = $user->id;
                $historyAction = H::ACTION_REJET_PARTIEL;
                break;

            case 'chef_division_validate':
                $newStatus                               = 'chef_cap_review';
                $update['status']                        = $newStatus;
                $update['chef_division_reviewed_at']     = now();
                $update['processed_by_chef_division_id'] = $user->id;
                $historyAction = $motif ? H::ACTION_VALIDATION_FLAGGED : H::ACTION_VALIDATION;
                break;

            // ── CHEF CAP ──────────────────────────────────────────────────────

            case 'chef_cap_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                          = 'secretaire_correction';
                $update['status']                   = $newStatus;
                $update['rejected_reason']          = $motif;
                $update['rejected_by']              = self::ROLE_LABELS[$role] ?? $role;
                $update['chef_cap_reviewed_at']     = now();
                $update['processed_by_chef_cap_id'] = $user->id;
                $historyAction = H::ACTION_REJET_PARTIEL;
                break;

            case 'chef_cap_sign':
                $sigType                            = $request->signature_type ?? 'signature';
                $update['signature_type']           = $sigType;
                $update['chef_cap_reviewed_at']     = now();
                $update['processed_by_chef_cap_id'] = $user->id;
                if ($sigType === 'paraphe') {
                    $newStatus        = 'sec_dir_adjointe_review';
                    $update['status'] = $newStatus;
                } else {
                    $update['status'] = 'ready';
                    // ready → pas de notifyNextActor, mais mail étudiant ci-dessous
                }
                $historyAction = $motif ? H::ACTION_VALIDATION_FLAGGED : H::ACTION_VALIDATION;
                break;

            // ── SEC. DIRECTRICE ADJOINTE ──────────────────────────────────────

            case 'sec_da_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                    = 'secretaire_correction';
                $update['status']             = $newStatus;
                $update['rejected_reason']    = $motif;
                $update['rejected_by']        = self::ROLE_LABELS[$role] ?? $role;
                $update['sec_da_reviewed_at'] = now();
                $historyAction = H::ACTION_REJET_PARTIEL;
                break;

            case 'sec_da_transmit':
                $newStatus                    = 'directrice_adjointe_review';
                $update['status']             = $newStatus;
                $update['sec_da_reviewed_at'] = now();
                $historyAction = $motif ? H::ACTION_VALIDATION_FLAGGED : H::ACTION_TRANSMISSION;
                break;

            // ── DIRECTRICE ADJOINTE ───────────────────────────────────────────

            case 'directrice_adjointe_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                                 = 'secretaire_correction';
                $update['status']                          = $newStatus;
                $update['rejected_reason']                 = $motif;
                $update['rejected_by']                     = self::ROLE_LABELS[$role] ?? $role;
                $update['directrice_adjointe_reviewed_at'] = now();
                $historyAction = H::ACTION_REJET_PARTIEL;
                break;

            case 'directrice_adjointe_sign':
                $newStatus                                 = 'sec_directeur_review';
                $update['status']                          = $newStatus;
                $update['directrice_adjointe_reviewed_at'] = now();
                $historyAction = $motif ? H::ACTION_VALIDATION_FLAGGED : H::ACTION_VALIDATION;
                break;

            // ── SEC. DIRECTEUR ────────────────────────────────────────────────

            case 'sec_directeur_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                           = 'secretaire_correction';
                $update['status']                    = $newStatus;
                $update['rejected_reason']           = $motif;
                $update['rejected_by']               = self::ROLE_LABELS[$role] ?? $role;
                $update['sec_directeur_reviewed_at'] = now();
                $historyAction = H::ACTION_REJET_PARTIEL;
                break;

            case 'sec_directeur_transmit':
                $newStatus                           = 'directeur_review';
                $update['status']                    = $newStatus;
                $update['sec_directeur_reviewed_at'] = now();
                $historyAction = $motif ? H::ACTION_VALIDATION_FLAGGED : H::ACTION_TRANSMISSION;
                break;

            // ── DIRECTEUR ─────────────────────────────────────────────────────

            case 'directeur_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                 = 'secretaire_correction';
                $update['status']          = $newStatus;
                $update['rejected_reason'] = $motif;
                $update['rejected_by']     = self::ROLE_LABELS[$role] ?? $role;
                $historyAction = H::ACTION_REJET_PARTIEL;
                break;

            case 'directeur_sign':
                $sigType                         = $request->signature_type ?? 'signature';
                $update['signature_type']        = $sigType;
                $update['directeur_reviewed_at'] = now();
                $update['status']                = 'ready';
                $historyAction = $motif ? H::ACTION_VALIDATION_FLAGGED : H::ACTION_VALIDATION;
                break;

            default:
                return response()->json(['message' => "Action inconnue : {$action}"], 422);
        }

        // ── 1. Persistance ────────────────────────────────────────────────────
        DB::table('document_requests')->where('id', $id)->update($update);

        // ── 2. Historique ─────────────────────────────────────────────────────
        if ($historyAction) {
            $this->logHistory(
                $id,
                $historyAction,
                $statusBefore,
                $update['status'] ?? $statusBefore,
                $historyComment
            );
        }

        // ── 3. Emails étudiant ────────────────────────────────────────────────
        $finalStatus = $update['status'] ?? $statusBefore;

        if ($finalStatus === 'rejected') {
            $mail = $this->notif->sendRejectedMail($demande, $motif ?? '');
            if ($mail) $this->logMail($id, $mail['subject']);
        } elseif ($finalStatus === 'ready') {
            $mail = $this->notif->sendReadyMail($demande);
            if ($mail) $this->logMail($id, $mail['subject']);
        } elseif ($finalStatus === 'delivered') {
            $mail = $this->notif->sendDeliveredMail($demande);
            if ($mail) $this->logMail($id, $mail['subject']);
        }

        // ── 4. Notification acteur suivant ────────────────────────────────────
        if ($newStatus) {
            $sentSubjects = $this->notif->notifyNextActor(
                demande:          $demande,
                newStatus:        $newStatus,
                expediteurUser:   $user,
                expediteurRole:   $role,
                chefDivisionType: $update['chef_division_type'] ?? ($demande->chef_division_type ?? null),
                commentaire:      $motif,
            );
            foreach ($sentSubjects as $subject) {
                $this->logMail($id, $subject);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès.',
            'data'    => DB::table('document_requests')->where('id', $id)->first(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MATRICE D'AUTORISATION
    // ─────────────────────────────────────────────────────────────────────────

    private function isActionAllowed(?string $role, string $action, string $currentStatus): bool
    {
        if ($role === 'admin') return true;

        $matrix = [
            'secretaire' => [
                'secretaire_validate'     => ['pending'],
                'secretaire_reject'       => ['pending'],
                'secretaire_resend'       => ['secretaire_correction'],
                'secretaire_reject_final' => ['secretaire_correction'],
                'secretaire_deliver'      => ['ready'],
            ],
            'comptable' => [
                'comptable_reject'   => ['comptable_review'],
                'comptable_validate' => ['comptable_review'],
            ],
            'chef-division' => [
                'chef_division_reject'   => ['chef_division_review'],
                'chef_division_validate' => ['chef_division_review'],
            ],
            'chef-cap' => [
                'chef_cap_reject' => ['chef_cap_review'],
                'chef_cap_sign'   => ['chef_cap_review'],
            ],
            'sec-da' => [
                'sec_da_reject'   => ['sec_dir_adjointe_review'],
                'sec_da_transmit' => ['sec_dir_adjointe_review'],
            ],
            'directrice-adjointe' => [
                'directrice_adjointe_reject' => ['directrice_adjointe_review'],
                'directrice_adjointe_sign'   => ['directrice_adjointe_review'],
            ],
            'sec-dir' => [
                'sec_directeur_reject'   => ['sec_directeur_review'],
                'sec_directeur_transmit' => ['sec_directeur_review'],
            ],
            'directeur' => [
                'directeur_reject' => ['directeur_review'],
                'directeur_sign'   => ['directeur_review'],
            ],
        ];

        $allowedStatuses = $matrix[$role][$action] ?? null;
        return $allowedStatuses !== null && in_array($currentStatus, $allowedStatuses);
    }
}
