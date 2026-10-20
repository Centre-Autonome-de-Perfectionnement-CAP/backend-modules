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
        // ── Lot 3 : espace stagiaire et pointage ─────────────────────────
        'lieu_position'            => null,   // {lat, lng} : relevée sur place
        'lieu_rayon'               => 50,     // mètres
        'activation_delay_hours'   => 2,      // lien envoyé 2 h après l'heure d'arrivée du 1er jour
        'desistement_days'         => 3,      // sans activation : désistement
        'permission_delay_days'    => 3,      // demande de permission après coup (jours ouvrés)
        // ── Lot 4 : rapports ─────────────────────────────────────────────
        'report_frequency'         => 'hebdomadaire', // journalier | hebdomadaire | mensuel
        'report_grace_days'        => 2,              // jours ouvrés après la fin de la période
        'final_report_open_days'   => 14,             // dépôt du rapport final ouvert N jours avant la fin
        // ── Lot 5 : attestations ─────────────────────────────────────────
        'seuil_assiduite'          => 80,             // % minimal de présence pour l'attestation
        'attestation_prefix'       => 'CAP-EPAC/STG', // numéro proposé : PREFIXE/AAAA/NNN
        'reglement_version'        => 1,
        'reglement_text'           => self::REGLEMENT,
    ];

    private const REGLEMENT = "Bienvenue au CAP-EPAC. En acceptant ce règlement, vous vous engagez à :\n\n"
        . "1. Respecter les horaires de votre stage et pointer vous-même, chaque jour ouvré : à l'arrivée, au départ en pause, au retour de pause et à la sortie.\n"
        . "2. Pointer uniquement sur votre lieu de stage, avec votre propre téléphone. Le pointage pour une autre personne est interdit.\n"
        . "3. Signaler toute absence ou tout retard par une demande de permission dans votre espace, au plus tard 3 jours ouvrés après.\n"
        . "4. Déposer vos rapports dans les délais demandés.\n"
        . "5. Respecter le personnel, le matériel et la confidentialité des informations auxquelles vous avez accès.\n"
        . "6. Adopter une tenue et un comportement corrects.\n\n"
        . "Votre présence est vérifiée par votre empreinte (gérée par votre téléphone : aucune donnée biométrique n'est envoyée) et par votre position au moment du pointage (seule la distance au lieu de stage est conservée).\n\n"
        . "Le non-respect de ce règlement peut entraîner la fin du stage et l'absence d'attestation.";

    /** Réglages modifiables depuis le progiciel. */
    public const EDITABLE = [
        'requests_open', 'closed_message', 'default_rejection_reason',
        'default_service', 'default_lieu', 'default_horaires',
        'lieu_position', 'lieu_rayon', 'reglement_text', 'permission_delay_days',
        'report_frequency', 'report_grace_days', 'final_report_open_days',
        'seuil_assiduite', 'attestation_prefix',
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
        // Règlement modifié : nouvelle version, que chaque stagiaire devra accepter.
        if (array_key_exists('reglement_text', $values) && trim((string) $values['reglement_text']) !== trim((string) $this->get('reglement_text'))) {
            $values['reglement_version'] = (int) $this->get('reglement_version') + 1;
        }

        foreach (array_intersect_key($values, array_flip([...self::EDITABLE, 'reglement_version'])) as $key => $value) {
            DB::table('stage_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_by' => $userId, 'updated_at' => now()],
            );
        }
        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }
}
