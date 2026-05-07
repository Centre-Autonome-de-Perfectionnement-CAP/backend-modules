<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exoneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'category',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];
}
