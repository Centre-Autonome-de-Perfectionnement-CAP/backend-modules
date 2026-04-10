<?php

namespace App\Modules\Inscription\Models;

use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformationCorrectionRequest extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'information_correction_requests';

    protected $fillable = [
        'student_id_number',
        'current_values',
        'suggested_values',
        'justification',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'current_values'  => 'array',
        'suggested_values' => 'array',
        'reviewed_at'     => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Retourne les clés des champs qui ont été modifiés.
     */
    public function getChangedFieldsAttribute(): array
    {
        return array_keys($this->suggested_values ?? []);
    }

    /**
     * Labels lisibles pour les champs modifiables.
     */
    public static function fieldLabels(): array
    {
        return [
            'last_name'   => 'Nom',
            'first_names' => 'Prénoms',
            'email'       => 'Adresse email',
            'contacts'    => 'Numéro(s) de téléphone',
        ];
    }
}
