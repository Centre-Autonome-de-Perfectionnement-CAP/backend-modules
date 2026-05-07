<?php

namespace App\Modules\Notes\Services;

class GradeCalculationService
{
    /**
     * Retourne une pondération équilibrée pour n notes
     * Exemple: 3 notes → [33, 33, 34] (total = 100)
     * 
     * @param int $count
     * @return array
     */
    public function getBalancedPonderation(int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $base = floor(100 / $count);
        $pond = array_fill(0, $count, $base);
        $reste = 100 - array_sum($pond);

        // Ajoute le reste au dernier élément pour total 100
        if ($reste > 0) {
            $pond[$count - 1] += $reste;
        }

        return $pond;
    }

    /**
     * Calcule la moyenne pondérée à partir des notes et de la pondération
     * 
     * @param array $notes [12, 14, 16]
     * @param array $pond [40, 30, 30]
     * @return float|null
     */
    public function calculateMoyennePonderee(array $notes, array $pond): ?float
    {
        if (count($notes) !== count($pond) || count($notes) === 0) {
            return null;
        }

        $total = 0;
        foreach ($notes as $i => $note) {
            $total += $note * ($pond[$i] / 100);
        }

        return round($total, 2);
    }

    /**
     * Vérifie si un étudiant est validé
     * 
     * @param float|null $moyenne
     * @param float|null $moyenneRattrapage
     * @param float $minimalAverage
     * @return bool
     */
    public function isValidated(?float $moyenne, ?float $moyenneRattrapage, float $minimalAverage = 10): bool
    {
        $finalAverage = $moyenneRattrapage ?? $moyenne;
        return $finalAverage >= $minimalAverage;
    }

    /**
     * Vérifie si un étudiant peut rattraper (7 <= moyenne < 10)
     * 
     * @param float|null $moyenne
     * @param float $minimalAverage
     * @return bool
     */
    public function canRetake(?float $moyenne, float $minimalAverage = 10): bool
    {
        if ($moyenne === null) {
            return false;
        }
        return $moyenne < $minimalAverage && $moyenne >= 7;
    }

    /**
     * Vérifie si un étudiant doit reprendre (moyenne < 7)
     * 
     * @param float|null $moyenne
     * @return bool
     */
    public function mustRetake(?float $moyenne): bool
    {
        if ($moyenne === null) {
            return false;
        }
        return $moyenne < 7;
    }
}
