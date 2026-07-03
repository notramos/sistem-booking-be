<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_all_day' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'room_id' => 'ruangan',
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
