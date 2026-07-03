<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'nip' => 'nullable|string|max:50|unique:users,nip',
            'role' => 'required|in:admin,sekretariat,jemaat',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'password' => 'password',
            'phone' => 'nomor telepon',
            'department' => 'departemen',
            'position' => 'jabatan',
            'nip' => 'NIP',
            'role' => 'peran',
        ];
    }
}
