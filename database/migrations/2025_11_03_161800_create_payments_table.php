<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Informations étudiant
            $table->string('student_id_number'); // Matricule
            $table->foreignId('student_pending_student_id')->nullable()->constrained('student_pending_student')->onDelete('set null');

            // Informations paiement
            $table->string('reference')->unique(); // Référence de la quittance
            $table->decimal('amount', 10, 2); // Montant
            $table->string('account_number'); // Numéro de compte
            $table->date('payment_date'); // Date de versement
            $table->string('purpose')->nullable(); // Motif

            // Contact
            $table->string('email')->nullable();
            $table->string('contact')->nullable();

            // Quittance
            $table->string('receipt_path')->nullable(); // Chemin de la quittance

            // status et gestion
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('observation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('reference');
            $table->index('student_id_number');
            $table->index('status');
            $table->index('student_pending_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
