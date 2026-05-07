<?php

namespace App\Modules\CahierTexte\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TextbookComment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'textbook_entry_id',
        'user_id',
        'comment',
        'type',
        'parent_id',
    ];

    /**
     * Relation avec l'entrée du cahier de texte
     */
    public function textbookEntry()
    {
        return $this->belongsTo(TextbookEntry::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le commentaire parent
     */
    public function parent()
    {
        return $this->belongsTo(TextbookComment::class, 'parent_id');
    }

    /**
     * Relation avec les réponses
     */
    public function replies()
    {
        return $this->hasMany(TextbookComment::class, 'parent_id');
    }

    /**
     * Scope pour les commentaires de premier niveau
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }
}
