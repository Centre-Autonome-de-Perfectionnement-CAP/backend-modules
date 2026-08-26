<?php

namespace App\Modules\Inscription\Constants;

class DocumentFields
{
    public const LICENCE = [
        'demande_da' => 'Demande manuscrite adressée au D/EPAC',
        'cv' => 'Curriculum Vitae',
        'acte_naissance' => "Photocopie de l’extrait d’acte de naissance légalisé ou sécurisé",
        'diplome_bac' => 'Photocopie légalisée du diplôme BAC ou équivalent',
        'diplome_licence' => 'Photocopie légalisée du diplôme de la licence professionnelle',
        'attestation_travail' => 'Attestation de travail',
        'quittance_rectorat' => 'Quittance Rectorat de 2.000F',
        'quittance_cap' => 'Quittance de 10.000F',
        'attestation_depot_dossier' => 'Attestation de dépôt de dossier pour diplômes étrangers',
    ];

    public const MASTER = [
        'demande_da' => 'Demande manuscrite adressée au D/EPAC',
        'cv' => 'Curriculum Vitae',
        'acte_naissance' => "Photocopie de l’extrait d’acte de naissance légalisé ou sécurisé",
        'diplome_bac' => 'Photocopie légalisée du diplôme BAC',
        'diplome_license' => 'Photocopie légalisée du diplôme de la licence professionnelle',
        'diplome_licence' => 'Photocopie légalisée du diplôme de la licence professionnelle',
        'attestation_travail' => 'Attestation de travail',
        'quittance_rectorat' => 'Quittance Rectorat de 2.000F',
        'quittance_cap' => 'Quittance de 20.000F',
        'attestation_depot_dossier' => 'Attestation de dépôt de dossier pour diplômes étrangers',
        'attestation_anglais' => 'Attestation d’Anglais pour le secteur biologique',
    ];

    public const INGENIEUR_PREPA = [
        'demande_da' => 'Demande manuscrite adressée au D/EPAC',
        'cv' => 'Curriculum Vitae',
        'acte_naissance' => "Photocopie de l’extrait d’acte de naissance légalisé ou sécurisé",
        'diplome_bac' => 'Photocopie légalisée du diplôme BAC',
        'diplome_licence' => 'Photocopie légalisée du diplôme de la licence',
        'attestation_travail' => 'Attestation de travail',
        'quittance_cap' => 'Quittance de 15.000F',
        'attestation_depot_dossier' => 'Attestation de dépôt de dossier pour diplômes étrangers',
    ];

    public const INGENIEUR_SPECIALITE = [
        'certificat_prepa' => 'Certificat de scolarité des classes préparatoires ou équivalent',
        'quittance_cap' => 'Quittance de 10.000F',
    ];

    /**
     * Retourne la liste des champs et libellés de documents pour un cycle donné
     */
    public static function forCycle(?string $cycleName): array
    {
        $normalized = mb_strtolower((string) $cycleName);

        if (str_contains($normalized, 'master')) {
            return self::MASTER;
        }

        if (str_contains($normalized, 'ingénieur') || str_contains($normalized, 'ingenieur')) {
            return self::INGENIEUR_PREPA;
        }

        return self::LICENCE;
    }

    /**
     * Tous les libellés possibles unifiés (avec support des alias pour remplacement propre)
     */
    public static function allFields(): array
    {
        return array_merge(
            self::LICENCE,
            self::MASTER,
            self::INGENIEUR_PREPA,
            self::INGENIEUR_SPECIALITE
        );
    }
}
