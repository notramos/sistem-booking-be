<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequest extends FormRequest
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
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_all_day' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'description' => 'deskripsi',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'is_all_day' => 'sepanjang hari',
        ];
    }
}
