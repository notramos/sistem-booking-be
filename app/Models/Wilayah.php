<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wilayah extends Model
{
    use HasUuids;

    protected $table = 'wilayah';

    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function lingkungan(): HasMany
    {
        return $this->hasMany(Lingkungan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
