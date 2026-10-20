<?php

namespace App\Modules\Stages\Support;

use App\Models\User;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Qui gère les stages : l'administrateur, et le Responsable de division
 * qui ne s'occupe pas des licences (type « formation_continue »).
 * Les rôles et leur nommage existants ne sont pas modifiés : on réutilise
 * le rôle `responsable-division` (et son alias historique) filtré par type.
 */
final class StageAccess
{
    public const DIVISION_TYPE = 'formation_continue';

    public static function canManage(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = WorkflowConstants::canonicalRole($user->roles->first()?->slug);

        if ($role === 'admin') {
            return true;
        }

        return $role === 'responsable-division'
            && ($user->chef_division_type ?? null) === self::DIVISION_TYPE;
    }

    /** Responsables de division destinataires des notifications Stages. */
    public static function managers(): Collection
    {
        return DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->whereIn('r.slug', WorkflowConstants::roleSlugVariants('responsable-division'))
            ->where('u.chef_division_type', self::DIVISION_TYPE)
            ->whereNull('u.deleted_at')
            ->select('u.id', 'u.email', 'u.phone', 'u.first_name', 'u.last_name')
            ->distinct()
            ->get();
    }
}
