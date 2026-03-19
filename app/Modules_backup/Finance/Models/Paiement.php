<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Traits\HasUuid;

class Paiement extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $table = 'payments';

    protected $fillable = [
        'student_id_number',
        'student_pending_student_id',
        'amount',
        'reference',
        'account_number',
        'payment_date',
        'receipt_path',
        'purpose',
        'observation',
        'email',
        'status',
        'contact',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Relation avec le modèle Student via le matricule (student_id_number)
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id_number', 'student_id_number');
    }

    /**
     * Relation avec StudentPendingStudent
     */
    public function studentPendingStudent()
    {
        return $this->belongsTo(StudentPendingStudent::class, 'student_pending_student_id');
    }

    /**
     * Obtenir l'URL de téléchargement de la quittance
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if ($this->receipt_path) {
            return route('api.finance.paiements.download', ['reference' => $this->reference]);
        }
        return null;
    }

    /**
     * Scope pour filtrer par status
     */
    public function scopeBystatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour les paiements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les paiements approuvés
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope pour les paiements rejetés
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
