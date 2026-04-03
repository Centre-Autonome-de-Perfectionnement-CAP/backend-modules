<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="Modèle représentant un utilisateur",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="last_name", type="string", description="Nom de famille"),
 *     @OA\Property(property="first_name", type="string", description="Prénom"),
 *     @OA\Property(property="email", type="string", format="email", description="Adresse email"),
 *     @OA\Property(property="phone", type="string", description="Numéro de téléphone"),
 *     @OA\Property(property="rib_number", type="string", description="Numéro RIB"),
 *     @OA\Property(property="rib", type="string", description="RIB"),
 *     @OA\Property(property="photo", type="string", description="Photo de profil"),
 *     @OA\Property(property="ifu_number", type="string", description="Numéro IFU"),
 *     @OA\Property(property="ifu", type="string", description="IFU"),
 *     @OA\Property(property="bank", type="string", description="Banque"),
 *     @OA\Property(property="uuid", type="string", format="uuid", description="UUID unique"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, description="Date de vérification email"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour")
 * )
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'last_name',
        'first_name',
        'email',
        'password',
        'phone',
        'rib_number',
        'rib',
        'photo',
        'ifu_number',
        'ifu',
        'bank',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function roles()
    {
        return $this->belongsToMany(\App\Modules\Stockage\Models\Role::class, 'role_user');
    }
}
