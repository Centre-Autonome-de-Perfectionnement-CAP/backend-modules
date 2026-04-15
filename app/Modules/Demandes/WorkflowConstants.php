<?php

namespace App\Modules\Demandes;

/**
 * Source unique de vérité pour les constantes du workflow Demandes.
 * Importée partout où l'on a besoin de labels, mappings ou matrices.
 */
final class WorkflowConstants
{
    // ── Labels ────────────────────────────────────────────────────────────────

    public const ROLE_LABELS = [
        'secretaire'          => 'Secrétaire',
        'comptable'           => 'Comptable',
        'chef-division'       => 'Responsable Division',
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
        'secretaire_validate'          => 'Validation',
        'secretaire_reject'            => 'Rejet',
        'secretaire_reject_final'      => 'Rejet définitif',
        'secretaire_resend'            => 'Renvoi',
        'secretaire_deliver'           => 'Remise',
        'comptable_validate'           => 'Validation',
        'comptable_validate_flagged'   => 'Validation avec réserve',
        'comptable_reject'             => 'Rejet',
        'chef_division_validate'       => 'Validation',
        'chef_division_validate_flagged' => 'Validation avec réserve',
        'chef_division_reject'         => 'Rejet',
        'chef_cap_sign'                => 'Signature / Paraphe',
        'chef_cap_sign_flagged'        => 'Signature avec réserve',
        'chef_cap_reject'              => 'Rejet',
        'sec_da_transmit'              => 'Transmission',
        'sec_da_transmit_flagged'      => 'Transmission avec réserve',
        'sec_da_reject'                => 'Rejet',
        'directrice_adjointe_sign'     => 'Signature',
        'directrice_adjointe_sign_flagged' => 'Signature avec réserve',
        'directrice_adjointe_reject'   => 'Rejet',
        'sec_directeur_transmit'       => 'Transmission',
        'sec_directeur_transmit_flagged' => 'Transmission avec réserve',
        'sec_directeur_reject'         => 'Rejet',
        'directeur_sign'               => 'Signature',
        'directeur_sign_flagged'       => 'Signature avec réserve',
        'directeur_reject'             => 'Rejet',
        'clear_flag'                   => 'Réserve levée',
    ];

    // ── Nouveau statut → slug rôle notifié ────────────────────────────────────

    public const STATUS_TO_ROLE = [
        'comptable_review'           => 'comptable',
        'chef_division_review'       => 'chef-division',
        'chef_cap_review'            => 'chef-cap',
        'sec_dir_adjointe_review'    => 'sec-da',
        'directrice_adjointe_review' => 'directrice-adjointe',
        'sec_directeur_review'       => 'sec-dir',
        'directeur_review'           => 'directeur',
        'secretaire_correction'      => 'secretaire',
    ];

    // ── Matrice d'autorisation : rôle → action → statuts autorisés ───────────

    public const ACTION_MATRIX = [
        'secretaire' => [
            'secretaire_validate'     => ['pending'],
            'secretaire_reject'       => ['pending'],
            'secretaire_resend'       => ['secretaire_correction'],
            'secretaire_reject_final' => ['secretaire_correction'],
            'secretaire_deliver'      => ['ready'],
            'clear_flag'              => [], // tous statuts — validé dans le service
        ],
        'comptable' => [
            'comptable_validate'         => ['comptable_review'],
            'comptable_validate_flagged' => ['comptable_review'],
            'comptable_reject'           => ['comptable_review'],
        ],
        'chef-division' => [
            'chef_division_validate'         => ['chef_division_review'],
            'chef_division_validate_flagged' => ['chef_division_review'],
            'chef_division_reject'           => ['chef_division_review'],
        ],
        'chef-cap' => [
            'chef_cap_sign'         => ['chef_cap_review'],
            'chef_cap_sign_flagged' => ['chef_cap_review'],
            'chef_cap_reject'       => ['chef_cap_review'],
        ],
        'sec-da' => [
            'sec_da_transmit'         => ['sec_dir_adjointe_review'],
            'sec_da_transmit_flagged' => ['sec_dir_adjointe_review'],
            'sec_da_reject'           => ['sec_dir_adjointe_review'],
        ],
        'directrice-adjointe' => [
            'directrice_adjointe_sign'         => ['directrice_adjointe_review'],
            'directrice_adjointe_sign_flagged' => ['directrice_adjointe_review'],
            'directrice_adjointe_reject'       => ['directrice_adjointe_review'],
        ],
        'sec-dir' => [
            'sec_directeur_transmit'         => ['sec_directeur_review'],
            'sec_directeur_transmit_flagged' => ['sec_directeur_review'],
            'sec_directeur_reject'           => ['sec_directeur_review'],
        ],
        'directeur' => [
            'directeur_sign'         => ['directeur_review'],
            'directeur_sign_flagged' => ['directeur_review'],
            'directeur_reject'       => ['directeur_review'],
        ],
    ];

    // ── Statuts visibles par rôle ─────────────────────────────────────────────

    public const VISIBLE_STATUSES = [
        'secretaire' => [
            'pending', 'secretaire_correction',
            'comptable_review', 'chef_division_review', 'chef_cap_review',
            'sec_dir_adjointe_review', 'directrice_adjointe_review',
            'sec_directeur_review', 'directeur_review',
            'ready', 'delivered', 'rejected',
        ],
        'comptable'           => ['comptable_review'],
        'chef-division'       => ['chef_division_review'],
        'chef-cap'            => ['chef_cap_review'],
        'sec-da'              => ['sec_dir_adjointe_review'],
        'directrice-adjointe' => ['directrice_adjointe_review'],
        'sec-dir'             => ['sec_directeur_review'],
        'directeur'           => ['directeur_review'],
        'admin'               => [],  // tout
    ];

    // ── Rôles direction (pas d'accès portail) ─────────────────────────────────

    public const DIRECTION_ROLES = [
        'sec-da',
        'directrice-adjointe',
        'sec-dir',
        'directeur',
    ];

    private function __construct() {} // non-instanciable
}
