<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
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
            'room_id' => 'sometimes|exists:rooms,id',
            'booking_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hours = config('booking.operating_hours');
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if ($start && $start < $hours['open']) {
                $validator->errors()->add('start_time', "Waktu mulai tidak boleh sebelum jam operasional ({$hours['open']}).");
            }
            if ($end && $end > $hours['close']) {
                $validator->errors()->add('end_time', "Waktu selesai tidak boleh melewati jam operasional ({$hours['close']}).");
            }
        });
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
