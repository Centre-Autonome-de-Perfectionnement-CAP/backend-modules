<?php

namespace App\Modules\Attestation\Services;

use App\Modules\Inscription\Models\{AcademicPath, StudentPendingStudent};
use App\Modules\Finance\Models\Transaction;
use App\Modules\Attestation\DTOs\StudentEligibilityDTO;

/**
 * CORRECTIF (v2) — Service extrait de AttestationController réel
 *
 * Le code source réel m'a permis de vérifier exactement la logique
 * d'éligibilité (commentaires originaux conservés ci-dessous).
 * Aucune divergence trouvée avec la v1 livrée — confirmé à l'identique.
 *
 * Logiques d'éligibilité (commentaire original du contrôleur) :
 *   attestation_passage     = isApproved && hasPass && studyLevel < yearsCount
 *   attestation_definitive  = isApproved && hasPass && studyLevel >= yearsCount
 *   attestation_inscription = isApproved
 */
class EligibilityService
{
    // ══════════════════════════════════════════════════════════════════════════
    // ÉLIGIBILITÉ ATTESTATIONS (getStatus)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @return array{attestation_passage: bool, attestation_definitive: bool, attestation_inscription: bool}
     */
    public function getAttestationEligibility(StudentPendingStudent $link): array
    {
        $pending  = $link->pendingStudent;
        $path     = AcademicPath::where('student_pending_student_id', $link->id)->latest('id')->first();
        $cycle    = $pending->department?->cycle;
        $yearsCount = (int) ($cycle?->years_count ?? 0);

        $rawLevel   = $path?->study_level ?? 0;
        $studyLevel = is_numeric($rawLevel)
            ? (int) $rawLevel
            : (int) preg_replace('/^[A-Za-z]+/', '', (string) $rawLevel);

        $hasPass = $path
            && $path->year_decision === 'pass'
            && !empty($path->deliberation_date);

        $isApproved = $pending->status === 'approved';

        return [
            'attestation_passage'     => $isApproved && $hasPass && $yearsCount > 0 && $studyLevel < $yearsCount,
            'attestation_definitive'  => $isApproved && $hasPass && $yearsCount > 0 && $studyLevel >= $yearsCount,
            'attestation_inscription' => $isApproved,
        ];
    }

    /**
     * Éligibilité réelle d'un étudiant à chaque attestation, sur TOUTES ses
     * inscriptions approuvées et TOUS ses parcours (et non plus seulement la
     * dernière inscription / le dernier parcours) :
     *
     *   - attestation_inscription : dernière inscription approuvée ;
     *   - attestation_passage     : un parcours « admis » (pass) délibéré, avec
     *                               niveau < durée du cycle ;
     *   - attestation_definitive  : un parcours « admis » délibéré, avec
     *                               niveau >= durée du cycle ;
     *   - les cycles préparatoires (« prépa ») n'ouvrent ni passage ni
     *     définitive.
     *
     * CORRECTIF (17/10/2026) : l'ancienne règle ne regardait que le DERNIER
     * parcours de la DERNIÈRE inscription. Un étudiant admis en L1 puis inscrit
     * en L2 (décision de L2 pas encore rendue) était déclaré non éligible à
     * l'attestation de passage de L1.
     *
     * Pour chaque type éligible, renvoie l'inscription (et l'année) à laquelle
     * rattacher la demande — la plus récente qui ouvre ce droit.
     *
     * @return array<string, array{link: StudentPendingStudent, academic_year_id: ?int}|null>
     */
    public function attestationTargets(int $studentId): array
    {
        $targets = [
            'attestation_passage'     => null,
            'attestation_definitive'  => null,
            'attestation_inscription' => null,
        ];

        $links = StudentPendingStudent::where('student_id', $studentId)
            ->whereHas('pendingStudent', fn ($q) => $q->where('status', 'approved'))
            ->with(['pendingStudent.department.cycle', 'pendingStudent.academicYear', 'pendingStudent.personalInformation'])
            ->orderByDesc('id')
            ->get();

        if ($links->isEmpty()) {
            return $targets;
        }

        $latest = $links->first();
        $targets['attestation_inscription'] = [
            'link'             => $latest,
            'academic_year_id' => $latest->pendingStudent->academic_year_id,
        ];

        $paths = AcademicPath::whereIn('student_pending_student_id', $links->pluck('id'))
            ->where('year_decision', 'pass')
            ->whereNotNull('deliberation_date')
            ->whereNotNull('study_level')
            ->where('study_level', '!=', '')
            ->orderByDesc('deliberation_date')
            ->orderByDesc('id')
            ->get();

        foreach ($paths as $path) {
            $link       = $links->firstWhere('id', $path->student_pending_student_id);
            $cycle      = $link?->pendingStudent?->department?->cycle;
            $yearsCount = (int) ($cycle?->years_count ?? 0);

            if (!$link || $yearsCount <= 0 || $this->isPrepaCycle($cycle?->name)) {
                continue;
            }

            $type = $this->studyLevel($path->study_level) < $yearsCount
                ? 'attestation_passage'
                : 'attestation_definitive';

            $targets[$type] ??= [
                'link'             => $link,
                'academic_year_id' => $path->academic_year_id ?? $link->pendingStudent->academic_year_id,
            ];
        }

        return $targets;
    }

    /** "L2", "2", "2ème année" → 2 */
    private function studyLevel(mixed $raw): int
    {
        return is_numeric($raw)
            ? (int) $raw
            : (int) preg_replace('/^[A-Za-z]+/', '', trim((string) $raw));
    }

    private function isPrepaCycle(?string $name): bool
    {
        $n = mb_strtolower((string) $name);
        return str_contains($n, 'prepa') || str_contains($n, 'prépa');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ÉLIGIBILITÉ BULLETIN (getBulletinStatus)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Condition : parcours académique renseigné (study_level non null).
     */
    public function isBulletinEligible(StudentPendingStudent $link): bool
    {
        return AcademicPath::where('student_pending_student_id', $link->id)
            ->whereNotNull('study_level')
            ->exists();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ÉLIGIBILITÉ PAR TYPE — checkAvailability
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @param  string  $type  'inscription' | 'passage' | 'definitive'
     */
    public function checkAvailabilityForType(StudentPendingStudent $link, string $type, ?\App\Modules\Inscription\Models\AcademicYear $year = null): StudentEligibilityDTO
    {
        $ps = $link->pendingStudent;

        if ($type === 'inscription') {
            if ($ps->status !== 'approved') {
                return StudentEligibilityDTO::unavailable("Votre inscription n'est pas encore approuvée.");
            }

            $hasTx = Transaction::where('pending_student_id', $ps->id)
                ->where('academic_year_id', $year?->id)
                ->exists();

            return $hasTx
                ? StudentEligibilityDTO::available()
                : StudentEligibilityDTO::unavailable('Aucun paiement validé trouvé pour cette année académique.');
        }

        $path = AcademicPath::where('student_pending_student_id', $link->id)
            ->where('academic_year_id', $year?->id)
            ->first();

        if (!$path) {
            return StudentEligibilityDTO::unavailable('Aucun parcours académique trouvé pour cette année.');
        }
        if ($path->year_decision !== 'pass') {
            return StudentEligibilityDTO::unavailable("La décision de jury n'est pas encore disponible ou n'est pas favorable.");
        }
        if (empty($path->deliberation_date)) {
            return StudentEligibilityDTO::unavailable("La date de délibération n'est pas encore renseignée.");
        }

        $yearsCount = (int) ($ps->department?->cycle?->years_count ?? 0);
        $studyLevel = (int) $path->study_level;

        if (!$yearsCount) {
            return StudentEligibilityDTO::unavailable('Impossible de déterminer la durée du cycle.');
        }

        if ($type === 'passage') {
            return $studyLevel >= $yearsCount
                ? StudentEligibilityDTO::unavailable("Vous êtes en dernière année. Une attestation de passage n'est pas applicable.")
                : StudentEligibilityDTO::available();
        }

        if ($type === 'definitive') {
            return $studyLevel < $yearsCount
                ? StudentEligibilityDTO::unavailable("Vous n'êtes pas encore en dernière année de votre cycle.")
                : StudentEligibilityDTO::available();
        }

        return StudentEligibilityDTO::unavailable('Type non reconnu.');
    }
}
