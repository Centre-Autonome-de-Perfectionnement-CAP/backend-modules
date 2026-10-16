<?php

namespace App\Modules\Demandes\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Identité du demandeur d'une demande (document_requests).
 *
 * Une demande appartient SOIT à un étudiant inscrit (student_pending_student
 * → pending_students → personal_information), SOIT à un ancien étudiant
 * déclaré (legacy_students). Toutes les lectures passent par ces jointures
 * GAUCHES + COALESCE pour couvrir les deux cas avec une seule requête.
 *
 * Alias fixes posés par join() : sps, ps, pi, ls (legacy_students),
 * dept (filière de l'inscrit), ldept (filière de l'ancien étudiant).
 */
final class Requester
{
    public const LAST_NAME   = 'COALESCE(pi.last_name, ls.last_name)';
    public const FIRST_NAMES = 'COALESCE(pi.first_names, ls.first_name)';
    public const BIRTH_DATE  = 'COALESCE(pi.birth_date, ls.date_of_birth)';
    public const DEPARTMENT  = 'COALESCE(dept.name, ldept.name)';

    /** @param string $dr alias de document_requests dans la requête */
    public static function join(Builder $query, string $dr = 'dr', bool $withDepartments = true): Builder
    {
        $query->leftJoin('student_pending_student as sps', "{$dr}.student_pending_student_id", '=', 'sps.id')
            ->leftJoin('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->leftJoin('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->leftJoin('legacy_students as ls', "{$dr}.legacy_student_id", '=', 'ls.id');

        if ($withDepartments) {
            $query->leftJoin('departments as dept', 'ps.department_id', '=', 'dept.id')
                ->leftJoin('departments as ldept', 'ls.department_id', '=', 'ldept.id');
        }

        return $query;
    }

    /** Matricule : étudiant inscrit, sinon ancien étudiant. */
    public static function matriculeSql(string $dr = 'dr'): string
    {
        return "COALESCE((SELECT s.student_id_number FROM students s
                 JOIN student_pending_student sps2 ON sps2.student_id = s.id
                 WHERE sps2.id = {$dr}.student_pending_student_id LIMIT 1), ls.matricule)";
    }

    /** Colonnes d'identité standard (nom, prénoms, matricule, ancien étudiant). */
    public static function identityColumns(string $dr = 'dr'): array
    {
        return [
            DB::raw(self::LAST_NAME . ' as last_name'),
            DB::raw(self::FIRST_NAMES . ' as first_names'),
            DB::raw(self::matriculeSql($dr) . ' as matricule'),
            DB::raw("({$dr}.legacy_student_id IS NOT NULL) as is_legacy"),
            'ls.status as legacy_declaration_status',
        ];
    }
}
