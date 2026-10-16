<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Intègre les anciens étudiants (déclarés, table legacy_students) au circuit
 * officiel des demandes (attestations + bulletins).
 *
 *  - student_pending_student_id devient nullable : une demande appartient
 *    SOIT à un étudiant inscrit (lien student_pending_student), SOIT à un
 *    ancien étudiant (legacy_student_id) ;
 *  - legacy_student_id : rattachement à la déclaration de l'ancien étudiant ;
 *  - bulletin_year : année du bulletin choisie par l'ancien étudiant
 *    (format AAAA-AAAA), ses années n'existant pas dans academic_years.
 *
 * Idempotente et réversible. Le down() supprime d'abord les demandes
 * d'anciens étudiants (sinon la colonne ne peut redevenir NOT NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('student_pending_student_id')->nullable()->change();
        });

        if (!Schema::hasColumn('document_requests', 'legacy_student_id')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->foreignId('legacy_student_id')
                    ->nullable()
                    ->after('student_pending_student_id')
                    ->constrained('legacy_students')
                    ->cascadeOnDelete();
                $table->index(['legacy_student_id', 'type', 'status'], 'dr_legacy_type_status_idx');
            });
        }

        if (!Schema::hasColumn('document_requests', 'bulletin_year')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->string('bulletin_year', 9)->nullable()->after('academic_year_id')
                    ->comment('Année du bulletin (AAAA-AAAA) — anciens étudiants');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_requests', 'legacy_student_id')) {
            \Illuminate\Support\Facades\DB::table('document_requests')
                ->whereNull('student_pending_student_id')
                ->delete();

            Schema::table('document_requests', function (Blueprint $table) {
                $table->dropForeign(['legacy_student_id']);
                $table->dropIndex('dr_legacy_type_status_idx');
                $table->dropColumn('legacy_student_id');
            });
        }

        if (Schema::hasColumn('document_requests', 'bulletin_year')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->dropColumn('bulletin_year');
            });
        }

        Schema::table('document_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('student_pending_student_id')->nullable(false)->change();
        });
    }
};
