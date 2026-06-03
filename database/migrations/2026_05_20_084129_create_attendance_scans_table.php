<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Nouveau schéma de pointage entrée/sortie
     *
     * attendance_scans  — chaque scan biométrique individuel
     *   → entry (arrivée) ou exit (départ/retour)
     *
     * attendances       — session calculée par jour et par cours
     *   → first_entry, last_exit, total_minutes, status, late_type
     *
     * Règles CAP-EPAC :
     *   Cours LUN-VEN : 18h00 → 22h00 (240 min)
     *   Cours SAM     : 08h00 → 12h00 (240 min)
     *   Ouverture pointage : 1h avant le cours
     *   Fermeture pointage : 15 min après la fin
     *   Grâce présent      : arrivée ≤ 18h15 (sam ≤ 08h15)
     *   Retard léger       : 18h16 → 18h30
     *   Retard grave       : après 18h30
     *   Absent             : aucun scan OU temps total < 168 min (70%)
     */

    public function up(): void
    {
        // ── Table des scans individuels ───────────────────────────────────────
        if (!Schema::hasTable('attendance_scans')) {
            Schema::create('attendance_scans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('course_element_id')->nullable();
                $table->unsignedBigInteger('room_id')->nullable();
                $table->date('date');
                $table->time('scan_time');                  // heure exacte du scan
                $table->enum('scan_type', ['entry', 'exit']); // entrée ou sortie
                $table->timestamps();

                $table->index(['student_id', 'date']);
                $table->index(['course_element_id', 'date']);
            });
        }

        // ── Colonnes supplémentaires sur attendances ──────────────────────────
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'fingerprint_index')) {
                $table->unsignedTinyInteger('fingerprint_index')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('attendances', 'first_entry')) {
                $table->time('first_entry')->nullable()->after('on_time')
                      ->comment('Heure du 1er scan entrée');
            }
            if (!Schema::hasColumn('attendances', 'last_exit')) {
                $table->time('last_exit')->nullable()->after('first_entry')
                      ->comment('Heure du dernier scan sortie');
            }
            if (!Schema::hasColumn('attendances', 'total_minutes')) {
                $table->unsignedSmallInteger('total_minutes')->default(0)->after('last_exit')
                      ->comment('Durée totale de présence en minutes');
            }
            if (!Schema::hasColumn('attendances', 'late_type')) {
                // null = à l'heure, 'leger' = retard ≤ 15min, 'grave' = retard > 15min
                $table->enum('late_type', ['leger', 'grave'])->nullable()->after('total_minutes');
            }
            if (!Schema::hasColumn('attendances', 'on_time')) {
                $table->boolean('on_time')->default(true)->after('status');
            }
            if (!Schema::hasColumn('attendances', 'date')) {
                $table->date('date')->nullable()->after('on_time');
            }
            if (!Schema::hasColumn('attendances', 'room_id')) {
                $table->unsignedBigInteger('room_id')->nullable()->after('course_element_id');
            }
        });

        // fingerprint_index sur students
        if (!Schema::hasColumn('students', 'fingerprint_index')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedTinyInteger('fingerprint_index')
                      ->nullable()->unique()->after('fingerprint_status')
                      ->comment('Slot capteur AS608 (1-127)');
            });
        }
        if (!Schema::hasColumn('students', 'fingerprint_status')) {
            Schema::table('students', function (Blueprint $table) {
                $table->boolean('fingerprint_status')->default(false)->after('phone');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_scans');
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('attendances', 'fingerprint_index') ? 'fingerprint_index' : null,
                Schema::hasColumn('attendances', 'first_entry')       ? 'first_entry'       : null,
                Schema::hasColumn('attendances', 'last_exit')         ? 'last_exit'         : null,
                Schema::hasColumn('attendances', 'total_minutes')     ? 'total_minutes'     : null,
                Schema::hasColumn('attendances', 'late_type')         ? 'late_type'         : null,
            ]));
        });
    }
};