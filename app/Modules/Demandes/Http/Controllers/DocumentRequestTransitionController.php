<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Models\DocumentRequestHistory as H;
use App\Modules\Demandes\Traits\RecordsDocumentHistory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * DocumentRequestTransitionController
 *
 * Responsabilité : exécuter les transitions de statut (workflow complet).
 *
 * À chaque action :
 *   1. Transition appliquée sur document_requests
 *   2. Entrée insérée dans document_request_histories (via RecordsDocumentHistory)
 *   3. Mail étudiant si applicable (rejet / prêt / remis)
 *   4. Mail inter-acteurs au prochain maillon du workflow
 *
 * Règle des validations sous réserve :
 *   Chaque rôle expose DEUX actions de validation distinctes :
 *     [role]_validate          → validation normale, commentaire optionnel, pas de flag
 *     [role]_validate_flagged  → validation sous réserve, commentaire OBLIGATOIRE,
 *                                positionne has_flag = true sur le dossier
 *
 *   La secrétaire peut acquitter le flag via : secretaire_acknowledge_flag
 *   (disponible quel que soit le statut courant, si has_flag = true)
 */
class DocumentRequestTransitionController extends Controller
{
    use ApiResponse, RecordsDocumentHistory;

    // ── Labels ───────────────────────────────────────────────────────────────

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
     * Mapping statut → rôle destinataire pour les notifications inter-acteurs.
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
    // POINT D'ENTRÉE
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

        // ── Cas spécial : acknowledge du flag ─────────────────────────────────
        // Traité avant la matrice d'autorisation car il opère sur has_flag
        // indépendamment du statut courant.
        if ($action === 'secretaire_acknowledge_flag') {
            return $this->acknowledgeFlag($demande, $user, $role);
        }

        if (!$this->isActionAllowed($role, $action, $demande->status)) {
            return response()->json([
                'message' => "Action « {$action} » non autorisée pour le rôle « {$role} » depuis le statut « {$demande->status} ».",
            ], 403);
        }

        $statusBefore  = $demande->status;
        $update        = ['updated_at' => now()];
        $mailData      = null;
        $newStatus     = null;
        $historyAction = null;

        // ── Dispatch ─────────────────────────────────────────────────────────

        switch ($action) {

            // ═══════════════════════════════════════════════════════════════
            // SECRÉTAIRE
            // ═══════════════════════════════════════════════════════════════

            case 'secretaire_validate':
                $newStatus                            = 'comptable_review';
                $update['status']                     = $newStatus;
                $update['processed_by_secretaire_id'] = $user->id;
                $historyAction                        = H::ACTION_VALIDATION;
                break;

            case 'secretaire_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire pour rejeter.'], 422);
                }
                $update['status']             = 'rejected';
                $update['rejected_reason']    = $motif;
                $update['secretaire_comment'] = $motif;
                $update['rejected_by']        = 'Secrétaire';
                $mailData                     = $this->rejectedMail($demande, $motif);
                $historyAction                = H::ACTION_REJET_DEFINITIF;
                break;

            case 'secretaire_reject_final':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $update['status']             = 'rejected';
                $update['rejected_reason']    = $motif;
                $update['secretaire_comment'] = $motif;
                $update['rejected_by']        = 'Secrétaire';
                $mailData                     = $this->rejectedMail($demande, $motif);
                $historyAction                = H::ACTION_REJET_DEFINITIF;
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
                $historyAction = H::ACTION_CORRECTION;
                $motif         = $motif ?? "Dossier corrigé et renvoyé vers : {$request->resend_to}";
                break;

            case 'secretaire_deliver':
                $update['status']       = 'delivered';
                $update['delivered_at'] = now();
                $mailData               = $this->deliveredMail($demande);
                $historyAction          = H::ACTION_LIVRAISON;
                break;

            // ═══════════════════════════════════════════════════════════════
            // COMPTABLE
            // ═══════════════════════════════════════════════════════════════

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
                $historyAction                       = H::ACTION_REJET_PARTIEL;
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
                $historyAction                       = H::ACTION_VALIDATION;
                break;

            case 'comptable_validate_flagged':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire pour une validation sous réserve.'], 422);
                }
                if (!$request->filled('chef_division_type')) {
                    return response()->json(['message' => 'Vous devez sélectionner le Responsable Division.'], 422);
                }
                $newStatus                           = 'chef_division_review';
                $update['status']                    = $newStatus;
                $update['has_flag']                  = true;
                $update['chef_division_type']        = $request->chef_division_type;
                $update['comptable_reviewed_at']     = now();
                $update['processed_by_comptable_id'] = $user->id;
                $historyAction                       = H::ACTION_VALIDATION_FLAGGED;
                break;

            // ═══════════════════════════════════════════════════════════════
            // CHEF DIVISION
            // ═══════════════════════════════════════════════════════════════

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
                $historyAction                           = H::ACTION_REJET_PARTIEL;
                break;

            case 'chef_division_validate':
                $newStatus                               = 'chef_cap_review';
                $update['status']                        = $newStatus;
                $update['chef_division_reviewed_at']     = now();
                $update['processed_by_chef_division_id'] = $user->id;
                $historyAction                           = H::ACTION_VALIDATION;
                break;

            case 'chef_division_validate_flagged':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire pour une validation sous réserve.'], 422);
                }
                $newStatus                               = 'chef_cap_review';
                $update['status']                        = $newStatus;
                $update['has_flag']                      = true;
                $update['chef_division_reviewed_at']     = now();
                $update['processed_by_chef_division_id'] = $user->id;
                $historyAction                           = H::ACTION_VALIDATION_FLAGGED;
                break;

            // ═══════════════════════════════════════════════════════════════
            // CHEF CAP
            // ═══════════════════════════════════════════════════════════════

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
                $historyAction                      = H::ACTION_REJET_PARTIEL;
                break;

            case 'chef_cap_validate':
                $sigType                            = $request->signature_type ?? 'signature';
                $update['signature_type']           = $sigType;
                $update['chef_cap_reviewed_at']     = now();
                $update['processed_by_chef_cap_id'] = $user->id;
                if ($sigType === 'paraphe') {
                    $newStatus        = 'sec_dir_adjointe_review';
                    $update['status'] = $newStatus;
                } else {
                    $update['status'] = 'ready';
                    $mailData         = $this->readyMail($demande);
                }
                $historyAction = H::ACTION_VALIDATION;
                break;

            // Alias historique (l'ancienne action s'appelait chef_cap_sign)
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
                    $mailData         = $this->readyMail($demande);
                }
                $historyAction = H::ACTION_VALIDATION;
                break;

            case 'chef_cap_validate_flagged':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire pour une validation sous réserve.'], 422);
                }
                $sigType                            = $request->signature_type ?? 'signature';
                $update['signature_type']           = $sigType;
                $update['has_flag']                 = true;
                $update['chef_cap_reviewed_at']     = now();
                $update['processed_by_chef_cap_id'] = $user->id;
                if ($sigType === 'paraphe') {
                    $newStatus        = 'sec_dir_adjointe_review';
                    $update['status'] = $newStatus;
                } else {
                    $update['status'] = 'ready';
                    $mailData         = $this->readyMail($demande);
                }
                $historyAction = H::ACTION_VALIDATION_FLAGGED;
                break;

            // ═══════════════════════════════════════════════════════════════
            // SEC. DIRECTRICE ADJOINTE
            // ═══════════════════════════════════════════════════════════════

            case 'sec_da_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                    = 'secretaire_correction';
                $update['status']             = $newStatus;
                $update['rejected_reason']    = $motif;
                $update['rejected_by']        = self::ROLE_LABELS[$role] ?? $role;
                $update['sec_da_reviewed_at'] = now();
                $historyAction                = H::ACTION_REJET_PARTIEL;
                break;

            case 'sec_da_transmit':
            case 'sec_dir_adjointe_validate':
                $newStatus                    = 'directrice_adjointe_review';
                $update['status']             = $newStatus;
                $update['sec_da_reviewed_at'] = now();
                $historyAction                = H::ACTION_TRANSMISSION;
                break;

            case 'sec_dir_adjointe_validate_flagged':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire pour une validation sous réserve.'], 422);
                }
                $newStatus                    = 'directrice_adjointe_review';
                $update['status']             = $newStatus;
                $update['has_flag']           = true;
                $update['sec_da_reviewed_at'] = now();
                $historyAction                = H::ACTION_VALIDATION_FLAGGED;
                break;

            // ═══════════════════════════════════════════════════════════════
            // DIRECTRICE ADJOINTE
            // ═══════════════════════════════════════════════════════════════

            case 'directrice_adjointe_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                                 = 'secretaire_correction';
                $update['status']                          = $newStatus;
                $update['rejected_reason']                 = $motif;
                $update['rejected_by']                     = self::ROLE_LABELS[$role] ?? $role;
                $update['directrice_adjointe_reviewed_at'] = now();
                $historyAction                             = H::ACTION_REJET_PARTIEL;
                break;

            case 'directrice_adjointe_validate':
            case 'directrice_adjointe_sign':
                $newStatus                                 = 'sec_directeur_review';
                $update['status']                          = $newStatus;
                $update['directrice_adjointe_reviewed_at'] = now();
                $historyAction                             = H::ACTION_VALIDATION;
                break;

            case 'directrice_adjointe_validate_flagged':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire pour une validation sous réserve.'], 422);
                }
                $newStatus                                 = 'sec_directeur_review';
                $update['status']                          = $newStatus;
                $update['has_flag']                        = true;
                $update['directrice_adjointe_reviewed_at'] = now();
                $historyAction                             = H::ACTION_VALIDATION_FLAGGED;
                break;

            // ═══════════════════════════════════════════════════════════════
            // SEC. DIRECTEUR
            // ═══════════════════════════════════════════════════════════════

            case 'sec_directeur_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                           = 'secretaire_correction';
                $update['status']                    = $newStatus;
                $update['rejected_reason']           = $motif;
                $update['rejected_by']               = self::ROLE_LABELS[$role] ?? $role;
                $update['sec_directeur_reviewed_at'] = now();
                $historyAction                       = H::ACTION_REJET_PARTIEL;
                break;

            case 'sec_directeur_transmit':
            case 'sec_directeur_validate':
                $newStatus                           = 'directeur_review';
                $update['status']                    = $newStatus;
                $update['sec_directeur_reviewed_at'] = now();
                $historyAction                       = H::ACTION_TRANSMISSION;
                break;

            case 'sec_directeur_validate_flagged':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire pour une validation sous réserve.'], 422);
                }
                $newStatus                           = 'directeur_review';
                $update['status']                    = $newStatus;
                $update['has_flag']                  = true;
                $update['sec_directeur_reviewed_at'] = now();
                $historyAction                       = H::ACTION_VALIDATION_FLAGGED;
                break;

            // ═══════════════════════════════════════════════════════════════
            // DIRECTEUR
            // ═══════════════════════════════════════════════════════════════

            case 'directeur_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                 = 'secretaire_correction';
                $update['status']          = $newStatus;
                $update['rejected_reason'] = $motif;
                $update['rejected_by']     = self::ROLE_LABELS[$role] ?? $role;
                $historyAction             = H::ACTION_REJET_PARTIEL;
                break;

            case 'directeur_validate':
            case 'directeur_sign':
                $sigType                         = $request->signature_type ?? 'signature';
                $update['signature_type']        = $sigType;
                $update['directeur_reviewed_at'] = now();
                $update['status']                = 'ready';
                $mailData                        = $this->readyMail($demande);
                $historyAction                   = H::ACTION_VALIDATION;
                break;

            case 'directeur_validate_flagged':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire pour une validation sous réserve.'], 422);
                }
                $sigType                         = $request->signature_type ?? 'signature';
                $update['signature_type']        = $sigType;
                $update['has_flag']              = true;
                $update['directeur_reviewed_at'] = now();
                $update['status']                = 'ready';
                $mailData                        = $this->readyMail($demande);
                $historyAction                   = H::ACTION_VALIDATION_FLAGGED;
                break;

            default:
                return response()->json(['message' => "Action inconnue : {$action}"], 422);
        }

        // ── Persistance ───────────────────────────────────────────────────────

        DB::table('document_requests')->where('id', $id)->update($update);

        // ── Historique ────────────────────────────────────────────────────────

        if ($historyAction) {
            $this->logHistory(
                $id,
                $historyAction,
                $statusBefore,
                $update['status'] ?? $statusBefore,
                $motif
            );
        }

        // ── Mail étudiant ─────────────────────────────────────────────────────

        if ($mailData && $demande->email) {
            try {
                Mail::send(
                    $mailData['view'],
                    $mailData['vars'],
                    fn($m) => $m->to($demande->email)->subject($mailData['subject'])
                );
                $this->logMail($id, $mailData['subject']);
            } catch (\Exception $e) {
                Log::error('Erreur envoi mail étudiant', [
                    'error' => $e->getMessage(),
                    'ref'   => $demande->reference,
                ]);
            }
        }

        // ── Notification inter-acteurs ────────────────────────────────────────

        if ($newStatus) {
            $this->notifyNextActor(
                demande:          $demande,
                newStatus:        $newStatus,
                expediteurUser:   $user,
                expediteurRole:   $role,
                chefDivisionType: $update['chef_division_type'] ?? ($demande->chef_division_type ?? null),
                commentaire:      $motif ?? null,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès.',
            'data'    => DB::table('document_requests')->where('id', $id)->first(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACQUITTEMENT DU FLAG
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * La secrétaire acquitte le flag d'un dossier (action hors workflow normal).
     *
     * Pré-condition : has_flag = true sur le dossier.
     * Effet         : has_flag → false, entrée flag_acknowledged dans l'historique.
     * Statut        : INCHANGÉ (status_before = status_after).
     */
    private function acknowledgeFlag(object $demande, object $user, ?string $role): JsonResponse
    {
        // Seul la secrétaire (ou admin) peut acquitter
        if (!in_array($role, ['secretaire', 'admin'])) {
            return response()->json(['message' => 'Seule la secrétaire peut acquitter un flag.'], 403);
        }

        if (!$demande->has_flag) {
            return response()->json(['message' => 'Ce dossier ne possède pas de flag actif.'], 422);
        }

        DB::table('document_requests')
            ->where('id', $demande->id)
            ->update(['has_flag' => false, 'updated_at' => now()]);

        // Historique : status_before = status_after (pas de transition)
        $this->logHistory(
            $demande->id,
            H::ACTION_FLAG_ACKNOWLEDGED,
            $demande->status,  // inchangé
            $demande->status,  // inchangé
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Flag acquitté avec succès.',
            'data'    => DB::table('document_requests')->where('id', $demande->id)->first(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MATRICE D'AUTORISATIONS
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
                'comptable_reject'           => ['comptable_review'],
                'comptable_validate'         => ['comptable_review'],
                'comptable_validate_flagged' => ['comptable_review'],
            ],
            'chef-division' => [
                'chef_division_reject'           => ['chef_division_review'],
                'chef_division_validate'         => ['chef_division_review'],
                'chef_division_validate_flagged' => ['chef_division_review'],
            ],
            'chef-cap' => [
                'chef_cap_reject'           => ['chef_cap_review'],
                'chef_cap_validate'         => ['chef_cap_review'],
                'chef_cap_sign'             => ['chef_cap_review'],   // alias
                'chef_cap_validate_flagged' => ['chef_cap_review'],
            ],
            'sec-da' => [
                'sec_da_reject'                  => ['sec_dir_adjointe_review'],
                'sec_da_transmit'                => ['sec_dir_adjointe_review'],
                'sec_dir_adjointe_validate'      => ['sec_dir_adjointe_review'],
                'sec_dir_adjointe_validate_flagged' => ['sec_dir_adjointe_review'],
            ],
            'directrice-adjointe' => [
                'directrice_adjointe_reject'           => ['directrice_adjointe_review'],
                'directrice_adjointe_sign'             => ['directrice_adjointe_review'],
                'directrice_adjointe_validate'         => ['directrice_adjointe_review'],
                'directrice_adjointe_validate_flagged' => ['directrice_adjointe_review'],
            ],
            'sec-dir' => [
                'sec_directeur_reject'           => ['sec_directeur_review'],
                'sec_directeur_transmit'         => ['sec_directeur_review'],
                'sec_directeur_validate'         => ['sec_directeur_review'],
                'sec_directeur_validate_flagged' => ['sec_directeur_review'],
            ],
            'directeur' => [
                'directeur_reject'           => ['directeur_review'],
                'directeur_sign'             => ['directeur_review'],
                'directeur_validate'         => ['directeur_review'],
                'directeur_validate_flagged' => ['directeur_review'],
            ],
        ];

        $allowedStatuses = $matrix[$role][$action] ?? null;
        return $allowedStatuses !== null && in_array($currentStatus, $allowedStatuses);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NOTIFICATION INTER-ACTEURS
    // ─────────────────────────────────────────────────────────────────────────

    private function notifyNextActor(
        object  $demande,
        string  $newStatus,
        object  $expediteurUser,
        ?string $expediteurRole,
        ?string $chefDivisionType = null,
        ?string $commentaire      = null,
    ): void {
        $targetRoleSlug = self::STATUS_TO_ROLE[$newStatus] ?? null;
        if (!$targetRoleSlug) {
            return;
        }

        $query = DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'ru.role_id', '=', 'r.id')
            ->where('r.slug', $targetRoleSlug)
            ->whereNotNull('u.email')
            ->select(
                'u.id',
                DB::raw("CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as name"),
                'u.email',
                'u.chef_division_type'
            );

        if ($targetRoleSlug === 'chef-division' && $chefDivisionType) {
            $query->where('u.chef_division_type', $chefDivisionType);
        }

        $destinataires = $query->get();

        if ($destinataires->isEmpty()) {
            Log::warning('notifyNextActor : aucun utilisateur trouvé', [
                'role'   => $targetRoleSlug,
                'statut' => $newStatus,
                'ref'    => $demande->reference,
            ]);
            return;
        }

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

        $etudiantNom       = trim(($etudiantInfo->first_names ?? '') . ' ' . ($etudiantInfo->last_name ?? '')) ?: 'Étudiant(e)';
        $expediteurNomAff  = $expediteurUser->name ?? trim(($expediteurUser->first_name ?? '') . ' ' . ($expediteurUser->last_name ?? '')) ?: 'Un acteur';
        $expediteurRoleAff = self::ROLE_LABELS[$expediteurRole] ?? ($expediteurRole ?? 'Acteur');

        foreach ($destinataires as $dest) {
            try {
                Mail::send(
                    'core::emails.dossier-transmis',
                    [
                        'destinataireNom'   => trim($dest->name),
                        'destinataireRole'  => self::ROLE_LABELS[$targetRoleSlug] ?? $targetRoleSlug,
                        'expediteurNom'     => $expediteurNomAff,
                        'expediteurRole'    => $expediteurRoleAff,
                        'reference'         => $demande->reference,
                        'typeDocument'      => self::TYPE_LABELS[$demande->type] ?? $demande->type,
                        'etudiantNom'       => $etudiantNom,
                        'etudiantMatricule' => $etudiantInfo->matricule ?? null,
                        'dateTransmission'  => now()->format('d/m/Y à H:i'),
                        'commentaire'       => $commentaire,
                        'urlEspace'         => config('app.url') . '/dashboard',
                        'etablissement'     => config('app.name', 'CAP-EPAC'),
                    ],
                    fn($m) => $m
                        ->to($dest->email, trim($dest->name))
                        ->subject("📂 Dossier à traiter — Réf : {$demande->reference}")
                );

                $this->logMail($demande->id, "Notification → {$dest->email} ({$targetRoleSlug})");

            } catch (\Exception $e) {
                Log::error('Erreur envoi mail acteur interne', [
                    'error'        => $e->getMessage(),
                    'destinataire' => $dest->email,
                    'ref'          => $demande->reference,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MAILS ÉTUDIANT
    // ─────────────────────────────────────────────────────────────────────────

    private function rejectedMail(object $demande, string $motif): array
    {
        return [
            'view'    => 'core::emails.demande-rejected',
            'subject' => "Votre demande a été rejetée — Réf : {$demande->reference}",
            'vars'    => ['reference' => $demande->reference, 'type' => self::TYPE_LABELS[$demande->type] ?? $demande->type, 'motif' => $motif],
        ];
    }

    private function readyMail(object $demande): array
    {
        return [
            'view'    => 'core::emails.demande-ready',
            'subject' => "Votre document est prêt — Réf : {$demande->reference}",
            'vars'    => ['reference' => $demande->reference, 'type' => self::TYPE_LABELS[$demande->type] ?? $demande->type],
        ];
    }

    private function deliveredMail(object $demande): array
    {
        return [
            'view'    => 'core::emails.demande-delivered',
            'subject' => "Votre document vous a été remis — Réf : {$demande->reference}",
            'vars'    => ['reference' => $demande->reference, 'type' => self::TYPE_LABELS[$demande->type] ?? $demande->type],
        ];
    }
}
