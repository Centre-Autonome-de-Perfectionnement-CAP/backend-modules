<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\Models\DocumentRequestHistory as H;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DocumentRequestHistoryService
 *
 * Seule classe autorisée à écrire dans document_request_histories.
 * Injectée via le trait RecordsDocumentHistory dans les controllers de transition.
 */
class DocumentRequestHistoryService
{
    /**
     * Enregistre une action.
     *
     * @param  array|null $actorOverride  ['id', 'name', 'role'] — si null, Auth::user() est utilisé
     */
    public function record(
        int     $documentRequestId,
        string  $actionType,
        ?string $statusBefore  = null,
        ?string $statusAfter   = null,
        ?string $comment       = null,
        ?array  $actorOverride = null
    ): H {
        if ($actorOverride) {
            $actorId   = $actorOverride['id']   ?? null;
            $actorName = $actorOverride['name']  ?? 'Système';
            $actorRole = $actorOverride['role']  ?? 'system';
        } else {
            $user      = Auth::user();
            $actorId   = $user?->id;
            $actorName = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
                : 'Système';
            $actorRole = $user?->roles?->first()?->slug ?? 'unknown';
        }

        return H::create([
            'document_request_id' => $documentRequestId,
            'actor_id'            => $actorId,
            'actor_name'          => $actorName ?: 'Inconnu',
            'actor_role'          => $actorRole,
            'action_type'         => $actionType,
            'status_before'       => $statusBefore,
            'status_after'        => $statusAfter,
            'comment'             => $comment,
        ]);
    }

    /**
     * Enregistre l'envoi d'un email (étudiant ou acteur interne).
     */
    public function recordMail(int $documentRequestId, string $subject): void
    {
        H::create([
            'document_request_id' => $documentRequestId,
            'actor_id'            => null,
            'actor_name'          => 'Système',
            'actor_role'          => 'system',
            'action_type'         => H::ACTION_MESSAGE_ENVOYE,
            'status_before'       => null,
            'status_after'        => null,
            'comment'             => "Email : {$subject}",
        ]);
    }

    /**
     * Retourne l'historique complet d'un dossier, ordre chronologique croissant.
     * Chaque entrée reçoit :
     *   - action_label   : libellé lisible du type d'action
     *   - is_own_action  : true si l'entrée appartient au rôle $currentRole
     *                      (hint UI — ne restreint pas la visibilité)
     */
    public function getHistory(int $documentRequestId, ?string $currentRole = null): \Illuminate\Support\Collection
    {
        return DB::table('document_request_histories')
            ->where('document_request_id', $documentRequestId)
            ->orderBy('created_at', 'asc')
            ->select([
                'id', 'actor_id', 'actor_name', 'actor_role',
                'action_type', 'status_before', 'status_after',
                'comment', 'created_at',
            ])
            ->get()
            ->map(function ($entry) use ($currentRole) {
                $entry->action_label  = H::ACTION_LABELS[$entry->action_type] ?? $entry->action_type;
                $entry->is_own_action = ($currentRole !== null && $entry->actor_role === $currentRole);
                return $entry;
            });
    }
}
