<?php

namespace App\Modules\Demandes;

/**
 * Source unique de vérité pour les constantes du workflow Demandes.
 */
final class WorkflowConstants
{
    // ── Labels ────────────────────────────────────────────────────────────────

    public const ROLE_LABELS = [
        'secretaire'          => 'Secrétaire',
        'comptable'           => 'Comptable',
        'responsable-division'=> 'Responsable Division',
        'chef-cap'            => 'Chef CAP',
        'sec-da'              => 'Secrétaire Directrice Adjointe',
        'directrice-adjointe' => 'Directrice Adjointe',
        'sec-dir'             => 'Secrétaire Directeur',
        'directeur'           => 'Directeur',
        'admin'               => 'Administrateur',
    ];

    public const TYPE_LABELS = [
        'attestation_passage'     => 'Attestation de Passage',
        'attestation_definitive'  => 'Attestation Définitive',
        'attestation_inscription' => "Attestation d'Inscription",
        'bulletin_notes'          => 'Bulletin de Notes',
    ];

    public const ACTION_LABELS = [
        'secretaire_validate'            => 'Validation',
        'secretaire_reject'              => 'Rejet',
        'secretaire_reject_final'        => 'Rejet définitif',
        'secretaire_resend'              => 'Renvoi',
        'secretaire_deliver'             => 'Remise',
        'secretaire_mark_ready'          => 'Document prêt (finalisation)',
        'comptable_validate'             => 'Validation',
        'comptable_validate_flagged'     => 'Validation avec réserve',
        'comptable_reject'               => 'Rejet',
        'responsable_division_validate'         => 'Validation',
        'responsable_division_validate_flagged' => 'Validation avec réserve',
        'responsable_division_reject'           => 'Rejet',
        'chef_cap_validate'              => 'Validation — Transmission Direction',
        'chef_cap_validate_flagged'      => 'Validation avec réserve — Transmission Direction',
        'chef_cap_reject'                => 'Rejet',
        // Anciens slugs conservés pour compatibilité historique (ne déclenchent plus de nouvelle transition)
        'chef_cap_sign'                  => 'Signature / Paraphe (obsolète)',
        'chef_cap_sign_flagged'          => 'Signature avec réserve (obsolète)',
        'sec_da_transmit'                => 'Transmission',
        'sec_da_transmit_flagged'        => 'Transmission avec réserve',
        'sec_da_reject'                  => 'Rejet',
        'directrice_adjointe_sign'       => 'Signature',
        'directrice_adjointe_sign_flagged' => 'Signature avec réserve',
        'directrice_adjointe_reject'     => 'Rejet',
        'sec_directeur_transmit'         => 'Transmission',
        'sec_directeur_transmit_flagged' => 'Transmission avec réserve',
        'sec_directeur_reject'           => 'Rejet',
        'directeur_sign'                 => 'Signature',
        'directeur_sign_flagged'         => 'Signature avec réserve',
        'directeur_reject'               => 'Rejet',
        'clear_flag'                     => 'Réserve levée',
        'return_to_secretaire'           => 'Renvoi à la Secrétaire',
    ];

    // ── Nouveau statut → slug rôle notifié ────────────────────────────────────
    //
    // CORRECTION BUG 3 : 'pending' ajouté pour notifier la secrétaire
    // à chaque nouvelle soumission de demande ou de complément de dossier.
    //
    // NOUVEAU WORKFLOW : après signature du Directeur, le dossier passe en
    // 'secretary_final_review' — la secrétaire est notifiée et doit finaliser
    // (marquer le document prêt via secretaire_mark_ready → ready_for_pickup).

    public const STATUS_TO_ROLE = [
        'submitted'                          => 'secretaire',       // ← AJOUT (Bug 3)
        'accounting_review'                  => 'comptable',
        'division_manager_review'            => 'responsable-division',
        'cap_manager_review'                 => 'chef-cap',
        'deputy_director_secretary_review'   => 'sec-da',
        'deputy_director_review'             => 'directrice-adjointe',
        'director_secretary_review'          => 'sec-dir',
        'director_review'                    => 'directeur',
        'secretary_correction'               => 'secretaire',
        'secretary_final_review'             => 'secretaire',       // ← NOUVEAU : après signature DG
    ];

    // ── Matrice d'autorisation ────────────────────────────────────────────────

    public const ACTION_MATRIX = [
        'secretaire' => [
            'secretaire_validate'     => ['submitted'],
            'secretaire_reject'       => ['submitted'],
            'secretaire_resend'       => ['secretary_correction'],
            'secretaire_reject_final' => ['secretary_correction'],
            'secretaire_deliver'      => ['ready_for_pickup'],
            'secretaire_mark_ready'   => ['secretary_final_review'],  // ← NOUVEAU
            'clear_flag'              => [],
        ],
        'comptable' => [
            'comptable_validate'         => ['accounting_review'],
            'comptable_validate_flagged' => ['accounting_review'],
            'comptable_reject'           => ['accounting_review'],
            'return_to_secretaire'       => ['accounting_review'],
        ],
        'responsable-division' => [
            'responsable_division_validate'         => ['division_manager_review'],
            'responsable_division_validate_flagged' => ['division_manager_review'],
            'responsable_division_reject'           => ['division_manager_review'],
            'return_to_secretaire'                  => ['division_manager_review'],
        ],
        'chef-cap' => [
            // Nouveaux slugs : le Chef CAP valide simplement (transmission vers Direction)
            'chef_cap_validate'         => ['cap_manager_review'],
            'chef_cap_validate_flagged' => ['cap_manager_review'],
            'chef_cap_reject'           => ['cap_manager_review'],
            'return_to_secretaire'      => ['cap_manager_review'],
            // Anciens slugs conservés pour compatibilité (historique existant)
            'chef_cap_sign'             => ['cap_manager_review'],
            'chef_cap_sign_flagged'     => ['cap_manager_review'],
        ],
        'sec-da' => [
            'sec_da_transmit'         => ['deputy_director_secretary_review'],
            'sec_da_transmit_flagged' => ['deputy_director_secretary_review'],
            'sec_da_reject'           => ['deputy_director_secretary_review'],
            'return_to_secretaire'    => ['deputy_director_secretary_review'],
        ],
        'directrice-adjointe' => [
            'directrice_adjointe_sign'         => ['deputy_director_review'],
            'directrice_adjointe_sign_flagged' => ['deputy_director_review'],
            'directrice_adjointe_reject'       => ['deputy_director_review'],
            'return_to_secretaire'             => ['deputy_director_review'],
        ],
        'sec-dir' => [
            'sec_directeur_transmit'         => ['director_secretary_review'],
            'sec_directeur_transmit_flagged' => ['director_secretary_review'],
            'sec_directeur_reject'           => ['director_secretary_review'],
            'return_to_secretaire'           => ['director_secretary_review'],
        ],
        'directeur' => [
            'directeur_sign'         => ['director_review'],
            'directeur_sign_flagged' => ['director_review'],
            'directeur_reject'       => ['director_review'],
            'return_to_secretaire'   => ['director_review'],
        ],
    ];

    // ── Statuts visibles par rôle ─────────────────────────────────────────────
    //
    // RÈGLE DU CIRCUIT DE CORRECTION :
    //   Un dossier en 'secretaire_correction' n'appartient QU'à la secrétaire.
    //   Les acteurs (comptable, chef-division, etc.) ne voient 'secretaire_correction'
    //   JAMAIS dans leur liste — quand la secrétaire leur renvoie un dossier pour
    //   correction, elle change le statut vers leur statut normal (ex: chef_division_review)
    //   avec is_in_correction_circuit = true. C'est ce flag qui change leur interface,
    //   pas le statut.
    //
    // NOUVEAU STATUT 'secretary_final_review' :
    //   Après signature du Directeur, le dossier revient à la secrétaire pour finalisation.
    //   Seule la secrétaire voit ce statut et peut déclencher secretaire_mark_ready.

    public const VISIBLE_STATUSES = [
        'secretaire' => [
            'submitted', 'secretary_correction',
            'accounting_review', 'division_manager_review', 'cap_manager_review',
            'deputy_director_secretary_review', 'deputy_director_review',
            'director_secretary_review', 'director_review',
            'secretary_final_review',                                   // ← NOUVEAU
            'ready_for_pickup', 'picked_up', 'rejected',
        ],
        // Chaque acteur voit UNIQUEMENT son statut propre.
        // secretaire_correction est retiré intentionnellement.
        'comptable'           => ['accounting_review'],
        'responsable-division'=> ['division_manager_review'],
        'chef-cap'            => ['cap_manager_review'],
        'sec-da'              => ['deputy_director_secretary_review'],
        'directrice-adjointe' => ['deputy_director_review'],
        'sec-dir'             => ['director_secretary_review'],
        'directeur'           => ['director_review'],
        'admin'               => [],
    ];

    // ── Rôles direction ───────────────────────────────────────────────────────

    public const DIRECTION_ROLES = [
        'sec-da',
        'directrice-adjointe',
        'sec-dir',
        'directeur',
    ];

    private function __construct() {}
}
