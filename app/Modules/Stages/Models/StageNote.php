<?php

namespace App\Modules\Stages\Models;

use Illuminate\Database\Eloquent\Model;

class StageNote extends Model
{
    public $timestamps = false;

    protected $table = 'stage_notes';

    protected $guarded = ['id'];

    /**
     * Numérotation des notes de service de stage (initiales et rectificatives) :
     * série propre, « 001-26 » = 1re note de 2026, imprimée
     * « N° 001-26/STG/CAP/EPAC/UAC ». Les anciennes notes gardent leur numéro.
     */
    public const NUMBER_PATTERN = '/^(\d{3,})-(\d{2})$/';

    /** Numéro suivant de l'année (à appeler dans une transaction). */
    public static function nextNumber(string $yy): string
    {
        $max = static::where('number', 'like', '%-' . $yy)->lockForUpdate()->pluck('number')
            ->map(fn ($n) => preg_match(self::NUMBER_PATTERN, (string) $n, $m) && $m[2] === $yy ? (int) $m[1] : 0)
            ->max() ?? 0;

        return str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT) . '-' . $yy;
    }

    /** Numéro complet imprimé : « N° 001-26/STG/CAP/EPAC/UAC ». */
    public function reference(string $suffix, string $legacySuffix): string
    {
        return 'N° ' . $this->number . '/' . trim(preg_match(self::NUMBER_PATTERN, (string) $this->number) ? $suffix : $legacySuffix, '/');
    }

    protected function casts(): array
    {
        return [
            'snapshot'  => 'array',
            'issued_at' => 'datetime',
            'recorded_at' => 'datetime',
        ];
    }
}
