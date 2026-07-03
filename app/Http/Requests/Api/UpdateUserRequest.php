<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$this->route('user'),
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'nip' => 'nullable|string|max:50|unique:users,nip,'.$this->route('user'),
            'is_active' => 'boolean',
            'role' => 'sometimes|in:admin,sekretariat,jemaat',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'department' => 'departemen',
            'position' => 'jabatan',
            'nip' => 'NIP',
            'is_active' => 'status aktif',
            'role' => 'peran',
        ];
    }
}
