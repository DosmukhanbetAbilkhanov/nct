<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class SmsVerificationCode extends Model
{
    protected $fillable = [
        'phone_number',
        'code_hash',
        'expires_at',
        'verified',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified' => 'boolean',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->verified && ! $this->isExpired();
    }

    public function verify(string $code): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if (Hash::check($code, $this->code_hash)) {
            $this->update(['verified' => true]);

            return true;
        }

        return false;
    }

    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
