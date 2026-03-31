<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('contrat_number')->nullable();
            $table->string('division')->nullable();

            $table->foreignId('professor_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->decimal('amount', 12, 2)->default(0);

            $table->date('validation_date')->nullable();
            $table->boolean('is_validated')->default(false);

            $table->enum('status', [
                'pending',
                'signed',
                'ongoing',
                'completed',
                'cancelled'
            ])->default('pending');

            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};
