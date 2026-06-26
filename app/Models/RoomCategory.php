<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RoomCategory extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (RoomCategory $cat) => $cat->slug ??= Str::slug($cat->name));
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'category_id');
    }
}
