<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RegistrationVerification extends Model
{
    use HasUuids;

    protected $fillable = [
        'email', 'code_hash', 'attempts', 'expires_at', 'verified_at', 'verification_token',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
