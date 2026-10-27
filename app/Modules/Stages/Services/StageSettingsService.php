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
        'final_correction_days'    => 90,             // version corrigée après soutenance : N jours après la fin du stage
        // ── Lot 5 : attestations ─────────────────────────────────────────
        'seuil_assiduite'          => 80,             // % minimal de présence pour l'attestation
        'attestation_structure'    => 'de la Cellule Informatique de la Division Formation Continue et Perfectionnement du CAP',
        // ── Documents PDF (attestation, note de service) ─────────────────
        'numero_suffixe'           => 'CAP/EPAC/UAC',   // attestations : « N° 001-26/CAP/EPAC/UAC »
        'pdf_ville'                => 'Abomey-Calavi',
        'signataire_titre'         => 'Le Chef CAP,',
        'signataire_nom'           => 'Professeur Fidèle Paul TCHOBO',
        // Notes de service (initiale et rectificative) : signature numérique du RD / FC-P
        'note_signataire_titre'    => 'Le Responsable Division Formation Continue et Perfectionnement (RD / FC-P)',
        'note_signataire_nom'      => '',
        'note_numero_suffixe'      => 'STG/CAP/EPAC/UAC', // « N° 001-26/STG/CAP/EPAC/UAC » : série propre aux notes de stage
        'note_signature'           => null,   // image : voir NoteSignatureService
        'pdf_pied_de_page'         => '01 B.P. 2009 COTONOU - Tél. : +229 01 99 54 62 67 / 01 91 94 73 67 - www.cap-epac.bj',
        'reglement_version'        => 3,
        'reglement_text'           => self::REGLEMENT,
    ];

    /**
     * Règlement par défaut. Les champs entre accolades sont remplacés à
     * l'affichage par les valeurs du stagiaire et des réglages : {horaires},
     * {tolerance}, {delai_permission}, {rapports}, {seuil}.
     */
    /**
     * Mise en forme : **texte** en gras, !!texte!! en gras rouge (à ne pas
     * manquer). Le reste est du texte simple.
     */
    private const REGLEMENT = "Bienvenue au CAP-EPAC. En acceptant ce règlement, vous vous engagez à :\n\n"
        . "1. Respecter les horaires de votre stage : du lundi au vendredi, {horaires}. Au-delà de {tolerance} minutes après l'heure prévue, vous êtes en retard.\n"
        . "2. Pointer **vous-même** chaque jour ouvré depuis votre espace stagiaire : à l'arrivée, au départ en pause, au retour de pause et à la sortie.\n"
        . "3. Pointer **uniquement sur votre lieu de stage**, avec **votre téléphone de stage**.\n"
        . "4. Signaler toute absence ou tout retard par une demande de permission dans votre espace, au plus tard {delai_permission} jours ouvrés après.\n"
        . "5. Déposer dans votre espace les rapports demandés ({rapports}), dans les délais, puis votre rapport de stage final avant la fin du stage.\n"
        . "6. Respecter le personnel, le matériel et la confidentialité des informations auxquelles vous avez accès.\n"
        . "7. Adopter une tenue et un comportement corrects.\n\n"
        . "!!VOTRE TÉLÉPHONE DE STAGE!!\n"
        . "Le téléphone avec lequel vous confirmez votre présence le premier jour devient **votre téléphone de stage**. C'est **le seul** qui ouvre votre espace et qui pointe, avec votre empreinte.\n"
        . "• Un téléphone ne sert qu'à **un seul stagiaire**.\n"
        . "• !!Interdit!! : vous connecter au compte d'un autre stagiaire, pointer pour lui, ou prêter votre téléphone, votre mot de passe ou votre empreinte.\n"
        . "• **Toute connexion depuis un autre téléphone est refusée et enregistrée dans votre dossier**, que voit votre superviseur.\n"
        . "• Nouveau téléphone (perte, panne) ? Demandez à votre superviseur de réinitialiser votre téléphone de stage, puis enregistrez le nouveau.\n\n"
        . "Votre présence est vérifiée par votre empreinte (gérée par votre téléphone : aucune donnée biométrique n'est envoyée) et par votre position au moment du pointage (seule la distance au lieu de stage est conservée).\n\n"
        . "L'attestation de stage est délivrée si votre assiduité atteint **au moins {seuil} %** et si votre rapport final est validé.\n\n"
        . "!!Toute fraude sur la présence entraîne la fin du stage et l'absence d'attestation.!! Le non-respect de ce règlement peut aussi y conduire.";

    /** Réglages modifiables depuis le progiciel. */
    public const EDITABLE = [
        'requests_open', 'closed_message', 'default_rejection_reason',
        'default_service', 'default_lieu', 'default_horaires',
        'lieu_position', 'lieu_rayon', 'reglement_text', 'permission_delay_days',
        'report_frequency', 'report_grace_days', 'final_report_open_days', 'final_correction_days',
        'seuil_assiduite', 'attestation_structure', 'numero_suffixe', 'pdf_ville',
        'signataire_titre', 'signataire_nom', 'pdf_pied_de_page',
        'note_signataire_titre', 'note_signataire_nom', 'note_numero_suffixe',
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

    /** Règlement à afficher, champs remplacés (horaires du stagiaire s'il est connu). */
    public function reglementFor(?\App\Modules\Stages\Models\StageRequest $req = null): string
    {
        $h = $req?->horaires ?: $this->get('default_horaires');
        $f = fn (?string $v) => str_replace(':', 'h', (string) $v);
        $freq = ['journalier' => 'chaque jour', 'hebdomadaire' => 'chaque semaine', 'mensuel' => 'chaque mois'][$this->get('report_frequency')] ?? 'chaque semaine';

        return strtr((string) $this->get('reglement_text'), [
            '{horaires}'         => "de {$f($h['arrivee'] ?? '08:00')} à {$f($h['pause_debut'] ?? '13:00')} et de {$f($h['pause_fin'] ?? '15:00')} à {$f($h['sortie'] ?? '18:00')}",
            '{tolerance}'        => (string) AttendanceService::TOLERANCE_MIN,
            '{delai_permission}' => (string) $this->get('permission_delay_days'),
            '{rapports}'         => $freq,
            '{seuil}'            => (string) $this->get('seuil_assiduite'),
        ]);
    }

    public function update(array $values, ?int $userId): array
    {
        // Règlement modifié : nouvelle version, que chaque stagiaire devra accepter.
        if (array_key_exists('reglement_text', $values) && trim((string) $values['reglement_text']) !== trim((string) $this->get('reglement_text'))) {
            $values['reglement_version'] = (int) $this->get('reglement_version') + 1;
        }

        foreach (array_intersect_key($values, array_flip([...self::EDITABLE, 'reglement_version'])) as $key => $value) {
            $this->write($key, $value, $userId);
        }
        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }

    /** Réglage géré par un service dédié (ex. image de la signature). */
    public function set(string $key, mixed $value, ?int $userId): void
    {
        if (!array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException("Réglage inconnu : {$key}");
        }
        $this->write($key, $value, $userId);
        Cache::forget(self::CACHE_KEY);
    }

    private function write(string $key, mixed $value, ?int $userId): void
    {
        DB::table('stage_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_by' => $userId, 'updated_at' => now()],
        );
    }
}
