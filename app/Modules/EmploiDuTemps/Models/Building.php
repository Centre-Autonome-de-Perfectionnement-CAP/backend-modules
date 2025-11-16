<?php

namespace App\Modules\EmploiDuTemps\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\BuildingFactory::new();
    }

    protected $fillable = [
        'name',
        'code',
        'address',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec les salles
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
