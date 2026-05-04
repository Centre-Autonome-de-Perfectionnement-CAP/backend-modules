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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // 🔗 Relations
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');

            $table->foreignId('course_element_id') // ✅ correction : relier aux course_elements
                ->constrained('course_elements')
                ->onDelete('cascade');

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->onDelete('cascade');

            // Statut présence
            $table->enum('status', ['present', 'absent'])->default('present');
            $table->date('date'); // ✅ nouvelle colonne pour la date réelle de la séance
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
