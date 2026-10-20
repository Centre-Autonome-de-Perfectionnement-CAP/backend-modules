<?php

namespace App\Modules\Stages\Support;

use App\Models\User;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Qui gère les stages :
 *  - « responsables » : l'administrateur et le Responsable de division qui
 *    ne s'occupe pas des licences (type « formation_continue ») — tout, y
 *    compris réglages, invitations et groupe WhatsApp ;
 *  - superviseurs (rôle `superviseur`) : presque tout le reste ; le
 *    responsable est informé de chacune de leurs actions.
 * Les rôles et leur nommage existants ne sont pas modifiés : on réutilise
 * le rôle `responsable-division` (et son alias historique) filtré par type.
 */
final class StageAccess
{
    public const DIVISION_TYPE = 'formation_continue';
    public const SUPERVISOR_ROLE = 'superviseur';

    /** Accès au module (responsables + superviseurs). */
    public static function canManage(?User $user): bool
    {
        return self::isLead($user) || self::isSupervisor($user);
    }

    /** Administrateur ou Responsable de division (formation continue). */
    public static function isLead(?User $user): bool
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

    public static function isSupervisor(?User $user): bool
    {
        return $user !== null && $user->roles->first()?->slug === self::SUPERVISOR_ROLE;
    }

    /** Superviseurs inscrits (comptes actifs). */
    public static function supervisors(): Collection
    {
        return DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('r.slug', self::SUPERVISOR_ROLE)
            ->whereNull('u.deleted_at')
            ->select('u.id', 'u.email', 'u.phone', 'u.first_name', 'u.last_name', 'u.created_at')
            ->distinct()
            ->orderBy('u.last_name')
            ->get();
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
