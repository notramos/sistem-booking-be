<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RoomAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'exclude_booking_id' => 'sometimes|uuid|exists:bookings,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'date' => 'tanggal',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
        ];
    }
}
