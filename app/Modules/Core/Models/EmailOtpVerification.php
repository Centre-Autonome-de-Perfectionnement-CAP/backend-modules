<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class EmailOtpVerification extends Model
{
    protected $table = 'email_otp_verifications';

    protected $fillable = [
        'email',
        'code',
        'token',
        'purpose',
        'attempts',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }
}
