<?php

namespace App\Modules\Demandes\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Demandes\Models\DocumentRequestFileComment;
use App\Modules\Demandes\Models\DocumentRequestFileCommentRevision;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Facades\DB;

/**
 * Commentaires des acteurs sur les pièces d'un dossier.
 *
 * Règles :
 *  - tout acteur du circuit Demandes peut commenter une pièce du dossier
 *    (l'accès est contrôlé en amont par le gate demandes.view) ;
 *  - seul l'auteur d'un commentaire peut le modifier ou le supprimer ;
 *  - chaque modification / suppression laisse une révision immuable ;
 *  - l'historique (révisions + commentaires supprimés) n'est visible que
 *    par la secrétaire et l'administrateur.
 */
class FileCommentService
{
    public const SOURCES = ['initial', 'complement', 'secretary'];

    private const HISTORY_ROLES = ['secretaire', 'admin'];

    public function canSeeHistory(User $user): bool
    {
        return in_array($this->role($user), self::HISTORY_ROLES, true);
    }

    /**
     * Liste des commentaires du dossier (toutes pièces), du plus ancien au plus récent.
     */
    public function list(int $demandeId, User $user): array
    {
        $history = $this->canSeeHistory($user);

        $query = DocumentRequestFileComment::query()
            ->where('document_request_id', $demandeId)
            ->orderBy('id');

        if ($history) {
            $query->withTrashed()->with('revisions');
        }

        return [
            'history_visible' => $history,
            'comments'        => $query->get()->map(fn ($c) => $this->present($c, $user, $history))->values()->all(),
        ];
    }

    /**
     * @throws BusinessException
     */
    public function create(int $demandeId, string $source, string $key, string $body, User $user): array
    {
        $this->assertFileExists($demandeId, $source, $key);

        $comment = DocumentRequestFileComment::create([
            'document_request_id' => $demandeId,
            'file_source'         => $source,
            'file_key'            => $key,
            'user_id'             => $user->id,
            'author_name'         => $this->authorName($user),
            'author_role'         => $this->role($user),
            'body'                => $this->clean($body),
        ]);

        return $this->present($comment, $user, $this->canSeeHistory($user));
    }

    /**
     * @throws BusinessException
     */
    public function update(int $demandeId, int $commentId, string $body, User $user): array
    {
        $comment = $this->findOwned($demandeId, $commentId, $user);
        $clean   = $this->clean($body);

        if ($clean === $comment->body) {
            return $this->present($comment, $user, $this->canSeeHistory($user));
        }

        DB::transaction(function () use ($comment, $clean, $user) {
            DocumentRequestFileCommentRevision::create([
                'comment_id'    => $comment->id,
                'action'        => 'edited',
                'previous_body' => $comment->body,
                'user_id'       => $user->id,
                'author_name'   => $this->authorName($user),
                'created_at'    => now(),
            ]);
            $comment->update(['body' => $clean, 'edited_at' => now()]);
        });

        return $this->present($comment->fresh(), $user, $this->canSeeHistory($user));
    }

    /**
     * @throws BusinessException
     */
    public function delete(int $demandeId, int $commentId, User $user): void
    {
        $comment = $this->findOwned($demandeId, $commentId, $user);

        DB::transaction(function () use ($comment, $user) {
            DocumentRequestFileCommentRevision::create([
                'comment_id'    => $comment->id,
                'action'        => 'deleted',
                'previous_body' => $comment->body,
                'user_id'       => $user->id,
                'author_name'   => $this->authorName($user),
                'created_at'    => now(),
            ]);
            $comment->delete();
        });
    }

    // ── Internes ──────────────────────────────────────────────────────────────

    /**
     * @throws BusinessException
     */
    private function findOwned(int $demandeId, int $commentId, User $user): DocumentRequestFileComment
    {
        $comment = DocumentRequestFileComment::query()
            ->where('document_request_id', $demandeId)
            ->find($commentId);

        if (!$comment) {
            throw new BusinessException('Commentaire introuvable.', 'COMMENT_NOT_FOUND', 404);
        }
        if ((int) $comment->user_id !== (int) $user->id) {
            throw new BusinessException("Seul l'auteur de ce commentaire peut le modifier ou le supprimer.", 'NOT_COMMENT_AUTHOR', 403);
        }

        return $comment;
    }

    /**
     * La pièce commentée doit réellement appartenir au dossier.
     *
     * @throws BusinessException
     */
    private function assertFileExists(int $demandeId, string $source, string $key): void
    {
        $row = DB::table('document_requests')
            ->where('id', $demandeId)
            ->select(['files', 'complement_files', 'secretary_files'])
            ->first();

        if (!$row) {
            throw new BusinessException('Dossier introuvable.', 'NOT_FOUND', 404);
        }

        $decode = fn ($raw) => is_array($raw) ? $raw : (is_string($raw) ? (json_decode($raw, true) ?: []) : []);

        $exists = match ($source) {
            'initial'    => array_key_exists($key, $decode($row->files)),
            'complement' => array_key_exists($key, $decode($row->complement_files)),
            'secretary'  => collect($decode($row->secretary_files))->contains(fn ($f) => ($f['id'] ?? null) === $key),
            default      => false,
        };

        if (!$exists) {
            throw new BusinessException('Cette pièce ne fait pas partie du dossier.', 'FILE_NOT_FOUND', 404);
        }
    }

    private function present(DocumentRequestFileComment $c, User $user, bool $history): array
    {
        $mine = (int) $c->user_id === (int) $user->id;
        $role = $c->author_role;

        $data = [
            'id'          => $c->id,
            'file_source' => $c->file_source,
            'file_key'    => $c->file_key,
            'author_name' => $c->author_name,
            'author_role' => $role,
            'author_role_label' => WorkflowConstants::ROLE_LABELS[$role] ?? $role,
            'body'        => $c->trashed() ? null : $c->body,
            'created_at'  => $c->created_at?->toIso8601String(),
            'edited_at'   => $c->edited_at?->toIso8601String(),
            'deleted_at'  => $c->deleted_at?->toIso8601String(),
            'is_mine'     => $mine,
            'can_edit'    => $mine && !$c->trashed(),
        ];

        if ($history) {
            // La secrétaire voit le dernier texte d'un commentaire supprimé
            // dans ses révisions (previous_body de la révision « deleted »).
            $data['revisions'] = $c->relationLoaded('revisions')
                ? $c->revisions->map(fn ($r) => [
                    'action'        => $r->action,
                    'previous_body' => $r->previous_body,
                    'author_name'   => $r->author_name,
                    'created_at'    => $r->created_at?->toIso8601String(),
                ])->values()->all()
                : [];
        }

        return $data;
    }

    private function clean(string $body): string
    {
        return trim(strip_tags($body));
    }

    private function role(User $user): ?string
    {
        return WorkflowConstants::canonicalRole($user->roles->first()?->slug);
    }

    private function authorName(User $user): string
    {
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        return mb_substr($name !== '' ? $name : ($user->email ?? 'Utilisateur'), 0, 200);
    }
}
