<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateCongregationServiceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('bookings.approve') ?? false; }

    public function rules(): array
    {
        return [
            'applicant_name' => 'sometimes|string|max:255', 'contact' => 'sometimes|string|max:255',
            'region' => 'nullable|string|max:255', 'neighborhood' => 'nullable|string|max:255',
            'service_date' => 'nullable|date', 'status' => 'sometimes|in:pending,approved,rejected',
            'description' => 'nullable|string|max:2000', 'notes' => 'nullable|string',
            'dynamic_fields' => 'nullable|array',
        ];
    }
}
