<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasUuids, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'department',
        'position',
        'nip',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activeBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::APPROVED->value]);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class, 'approver_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSekretariat(): bool
    {
        return $this->hasRole('sekretariat');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
