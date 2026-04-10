<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('information_correction_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_id_number');
            $table->json('current_values');   // snapshot: last_name, first_names, email, contacts
            $table->json('suggested_values'); // propositions de l'étudiant
            $table->text('justification')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('student_id_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information_correction_requests');
    }
};
