<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'purpose_type' => 'nullable|in:ibadah,acara_keluarga,latihan_musik,pembinaan,rapat,seminar,publik',
            'expected_attendees' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'room_id' => 'ruangan',
            'title' => 'judul',
            'description' => 'deskripsi',
            'booking_date' => 'tanggal booking',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'purpose_type' => 'tujuan penggunaan',
            'expected_attendees' => 'perkiraan peserta',
            'notes' => 'catatan',
        ];
    }
}
