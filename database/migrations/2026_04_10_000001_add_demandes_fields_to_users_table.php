<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajout des champs nécessaires au module Demandes sur la table users :
 *  - chef_division_type : distingue les deux responsables de division
 *  - whatsapp_number    : numéro WhatsApp pour les notifications
 *  - digital_signature  : chemin de l'image de signature scannée (optionnelle)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // chef_division_type
            if (!Schema::hasColumn('users', 'chef_division_type')) {
                $table->enum('chef_division_type', ['formation_distance', 'formation_continue'])
                      ->nullable()
                      ->after('phone')
                      ->comment('Uniquement pour les rôles chef-division');
            }

            // whatsapp_number
            if (!Schema::hasColumn('users', 'whatsapp_number')) {
                $table->string('whatsapp_number', 20)
                      ->nullable()
                      ->after('chef_division_type')
                      ->comment('Numéro WhatsApp pour les notifications (format: +22967000000)');
            }

            // digital_signature_path
            if (!Schema::hasColumn('users', 'digital_signature_path')) {
                $table->string('digital_signature_path')
                      ->nullable()
                      ->after('whatsapp_number')
                      ->comment('Chemin de l\'image de signature scannée (PNG/JPG)');
            }

            // digital_signature_active
            if (!Schema::hasColumn('users', 'digital_signature_active')) {
                $table->boolean('digital_signature_active')
                      ->default(false)
                      ->after('digital_signature_path');
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $columns = [
                'chef_division_type',
                'whatsapp_number',
                'digital_signature_path',
                'digital_signature_active',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

        });
    }
};