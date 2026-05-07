<?php

namespace App\Modules\EmploiDuTemps\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory, HasUuid;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\RoomFactory::new();
    }

    protected $fillable = [
        'building_id',
        'name',
        'code',
        'capacity',
        'room_type',
        'equipment',
        'is_available',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'equipment' => 'array',
        'is_available' => 'boolean',
    ];

    /**
     * Types de salles
     */
    const TYPE_AMPHITHEATER = 'amphitheater';
    const TYPE_CLASSROOM = 'classroom';
    const TYPE_LAB = 'lab';
    const TYPE_COMPUTER_LAB = 'computer_lab';
    const TYPE_CONFERENCE = 'conference';

    /**
     * Relation avec le bâtiment
     */
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Relation avec les cours planifiés
     */
    public function scheduledCourses()
    {
        return $this->hasMany(ScheduledCourse::class);
    }

    /**
     * Vérifier si la salle a une capacité suffisante
     */
    public function hasCapacityFor(int $studentCount): bool
    {
        return $this->capacity >= $studentCount;
    }
}
