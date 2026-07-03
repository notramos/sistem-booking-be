<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:room_facilities,name',
            'icon' => 'nullable|string|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama fasilitas',
            'icon' => 'ikon',
        ];
    }
}
