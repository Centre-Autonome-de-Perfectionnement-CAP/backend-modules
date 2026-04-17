<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complément de dossier — colonnes ajoutées à document_requests
 *
 *  complement_files           JSON nullable
 *      — Pièces déposées par l'étudiant en complément
 *      — Même format que `files` : { key => chemin_storage }
 *      — Stockées sous storage/{MATRICULE}/complement/
 *      — Affichées dans DossierFilesSplit (onglet « Complémentaires »)
 *
 *  complement_at              timestamp nullable
 *      — Date du dernier dépôt complémentaire (mis à jour à chaque soumission)
 *
 *  complement_pieces_requises JSON nullable
 *      — Liste des pièces réclamées par le secrétariat
 *      — Format : [{"key":"acte_naissance","label":"Acte de naissance","required":true}, …]
 *      — Renseignée depuis le progiciel, consommée par le site vitrine
 *
 * NOTE : cette migration remplace les deux migrations en conflit :
 *   - 2026_04_16_000001_add_complement_fields_to_document_requests.php  (files__26_.zip)
 *   - 2026_04_16_055849_add_complement_files_to_document_requests.php   (complement_files_feature.zip)
 * Ne lancer qu'une seule des deux — utiliser uniquement celle-ci.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            // Pièces complémentaires déposées
            $table->json('complement_files')
                  ->nullable()
                  ->after('files')
                  ->comment('Fichiers ajoutés lors du complément { key => chemin }');

            // Date du dernier complément reçu
            $table->timestamp('complement_at')
                  ->nullable()
                  ->after('complement_files')
                  ->comment('Horodatage du dernier dépôt complémentaire');

            // Pièces réclamées par la scolarité
            $table->json('complement_pieces_requises')
                  ->nullable()
                  ->after('complement_at')
                  ->comment('Pièces demandées : [{key, label, required}]');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn([
                'complement_files',
                'complement_at',
                'complement_pieces_requises',
            ]);
        });
    }
};
