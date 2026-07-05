<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongregationService extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'service_type', 'applicant_name', 'applicant_gender',
        'baptismal_name', 'birth_place', 'birth_date', 'address', 'contact',
        'phone', 'mobile_phone', 'neighborhood', 'region', 'parish',
        'father_name', 'father_religion', 'mother_name', 'mother_religion',
        'school', 'grade', 'occupation', 'family_card_number',
        'service_date', 'description', 'status', 'notes', 'dynamic_fields',
        'signature_pemohon', 'signature_pemohon_at',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date:Y-m-d',
            'birth_date' => 'date:Y-m-d',
            'dynamic_fields' => 'array',
            'signature_pemohon_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
