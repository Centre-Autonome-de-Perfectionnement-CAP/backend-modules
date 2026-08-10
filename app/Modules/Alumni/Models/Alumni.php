<?php

namespace App\Modules\Alumni\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    protected $table = 'alumni';

    protected $fillable = [
        'ecole',
        'nom',
        'prenom',
        'civilite',
        'mail',
        'telephone',
        'situation_professionnelle',
        'autre_situation',
        'secteur_emploi',
        'secteur_professionnel',
        'type_emploi',
        'nom_entreprise',
        'annee_entree',
        'annee_sortie',
        'promotion',
        'formation',
        'autre_formation',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeCap($query)
    {
        return $query->where('ecole', 'CAP');
    }

    public function scopeEpac($query)
    {
        return $query->where('ecole', 'EPAC');
    }

    // ── Accesseurs ────────────────────────────────────────────────────────────

    /**
     * Retourne la valeur effective de la situation professionnelle.
     */
    public function getSituationEffectiveAttribute(): string
    {
        return $this->situation_professionnelle === 'Autre'
            ? ($this->autre_situation ?? 'Autre')
            : $this->situation_professionnelle;
    }

    /**
     * Retourne la valeur effective de la formation.
     */
    public function getFormationEffectiveAttribute(): string
    {
        return $this->formation === 'Autre'
            ? ($this->autre_formation ?? 'Autre')
            : $this->formation;
    }
}
