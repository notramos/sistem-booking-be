<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLingkunganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lingkungan = $this->route('lingkungan');
        $wilayahId = $this->input('wilayah_id', $lingkungan?->wilayah_id);

        return [
            'wilayah_id' => 'sometimes|required|uuid|exists:wilayah,id',
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('lingkungan', 'name')->where(fn ($query) => $query->where('wilayah_id', $wilayahId))->ignore($lingkungan),
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
