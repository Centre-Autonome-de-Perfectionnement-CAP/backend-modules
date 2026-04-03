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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('student_id_number')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('student_id_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('matricule')->unique()->nullable();
            $table->string('niveau')->nullable();
            $table->boolean('fingerprint_status')->default(false);

           $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('filiere_id')->nullable();
});
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
