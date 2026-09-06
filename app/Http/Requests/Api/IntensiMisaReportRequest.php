<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IntensiMisaReportRequest extends FormRequest
{
    // Route middleware distinguishes view permission from export permission.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            // Accept historical times such as 09:30 when consulting archives.
            'schedule' => 'nullable|date_format:H:i',
            'status' => 'nullable|in:pending,approved,rejected',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
