<?php

namespace App\Models;

use App\Modules\Inscription\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $matricule
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property int $enrollment_year
 * @property string $status pending|validated|rejected
 * @property string|null $rejection_reason
 * @property string|null $notes_admin
 * @property int|null $validated_by
 * @property \Illuminate\Support\Carbon|null $validated_at
 */
class LegacyStudent extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'matricule',
        'first_name',
        'last_name',
        'email',
        'phone',
        'enrollment_year',
        'status',
        'rejection_reason',
        'notes_admin',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'enrollment_year' => 'integer',
        'validated_at' => 'datetime',
    ];

    // Pratique pour la sérialisation JSON côté API admin (Dev 2) sans
    // avoir à les rappeler explicitement dans chaque contrôleur.
    protected $appends = [
        'full_name',
        'status_badge',
    ];

    /*
    |--------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------
    */

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'legacy_student_departments')
            ->using(LegacyStudentDepartment::class)
            ->withPivot('cycle_id')
            ->withTimestamps();
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /*
    |--------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------
    */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VALIDATED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Recherche par mot-clé unique sur matricule, nom, prénom et email.
     *
     * Usage : LegacyStudent::search('koudjo')->paginate();
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('matricule', 'like', "%{$keyword}%")
                ->orWhere('first_name', 'like', "%{$keyword}%")
                ->orWhere('last_name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%");
        });
    }

    /**
     * Filtre par filière (department_id) via la relation pivot.
     * Ajouté en plus du contrat initial car les KPIs de Dev 2 filtrent
     * aussi "par filière" — pratique à avoir tout de suite.
     */
    public function scopeInDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->whereHas('departments', function (Builder $q) use ($departmentId) {
            $q->where('departments.id', $departmentId);
        });
    }

    /*
    |--------------------------------------------------------------------
    | Accesseurs
    |--------------------------------------------------------------------
    */

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    /**
     * Badge structuré { label, value, color } pour affichage direct côté UI,
     * sans dupliquer le mapping statut -> couleur dans chaque contrôleur/vue.
     */
    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                self::STATUS_VALIDATED => [
                    'label' => 'Validé',
                    'value' => self::STATUS_VALIDATED,
                    'color' => 'green',
                ],
                self::STATUS_REJECTED => [
                    'label' => 'Rejeté',
                    'value' => self::STATUS_REJECTED,
                    'color' => 'red',
                ],
                default => [
                    'label' => 'En attente',
                    'value' => self::STATUS_PENDING,
                    'color' => 'orange',
                ],
            },
        );
    }
}
