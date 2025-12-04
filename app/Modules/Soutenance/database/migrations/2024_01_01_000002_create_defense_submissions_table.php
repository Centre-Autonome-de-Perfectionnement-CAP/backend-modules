<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('last_name');
            $table->string('first_names');
            $table->string('email');
            $table->string('contacts');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('student_id_number', 11);
            $table->foreignId('defense_submission_period_id')->constrained('defense_submission_periods')->onDelete('cascade');
            $table->text('thesis_title');
            $table->foreignId('professor_id')->constrained('professors')->onDelete('cascade');
            $table->json('files')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'scheduled', 'completed'])->default('pending');
            $table->enum('defense_type', ['licence', 'master', 'doctorat']);
            $table->text('rejection_reason')->nullable();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null');
            $table->dateTime('defense_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_submissions');
    }
};
