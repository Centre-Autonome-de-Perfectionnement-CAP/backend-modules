<?php

namespace App\Modules\Stages\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Réglages du module Stages, avec des valeurs par défaut prêtes à l'emploi :
 * le responsable de division n'a rien à configurer pour démarrer.
 */
class StageSettingsService
{
    private const CACHE_KEY = 'stages:settings';

    public const DEFAULTS = [
        'requests_open'            => true,
        'closed_message'           => "Les demandes de stage sont momentanément fermées. Merci de revenir un peu plus tard.",
        'default_rejection_reason' => "Nous avons atteint notre capacité d'accueil de stagiaires pour le moment et ne pouvons donc pas donner suite à votre demande. Merci de l'intérêt que vous portez au CAP-EPAC.",
        'default_service'          => 'Service Informatique',
        'default_lieu'             => 'CAP-EPAC',
        'default_horaires'         => ['arrivee' => '08:00', 'pause_debut' => '13:00', 'pause_fin' => '15:00', 'sortie' => '18:00'],
        'proposal_reminder_days'   => 3,
        'proposal_expiry_days'     => 7,
    ];

    /** Réglages modifiables depuis le progiciel. */
    public const EDITABLE = [
        'requests_open', 'closed_message', 'default_rejection_reason',
        'default_service', 'default_lieu', 'default_horaires',
    ];

    public function all(): array
    {
        $stored = Cache::remember(self::CACHE_KEY, 300, function () {
            return DB::table('stage_settings')->pluck('value', 'key')
                ->map(fn ($v) => json_decode($v, true))
                ->all();
        });

        return array_merge(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    public function update(array $values, ?int $userId): array
    {
        foreach (array_intersect_key($values, array_flip(self::EDITABLE)) as $key => $value) {
            DB::table('stage_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_by' => $userId, 'updated_at' => now()],
            );
        }
        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }
}
