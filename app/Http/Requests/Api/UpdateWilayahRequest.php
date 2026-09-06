<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWilayahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('wilayah', 'name')->ignore($this->route('wilayah'))],
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama wilayah', 'is_active' => 'status wilayah'];
    }
}
