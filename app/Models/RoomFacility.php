<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoomFacility extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'icon', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'facility_room', 'facility_id', 'room_id');
    }
}
