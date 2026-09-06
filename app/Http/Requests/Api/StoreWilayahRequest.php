<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreWilayahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:wilayah,name',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama wilayah', 'is_active' => 'status wilayah'];
    }
}
