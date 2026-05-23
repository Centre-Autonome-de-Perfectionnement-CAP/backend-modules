<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE document_requests MODIFY COLUMN status ENUM(
            'submitted',
            'secretary_correction',
            'accounting_review',
            'division_manager_review',
            'cap_manager_review',
            'deputy_director_secretary_review',
            'deputy_director_review',
            'director_secretary_review',
            'director_review',
            'ready_for_pickup',
            'picked_up',
            'rejected'
        ) NOT NULL DEFAULT 'submitted'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE document_requests MODIFY COLUMN status ENUM(
            'submitted',
            'secretary_correction',
            'accounting_review',
            'division_manager_review',
            'cap_manager_review',
            'deputy_director_secretary_review',
            'deputy_director_review',
            'director_secretary_review',
            'director_review',
            'ready_for_pickup',
            'picked_up',
            'rejected'
        ) NOT NULL DEFAULT 'submitted'");
    }
};
