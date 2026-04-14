<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle immuable : aucune mise à jour n'est autorisée après insertion.
 *
 * @property int         $id
 * @property int         $document_request_id
 * @property int|null    $actor_id
 * @property string      $actor_name
 * @property string      $actor_role
 * @property string      $action_type
 * @property string|null $status_before
 * @property string|null $status_after
 * @property string|null $comment
 * @property \Carbon\Carbon $created_at
 */
class DocumentRequestHistory extends Model
{
    const UPDATED_AT = null;

    protected $table = 'document_request_histories';

    protected $fillable = [
        'document_request_id',
        'actor_id',
        'actor_name',
        'actor_role',
        'action_type',
        'status_before',
        'status_after',
        'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Types d'action ───────────────────────────────────────────────────────

    public const ACTION_VALIDATION          = 'validation';
    public const ACTION_VALIDATION_FLAGGED  = 'validation_flagged';
    public const ACTION_FLAG_ACKNOWLEDGED   = 'flag_acknowledged';
    public const ACTION_REJET_PARTIEL       = 'rejet_partiel';
    public const ACTION_REJET_DEFINITIF     = 'rejet_definitif';
    public const ACTION_COMPLEMENT          = 'complement_demande';
    public const ACTION_CORRECTION          = 'correction';
    public const ACTION_TRANSMISSION        = 'transmission';
    public const ACTION_LIVRAISON           = 'livraison';
    public const ACTION_MESSAGE_ENVOYE      = 'message_envoye';

    // ── Labels lisibles ──────────────────────────────────────────────────────

    public const ACTION_LABELS = [
        self::ACTION_VALIDATION         => 'Validation',
        self::ACTION_VALIDATION_FLAGGED => 'Validation sous réserve',
        self::ACTION_FLAG_ACKNOWLEDGED  => 'Réserve acquittée',
        self::ACTION_REJET_PARTIEL      => 'Rejet partiel (correction demandée)',
        self::ACTION_REJET_DEFINITIF    => 'Rejet définitif',
        self::ACTION_COMPLEMENT         => 'Complément de dossier demandé',
        self::ACTION_CORRECTION         => 'Dossier corrigé & renvoyé',
        self::ACTION_TRANSMISSION       => 'Transmission',
        self::ACTION_LIVRAISON          => 'Document remis à l\'étudiant',
        self::ACTION_MESSAGE_ENVOYE     => 'Email envoyé',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DocumentRequest::class);
    }

    // ── Accesseur ────────────────────────────────────────────────────────────

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action_type] ?? $this->action_type;
    }

    // ── Sécurité immuabilité ─────────────────────────────────────────────────

    public function save(array $options = []): bool
    {
        if (!$this->exists) {
            return parent::save($options);
        }
        throw new \LogicException('DocumentRequestHistory est immuable. Aucune mise à jour autorisée.');
    }
}
