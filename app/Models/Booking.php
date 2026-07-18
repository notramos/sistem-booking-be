<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'room_id', 'title', 'description', 'booking_date',
        'start_time', 'end_time', 'purpose_type', 'expected_attendees',
        'contact_person', 'status', 'notes', 'service_details', 'reject_reason',
        'cancelled_at', 'completed_at',
        'signature_pemohon', 'signature_pemohon_at',
        'signature_petugas', 'signature_petugas_at', 'signed_petugas_by',
        'booking_type', 'recurring_pattern', 'recurring_dates',
    ];

    protected $appends = ['is_cancellable'];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date:Y-m-d',
            'start_time' => 'string',
            'end_time' => 'string',
            'expected_attendees' => 'integer',
            'service_details' => 'array',
            'recurring_dates' => 'array',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'signature_pemohon_at' => 'datetime',
            'signature_petugas_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class);
    }

    public function sekretariatApproval(): HasOne
    {
        return $this->hasOne(BookingApproval::class)->where('stage', 'sekretariat')->latestOfMany();
    }

    public function adminApproval(): HasOne
    {
        return $this->hasOne(BookingApproval::class)->where('stage', 'admin')->latestOfMany();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BookingLog::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_petugas_by');
    }

    public function isPending(): bool
    {
        return $this->status === BookingStatus::PENDING->value;
    }

    public function isApproved(): bool
    {
        return $this->status === BookingStatus::APPROVED->value;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [
            ...BookingStatus::nonFinal(),
            BookingStatus::APPROVED->value,
        ]);
    }

    public function getIsCancellableAttribute(): bool
    {
        return $this->isCancellable();
    }

    public function scopePending($query)
    {
        return $query->where('status', BookingStatus::PENDING->value);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            BookingStatus::PENDING->value,
            BookingStatus::APPROVED->value,
        ]);
    }

    public function scopePendingSekretariat($query)
    {
        return $query->whereIn('status', [
            BookingStatus::PENDING->value,
            BookingStatus::SEKRETARIAT_REVIEW->value,
        ]);
    }

    public function scopePendingAdmin($query)
    {
        return $query->where('status', BookingStatus::ADMIN_REVIEW->value);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }
}
