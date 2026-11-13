<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Attestations (de stage et de service fait) : « N° …/FCP/CAP/EPAC/UAC ».
 * Le suffixe enregistré n'est remplacé que s'il est resté celui d'origine.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->swap('CAP/EPAC/UAC', 'FCP/CAP/EPAC/UAC');
    }

    public function down(): void
    {
        $this->swap('FCP/CAP/EPAC/UAC', 'CAP/EPAC/UAC');
    }

    private function swap(string $from, string $to): void
    {
        if (!Schema::hasTable('stage_settings')) {
            return;
        }
        DB::table('stage_settings')->where('key', 'numero_suffixe')->whereRaw('JSON_UNQUOTE(`value`) = ?', [$from])
            ->update(['value' => json_encode($to)]);
        Cache::forget('stages:settings');
    }
};
