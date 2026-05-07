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
        Schema::create('course_elements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('teaching_unit_id')->constrained('teaching_units')->onDelete('cascade');
            $table->string('type')->nullable();
            $table->integer('hours')->default(0);
            $table->decimal('coefficient', 5, 2)->default(1.0);
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('teaching_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_elements');
    }
};
