<?php

namespace App\Modules\CahierTexte\Services;

use App\Modules\CahierTexte\Models\TextbookComment;
use Illuminate\Support\Facades\Log;

class TextbookCommentService
{
    /**
     * Récupérer tous les commentaires d'une entrée
     */
    public function getByEntry(int $entryId)
    {
        return TextbookComment::where('textbook_entry_id', $entryId)
            ->topLevel()
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Créer un nouveau commentaire
     */
    public function create(int $entryId, array $data, $user)
    {
        try {
            $comment = TextbookComment::create([
                'textbook_entry_id' => $entryId,
                'user_id' => $user->id,
                'comment' => $data['comment'],
                'type' => $data['type'] ?? 'comment',
                'parent_id' => $data['parent_id'] ?? null,
            ]);
            
            Log::info('Commentaire cahier de texte créé', [
                'comment_id' => $comment->id,
                'entry_id' => $entryId,
                'user_id' => $user->id,
            ]);

            return $comment->load('user');
        } catch (\Exception $e) {
            Log::error('Erreur création commentaire cahier de texte', [
                'entry_id' => $entryId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mettre à jour un commentaire
     */
    public function update(int $id, array $data)
    {
        try {
            $comment = TextbookComment::findOrFail($id);
            $comment->update($data);
            
            Log::info('Commentaire cahier de texte mis à jour', [
                'comment_id' => $id,
            ]);

            return $comment->fresh('user');
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour commentaire cahier de texte', [
                'comment_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Supprimer un commentaire
     */
    public function delete(int $id)
    {
        try {
            $comment = TextbookComment::findOrFail($id);
            $comment->delete();
            
            Log::info('Commentaire cahier de texte supprimé', [
                'comment_id' => $id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur suppression commentaire cahier de texte', [
                'comment_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
