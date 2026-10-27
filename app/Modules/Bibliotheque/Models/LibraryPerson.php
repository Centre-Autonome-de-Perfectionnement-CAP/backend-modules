<?php

namespace App\Modules\Bibliotheque\Models;

use Illuminate\Database\Eloquent\Model;

/** Auteur, maître de mémoire ou superviseur ; ses coordonnées ne sont jamais publiées. */
class LibraryPerson extends Model
{
    public const ROLES = ['auteur' => 'Auteur', 'maitre' => 'Maître de mémoire', 'superviseur' => 'Superviseur'];

    protected $table = 'library_people';

    protected $guarded = ['id'];

    public function reachable(): bool
    {
        return (bool) ($this->email || $this->phone);
    }
}
