<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequestHistory extends Model
{
    public $timestamps = false;  // seulement created_at, pas updated_at

    protected $table = 'document_request_histories';

    protected $fillable = [
        'document_request_id',
        'actor_id',
        'actor_name',
        'actor_role',
        'action_type',
        'action_label',
        'status_before',
        'status_after',
        'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Garde l'historique immuable ───────────────────────────────────────────

    public function save(array $options = []): bool
    {
        if (!$this->wasRecentlyCreated && $this->exists) {
            throw new \LogicException('Une entrée d\'historique ne peut pas être modifiée après création.');
        }
        return parent::save($options);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Demandes\Models\DocumentRequest::class);
    }
}
