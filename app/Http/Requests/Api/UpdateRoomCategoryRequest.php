<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255|unique:room_categories,name,'.$this->route('roomCategory'),
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'description' => 'deskripsi',
            'is_active' => 'status aktif',
        ];
    }
}
