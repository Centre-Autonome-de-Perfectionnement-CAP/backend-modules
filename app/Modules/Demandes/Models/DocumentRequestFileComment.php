<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Commentaire d'un acteur sur une pièce du dossier.
 * Seul l'auteur peut le modifier ou le supprimer (voir FileCommentService).
 */
class DocumentRequestFileComment extends Model
{
    use SoftDeletes;

    protected $table = 'document_request_file_comments';

    protected $fillable = [
        'document_request_id',
        'file_source',
        'file_key',
        'user_id',
        'author_name',
        'author_role',
        'body',
        'edited_at',
    ];

    protected $casts = [
        'edited_at'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRequestFileCommentRevision::class, 'comment_id')->orderBy('id');
    }
}
