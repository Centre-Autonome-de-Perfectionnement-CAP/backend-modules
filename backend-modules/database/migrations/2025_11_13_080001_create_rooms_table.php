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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            $table->foreignId('building_id')
                ->constrained('buildings')
                ->onDelete('cascade');
            
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('capacity')->unsigned();
            $table->enum('room_type', [
                'amphitheater',
                'classroom',
                'lab',
                'computer_lab',
                'conference'
            ])->default('classroom');
            $table->json('equipment')->nullable()->comment('Équipements disponibles: projecteur, climatisation, etc.');
            $table->boolean('is_available')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('building_id');
            $table->index('code');
            $table->index('room_type');
            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
