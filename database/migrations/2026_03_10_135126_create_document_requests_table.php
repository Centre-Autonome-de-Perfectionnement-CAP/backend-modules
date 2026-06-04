<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('student_pending_student_id')->constrained('student_pending_student')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->cascadeOnDelete();
            
            $table->string('type', 50);
            $table->text('rejected_reason')->nullable();
            $table->string('rejected_by')->nullable();
            
            $table->string('email')->nullable();
            $table->string('demandeur_whatsapp', 20)->nullable()->comment('Numéro WhatsApp du demandeur pour notifications');
            
            $table->enum('payment_method', ['manual', 'tresor_online'])->default('manual')
                  ->comment('manual = quittance physique uploadée | tresor_online = paiement API Trésor Public');
            $table->string('payment_reference', 50)->nullable()
                  ->comment('Numéro quittance Trésor. Préfixe QUI-TRESO- = paiement en ligne');
            
            $table->json('files')->nullable();
            
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            
            $table->enum('status', [
                'submitted',
                'secretary_correction',
                'accounting_review',
                'division_manager_review',
                'cap_manager_review',
                'deputy_director_secretary_review',
                'deputy_director_review',
                'director_secretary_review',
                'director_review',
                'secretary_final_review',
                'ready_for_pickup',
                'picked_up',
                'rejected'
            ])->default('submitted');
            
            $table->string('correction_origin_role')->nullable()->comment('Rôle de l\'acteur ayant initié le rejet initial');
            $table->boolean('is_in_correction_circuit')->default(false)->comment('Indique si le dossier est actuellement dans le circuit de correction');
            $table->string('correction_origin_status')->nullable()->comment('Statut BD au moment du déclenchement du circuit de correction');
            $table->boolean('has_flag')->default(false);
            
            $table->string('department_name')->nullable();
            $table->enum('responsable_division_type', ['formation_distance', 'formation_continue'])->nullable();
            $table->enum('signature_type', ['paraphe', 'signature'])->nullable();
            
            $table->timestamp('delivered_at')->nullable();
            
            $table->json('complement_files')->nullable()->comment('Fichiers ajoutés lors du complément (JSON)');
            $table->json('secretary_files')->nullable();
            $table->timestamp('complement_at')->nullable()->comment('Quand le demandeur a soumis le complément');

            // Indexes
            $table->index(['status', 'updated_at'], 'dr_status_updated_idx');
            $table->index(['responsable_division_type', 'status'], 'dr_responsable_division_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
