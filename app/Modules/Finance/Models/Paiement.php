<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Inscription\Models\Student;
use App\Modules\Stockage\Models\File;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'matricule',
        'montant',
        'reference',
        'numero_compte',
        'date_versement',
        'quittance',
        'motif',
        'observation',
        'email',
        'statut',
        'contact',
    ];

    protected $casts = [
        'montant' => 'float',
        'date_versement' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'statut' => 'attente',
    ];

    /**
     * Relation avec le modèle Student via le matricule (student_id_number)
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'matricule', 'student_id_number');
    }

    /**
     * Relation avec le fichier de quittance
     */
    public function quittanceFile()
    {
        return $this->belongsTo(File::class, 'quittance', 'id');
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeByStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope pour les paiements en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'attente');
    }

    /**
     * Scope pour les paiements acceptés
     */
    public function scopeAccepte($query)
    {
        return $query->where('statut', 'accepte');
    }

    /**
     * Scope pour les paiements rejetés
     */
    public function scopeRejete($query)
    {
        return $query->where('statut', 'rejete');
    }
}
