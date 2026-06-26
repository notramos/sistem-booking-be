<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongregationService extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'service_type', 'applicant_name', 'address', 'contact',
        'service_date', 'description', 'status', 'notes', 'dynamic_fields',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date:Y-m-d',
            'dynamic_fields' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
