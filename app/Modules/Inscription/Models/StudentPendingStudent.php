<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class StudentPendingStudent extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'student_pending_student';

    protected $fillable = [
        'student_id',
        'pending_student_id',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function pendingStudent()
    {
        return $this->belongsTo(PendingStudent::class);
    }
}
