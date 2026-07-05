<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lingkungan extends Model
{
    use HasUuids;

    protected $table = 'lingkungan';

    protected $fillable = ['wilayah_id', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
