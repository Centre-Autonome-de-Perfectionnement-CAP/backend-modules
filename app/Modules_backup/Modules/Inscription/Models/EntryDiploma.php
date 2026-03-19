<?php

namespace App\Modules\Inscription\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class EntryDiploma extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['name', 'abbreviation', 'entry_level'];

}
