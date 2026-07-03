<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'description' => 'deskripsi',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'notes' => 'catatan',
        ];
    }
}
