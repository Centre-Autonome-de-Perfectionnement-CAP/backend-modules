<?php

namespace App\Modules\Contact\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
        'admin_notes',
        'read_at',
        'replied_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope pour filtrer les messages non lus
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope pour filtrer les messages lus
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Scope pour filtrer les messages avec réponse
     */
    public function scopeReplied($query)
    {
        return $query->where('status', 'replied');
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead()
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Marquer comme répondu
     */
    public function markAsReplied()
    {
        $this->update([
            'status' => 'replied',
            'replied_at' => now(),
        ]);
    }
}
