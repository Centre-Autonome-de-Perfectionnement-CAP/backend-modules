<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_students', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique()->index();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->integer('enrollment_year'); // Année d'inscription < 2023
            $table->enum('status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->string('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_student_filieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_student_id')->constrained('legacy_students')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('legacy_student_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_student_id')->constrained('legacy_students')->cascadeOnDelete();
            $table->string('matricule')->index();
            $table->string('student_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('service_type'); // quitus_memoire, attestation_diplome, demande_bulletin, etc.
            $table->string('service_name');
            $table->string('filiere_name')->nullable();
            $table->integer('enrollment_year')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'delivered', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->string('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_student_services');
        Schema::dropIfExists('legacy_student_filieres');
        Schema::dropIfExists('legacy_students');
    }
};
