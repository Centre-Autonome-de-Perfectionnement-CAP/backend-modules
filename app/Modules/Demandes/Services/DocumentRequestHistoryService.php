<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\Models\DocumentRequestHistory;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Facades\Auth;

/**
 * Gère toutes les écritures dans document_request_histories.
 * Chaque entrée est immuable après création.
 */
class DocumentRequestHistoryService
{
    /**
     * Enregistre une transition de workflow.
     */
    public function record(
        int     $documentRequestId,
        string  $action,
        string  $statusBefore,
        string  $statusAfter,
        ?string $comment = null,
    ): DocumentRequestHistory {
        $user = Auth::user();

        return DocumentRequestHistory::create([
            'document_request_id' => $documentRequestId,
            'actor_id'            => $user->id,
            'actor_name'          => $user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'actor_role'          => $user->roles->first()?->slug ?? 'inconnu',
            'action_type'         => $this->resolveActionType($action),
            'action_label'        => WorkflowConstants::ACTION_LABELS[$action] ?? $action,
            'status_before'       => $statusBefore,
            'status_after'        => $statusAfter,
            'comment'             => $comment,
        ]);
    }

    /**
     * Enregistre la levée d'une réserve (flag_cleared).
     * Pas de changement de statut, on enregistre quand même pour traçabilité.
     */
    public function recordFlagCleared(int $documentRequestId, string $currentStatus): DocumentRequestHistory
    {
        return $this->record(
            documentRequestId: $documentRequestId,
            action:            'clear_flag',
            statusBefore:      $currentStatus,
            statusAfter:       $currentStatus,
            comment:           null,
        );
    }

    /**
     * Récupère l'historique d'une demande avec le champ is_own_action.
     */
    public function getForDemande(int $documentRequestId): array
    {
        $userId = Auth::id();

        return DocumentRequestHistory::where('document_request_id', $documentRequestId)
            ->orderBy('created_at')
            ->get()
            ->map(fn($entry) => array_merge($entry->toArray(), [
                'is_own_action' => $entry->actor_id === $userId,
            ]))
            ->all();
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    private function resolveActionType(string $action): string
    {
        if (str_ends_with($action, '_flagged')) {
            return 'validation_flagged';
        }

        if ($action === 'clear_flag') {
            return 'flag_cleared';
        }

        if (str_contains($action, 'reject')) {
            return 'rejection';
        }

        if ($action === 'secretaire_resend') {
            return 'resend';
        }

        if ($action === 'secretaire_deliver') {
            return 'delivery';
        }

        return 'validation';
    }
}
