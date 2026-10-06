<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApproveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:500',
            'service_date' => 'nullable|date|after_or_equal:today',
            'service_time' => 'nullable|date_format:H:i',
            'service_place' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'notes' => 'catatan',
        ];
    }
}
