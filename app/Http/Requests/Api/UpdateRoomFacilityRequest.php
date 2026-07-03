<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255|unique:room_facilities,name,'.$this->route('roomFacility'),
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama fasilitas',
            'icon' => 'ikon',
            'is_active' => 'status aktif',
        ];
    }
}
