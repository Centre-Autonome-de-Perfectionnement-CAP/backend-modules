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
        // FUSION develop/main :
        //  - main seul (« créer si absente ») laissait sur une base neuve l'ANCIEN
        //    schéma créé par 2025_11_03_161801 (transaction_id, payment_id…) ;
        //  - develop seul (dropIfExists inconditionnel) pouvait détruire une table
        //    déjà au nouveau format.
        // On ne remplace donc la table que si elle est encore à l'ancien format.
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'reference')) {
            Schema::drop('transactions');
        }

        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                
                // Reference unique (numero_quittance)
                $table->string('reference', 50)->unique()->comment('Numéro de quittance unique');
                
                // Relations
                $table->foreignId('pending_student_id')->constrained('pending_students')->onDelete('cascade');
                $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
                
                // Informations de paiement
                $table->decimal('amount_paid', 10, 2)->comment('Montant payé');
                $table->date('payment_date')->comment('Date de versement');
                
                // Métadonnées
                $table->text('notes')->nullable()->comment('Notes ou observations');
                
                $table->timestamps();
                $table->softDeletes();
                
                // Index pour améliorer les performances
                $table->index('pending_student_id');
                $table->index('academic_year_id');
                $table->index('payment_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
