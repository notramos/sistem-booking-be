<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLingkunganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wilayah_id' => 'required|uuid|exists:wilayah,id',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('lingkungan', 'name')->where(fn ($query) => $query->where('wilayah_id', $this->input('wilayah_id'))),
            ],
            'area' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function attributes(): array
    {
        return ['wilayah_id' => 'wilayah', 'name' => 'nama lingkungan', 'area' => 'area lingkungan'];
    }
}
