<?php

namespace App\Modules\Stages\Support;

/**
 * Vérification de la position au pointage. Seule la distance au lieu de
 * stage est conservée, jamais les coordonnées.
 */
final class Geo
{
    /** Au-delà, la position du téléphone est jugée trop imprécise. */
    public const MAX_ACCURACY_M = 150;
    /** Tolérance accordée au GPS (en intérieur), ajoutée au rayon. */
    private const ACCURACY_TOLERANCE_M = 30;

    public static function distanceM(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $r = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round(2 * $r * asin(min(1, sqrt($a))));
    }

    /**
     * @param array{lat: float, lng: float}|null $place
     * @return array{ok: bool, checked: bool, distance: ?int, message: ?string, reason: ?string}
     */
    public static function check(?array $place, int $radius, float $lat, float $lng, float $accuracy): array
    {
        if (!$place || !isset($place['lat'], $place['lng'])) {
            // Lieu pas encore relevé par le responsable : aucun pointage n'est
            // accepté (sinon on pourrait pointer depuis chez soi).
            return [
                'ok' => false, 'checked' => false, 'distance' => null, 'reason' => 'no_place',
                'message' => 'Le lieu de stage n\'est pas encore configuré : le pointage n\'est pas possible pour le moment. '
                    . 'Prévenez votre responsable de stage.',
            ];
        }

        if ($accuracy > self::MAX_ACCURACY_M) {
            return [
                'ok' => false, 'checked' => true, 'distance' => null, 'reason' => 'imprecise',
                'message' => 'Votre position est trop imprécise (±' . (int) $accuracy . ' m). Activez la localisation précise, '
                    . 'rapprochez-vous d\'une fenêtre ou sortez du bâtiment, puis réessayez.',
            ];
        }

        $distance = self::distanceM((float) $place['lat'], (float) $place['lng'], $lat, $lng);
        $margin   = (int) min($accuracy, self::ACCURACY_TOLERANCE_M);

        if ($distance - $margin > $radius) {
            return [
                'ok' => false, 'checked' => true, 'distance' => $distance, 'reason' => 'out_of_zone',
                'message' => "Vous êtes à environ {$distance} m du lieu de stage. Le pointage n'est possible qu'à moins de {$radius} m.",
            ];
        }

        return ['ok' => true, 'checked' => true, 'distance' => $distance, 'message' => null, 'reason' => null];
    }
}
