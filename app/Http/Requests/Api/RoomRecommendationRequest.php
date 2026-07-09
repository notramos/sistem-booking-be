<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RoomRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'attendees' => 'required|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'date' => 'tanggal',
            'attendees' => 'jumlah peserta',
        ];
    }
}
