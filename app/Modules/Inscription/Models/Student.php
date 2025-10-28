<?php

namespace App\Modules\Inscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\HasUuid;

class Student extends Authenticatable
{
    use HasFactory, HasUuid;

    protected $fillable = ['student_id_number', 'password'];
}
