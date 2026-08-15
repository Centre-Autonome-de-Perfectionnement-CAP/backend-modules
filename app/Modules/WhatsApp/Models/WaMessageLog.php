<?php

namespace App\Modules\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $recipient
 * @property string $message
 * @property string|null $context
 * @property string $status queued|sending|sent|failed
 * @property int $attempts
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $queued_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 */
class WaMessageLog extends Model
{
    protected $table = 'wa_message_log';

    protected $fillable = [
        'recipient',
        'message',
        'context',
        'module',
        'file_name',
        'media_type',
        'file_disk',
        'file_path',
        'status',
        'attempts',
        'last_error',
        'queued_at',
        'sent_at',
    ];

    protected $casts = [
        'attempts'  => 'integer',
        'queued_at' => 'datetime',
        'sent_at'   => 'datetime',
    ];
}
