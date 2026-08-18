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
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->unsignedSmallInteger('enrollment_year');

            $table->enum('status', ['pending', 'validated', 'rejected'])
                ->default('pending')
                ->index();

            $table->text('rejection_reason')->nullable();
            $table->text('notes_admin')->nullable();

            $table->foreignId('validated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_students');
    }
};
