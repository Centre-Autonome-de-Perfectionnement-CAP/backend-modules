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
        Schema::create('course_element_resources', function (Blueprint $table) {
            $table->id();
            // Pour le trait HasUuid
            $table->uuid('uuid')->unique();

            // Relation avec CourseElement
            $table->foreignId('course_element_id')
                ->constrained('course_elements')
                ->onDelete('cascade');

            // Relation avec File (module Stockage)
            $table->foreignId('file_id')
                ->nullable()
                ->constrained('files')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('resource_type');

            // Ressource publique ou non
            $table->boolean('is_public')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Index utiles
            $table->index('course_element_id');
            $table->index('file_id');
            $table->index('resource_type');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_element_resources');
    }
};
