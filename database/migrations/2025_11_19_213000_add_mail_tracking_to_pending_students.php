<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_students', function (Blueprint $table) {
            if (!Schema::hasColumn('pending_students', 'cuo_opinion')) {
                $table->string('cuo_opinion')->nullable()->after('cuca_comment');
            }
            if (!Schema::hasColumn('pending_students', 'cuo_comment')) {
                $table->text('cuo_comment')->nullable()->after('cuo_opinion');
            }
            if (!Schema::hasColumn('pending_students', 'mail_cuca_sent')) {
                $table->boolean('mail_cuca_sent')->default(false)->after('cuo_comment');
            }
            if (!Schema::hasColumn('pending_students', 'mail_cuca_count')) {
                $table->integer('mail_cuca_count')->default(0)->after('mail_cuca_sent');
            }
            if (!Schema::hasColumn('pending_students', 'mail_cuo_sent')) {
                $table->boolean('mail_cuo_sent')->default(false)->after('mail_cuca_count');
            }
            if (!Schema::hasColumn('pending_students', 'mail_cuo_count')) {
                $table->integer('mail_cuo_count')->default(0)->after('mail_cuo_sent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pending_students', function (Blueprint $table) {
            $table->dropColumn(['cuo_opinion', 'cuo_comment', 'mail_cuca_sent', 'mail_cuca_count', 'mail_cuo_sent', 'mail_cuo_count']);
        });
    }
};
