<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Room extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'category_id', 'description', 'capacity',
        'floor', 'building', 'latitude', 'longitude', 'status', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Room $room) => $room->slug ??= Str::slug($room->name));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'category_id');
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(RoomFacility::class, 'facility_room', 'room_id', 'facility_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(RoomImage::class)->where('is_primary', true)->limit(1);
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

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === RoomStatus::AVAILABLE->value && $this->is_active;
    }

    public function isUnderMaintenance(): bool
    {
        return $this->status === RoomStatus::MAINTENANCE->value;
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', RoomStatus::AVAILABLE->value)->where('is_active', true);
    }

    public function scopeByCapacity($query, int $minCapacity)
    {
        return $query->where('capacity', '>=', $minCapacity);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
              ->orWhere('building', 'ilike', "%{$term}%")
              ->orWhere('description', 'ilike', "%{$term}%");
        });
    }
}
