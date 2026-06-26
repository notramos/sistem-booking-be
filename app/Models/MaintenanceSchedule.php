<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'room_id', 'title', 'description', 'start_date', 'end_date',
        'start_time', 'end_time', 'is_all_day', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'start_time' => 'string',
            'end_time' => 'string',
            'is_all_day' => 'boolean',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', now()->toDateString());
    }

    public function scopeForRoom($query, string $roomId, string $date)
    {
        return $query->where('room_id', $roomId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }
}
