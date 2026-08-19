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

    protected $appends = [
        'receipt_url',
        'student_full_name',
    ];

    /**
     * Relation avec le modèle Student via le matricule (student_id_number)
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id_number', 'student_id_number');
    }

    /**
     * Relation avec LegacyStudent pour les anciens étudiants (< 2023)
     */
    public function legacyStudent()
    {
        return $this->belongsTo(\App\Modules\LegacyStudent\Models\LegacyStudent::class, 'student_id_number', 'matricule');
    }

    /**
     * Relation avec StudentPendingStudent
     */
    public function studentPendingStudent()
    {
        return $this->belongsTo(StudentPendingStudent::class, 'student_pending_student_id');
    }

    /**
     * Nom complet de l'étudiant (qu'il soit moderne ou ancien étudiant)
     */
    public function getStudentFullNameAttribute(): ?string
    {
        if ($this->legacyStudent) {
            return trim("{$this->legacyStudent->first_name} {$this->legacyStudent->last_name}");
        }

        if ($this->studentPendingStudent?->pendingStudent?->personalInformation) {
            $pi = $this->studentPendingStudent->pendingStudent->personalInformation;
            return trim("{$pi->first_names} {$pi->last_name}");
        }

        if ($this->relationLoaded('student') && $this->student) {
            $studentPending = $this->student->pendingStudents()->with('personalInformation')->first();
            $pi = $studentPending?->personalInformation;
            if ($pi) {
                return trim("{$pi->first_names} {$pi->last_name}");
            }
        }

        // Fallback: rechercher dans legacy_students si non chargé
        $legacy = \App\Modules\LegacyStudent\Models\LegacyStudent::where('matricule', $this->student_id_number)->first();
        if ($legacy) {
            return trim("{$legacy->first_name} {$legacy->last_name}");
        }

        return null;
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
     * Scope pour filtrer par statut
     */
    public function scopeByStatus($query, string $status)
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
