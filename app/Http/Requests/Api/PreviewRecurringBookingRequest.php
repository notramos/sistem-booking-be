<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PreviewRecurringBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $minDate = now()->addDays(config('booking.min_advance_days'))->toDateString();
        // Batas jaga-jaga (bukan aturan H+30 booking biasa) supaya tanggal pertama tidak
        // asal jauh — booking rutin memang boleh melampaui H+30 untuk occurrence berikutnya.
        $maxFirstDate = now()->addDays(400)->toDateString();

        return [
            'room_id' => 'required|exists:rooms,id',
            'first_date' => "required|date|after_or_equal:{$minDate}|before_or_equal:{$maxFirstDate}",
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'pattern' => 'required|in:weekly,monthly',
            'duration_months' => 'required|integer|in:1,3,6,12',
        ];
    }

    public function messages(): array
    {
        $minDate = now()->locale('id')->addDays(config('booking.min_advance_days'))->translatedFormat('d F Y');

        return [
            'first_date.after_or_equal' => "Tanggal pertama harus minimal {$minDate} (H+".config('booking.min_advance_days').' dari hari ini).',
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
            'room_id' => 'ruangan',
            'first_date' => 'tanggal pertama',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'pattern' => 'pola pengulangan',
            'duration_months' => 'durasi',
        ];
    }
}
