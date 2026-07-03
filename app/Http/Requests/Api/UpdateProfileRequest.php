<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nomor telepon',
            'department' => 'departemen',
            'position' => 'jabatan',
        ];
    }
}
