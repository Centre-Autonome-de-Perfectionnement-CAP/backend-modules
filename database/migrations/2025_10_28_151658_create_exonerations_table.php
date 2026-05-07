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
        Schema::create('exonerations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('matricule');
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->enum('type', ['full', 'partial', 'merit', 'need_based'])->default('partial');
            $table->text('reason')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('matricule');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exonerations');
    }
};
