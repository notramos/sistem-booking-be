<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'username' => 'sometimes|nullable|string|max:100|alpha_dash|unique:users,username,'.$this->route('user'),
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'nip' => 'nullable|string|max:50|unique:users,nip,'.$this->route('user'),
            'is_active' => 'boolean',
            'role' => ['sometimes', Rule::in(['umat', 'ketua_lingkungan', 'sekretariat', 'p2', 'pastor', 'it_admin'])],
            'wilayah_id' => ['nullable', 'uuid', 'exists:wilayah,id'],
            'lingkungan_id' => [
                'nullable',
                'uuid',
                Rule::exists('lingkungan', 'id')->where(
                    fn ($query) => $query->where('wilayah_id', $this->input('wilayah_id'))
                ),
            ],
            'parish' => 'nullable|string|max:255',
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
            'wilayah_id' => 'wilayah',
            'lingkungan_id' => 'lingkungan',
            'parish' => 'paroki',
        ];
    }
}
