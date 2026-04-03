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
        Schema::create('personal_information', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('last_name');
            $table->string('first_names');
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('birth_country')->nullable();
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->json('contacts')->nullable();
            $table->string('nationality')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('email');
            $table->index('last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_information');
    }
};
