<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_jury_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('defense_submission_id')->constrained('defense_submissions')->onDelete('cascade');
            $table->foreignId('professor_id')->nullable()->constrained('professors')->onDelete('set null');
            $table->foreignId('grade_id')->nullable()->constrained('grades')->onDelete('set null');
            $table->string('name');
            $table->enum('role', ['president', 'rapporteur', 'examinateur', 'directeur']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_jury_members');
    }
};
