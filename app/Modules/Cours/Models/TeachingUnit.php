<?php

namespace App\Modules\Cours\Models;

use App\Traits\HasUuid; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingUnit extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TeachingUnitFactory::new();
    }

    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Relation avec les ECUE (Course Elements)
     */
    public function courseElements()
    {
        return $this->hasMany(CourseElement::class);
    }
}
