<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Trace immuable d'une modification ou d'une suppression de commentaire.
 */
class DocumentRequestFileCommentRevision extends Model
{
    public $timestamps = false;

    protected $table = 'document_request_file_comment_revisions';

    protected $fillable = [
        'comment_id',
        'action',
        'previous_body',
        'user_id',
        'author_name',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \LogicException("Une révision de commentaire ne peut pas être modifiée.");
        }
        return parent::save($options);
    }
}
