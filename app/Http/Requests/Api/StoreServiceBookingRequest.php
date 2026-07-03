<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'service_type' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
            'equipment' => 'nullable|array',
            'equipment.*' => 'string',
            'other_equipment' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'dynamic_fields' => 'nullable|array',
        ];
    }

    public function attributes(): array
    {
        return [
            'room_id' => 'ruangan',
            'booking_date' => 'tanggal',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'service_type' => 'jenis pelayanan',
            'contact' => 'kontak',
            'equipment' => 'perlengkapan',
            'other_equipment' => 'perlengkapan lainnya',
            'notes' => 'catatan',
        ];
    }
}
