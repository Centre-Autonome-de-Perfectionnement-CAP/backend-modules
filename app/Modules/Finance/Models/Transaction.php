<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Transaction Model - Validated Receipts
 * 
 * Stores validated payment receipts (quittances validées).
 * Once a payment is approved, it becomes a transaction.
 */
class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'reference',
        'pending_student_id',
        'academic_year_id',
        'amount_paid',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation: Pending Student
     */
    public function pendingStudent()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\PendingStudent::class);
    }

    /**
     * Relation: Academic Year
     */
    public function academicYear()
    {
        return $this->belongsTo(\App\Modules\Inscription\Models\AcademicYear::class);
    }

    /**
     * Get the student through pending_student
     */
    public function student()
    {
        return $this->hasOneThrough(
            \App\Modules\Inscription\Models\Student::class,
            \App\Modules\Inscription\Models\PendingStudent::class,
            'id', // Foreign key on pending_students
            'id', // Foreign key on students
            'pending_student_id', // Local key on transactions
            'student_id' // Local key on pending_students
        );
    }

    /**
     * Scope: Filter by academic year
     */
    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope: Filter by pending student
     */
    public function scopeForPendingStudent($query, $pendingStudentId)
    {
        return $query->where('pending_student_id', $pendingStudentId);
    }

    /**
     * Get total amount paid by a pending student
     */
    public static function getTotalPaidByPendingStudent($pendingStudentId, $academicYearId = null)
    {
        $query = static::where('pending_student_id', $pendingStudentId);
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        return $query->sum('amount_paid');
    }
}
