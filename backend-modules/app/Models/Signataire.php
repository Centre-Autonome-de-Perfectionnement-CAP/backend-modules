<?php

namespace App\Models;

use App\Modules\Stockage\Models\Role;
use Illuminate\Database\Eloquent\Model;

class Signataire extends Model
{
    protected $fillable = [
        'nom',
        'role_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public static function getByRole(string $roleName)
    {
        return self::whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        })->first();
    }
}
