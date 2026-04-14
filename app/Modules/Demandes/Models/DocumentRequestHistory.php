<?php

namespace App\Modules\Demandes\Models;

use Illuminate\Database\Eloquent\Model;

/**
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
    // Pas d'updated_at — table immuable
    const UPDATED_AT = null;

    protected $table    = 'document_request_histories';
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
    protected $casts = ['created_at' => 'datetime'];

    // ── Constantes des types d'action ────────────────────────────────────────

    public const ACTION_VALIDATION         = 'validation';
    public const ACTION_VALIDATION_FLAGGED = 'validation_flagged';
    public const ACTION_REJET_PARTIEL      = 'rejet_partiel';
    public const ACTION_REJET_DEFINITIF    = 'rejet_definitif';
    public const ACTION_CORRECTION         = 'correction';
    public const ACTION_TRANSMISSION       = 'transmission';
    public const ACTION_LIVRAISON          = 'livraison';
    public const ACTION_MESSAGE_ENVOYE     = 'message_envoye';

    public const ACTION_LABELS = [
        self::ACTION_VALIDATION         => 'Validation',
        self::ACTION_VALIDATION_FLAGGED => 'Validation avec réserve',
        self::ACTION_REJET_PARTIEL      => 'Rejet partiel – correction demandée',
        self::ACTION_REJET_DEFINITIF    => 'Rejet définitif',
        self::ACTION_CORRECTION         => 'Dossier corrigé & renvoyé',
        self::ACTION_TRANSMISSION       => 'Transmission',
        self::ACTION_LIVRAISON          => 'Document remis à l\'étudiant',
        self::ACTION_MESSAGE_ENVOYE     => 'Email envoyé',
    ];

    // ── Sécurité : interdit toute mise à jour ────────────────────────────────

    public function save(array $options = []): bool
    {
        if (!$this->exists) {
            return parent::save($options);
        }
        throw new \LogicException('DocumentRequestHistory est immuable — aucune mise à jour autorisée.');
    }
}
