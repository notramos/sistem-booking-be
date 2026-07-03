<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:room_categories,name',
            'description' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'description' => 'deskripsi',
        ];
    }
}
