<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'email',
            'code' => 'kode verifikasi',
        ];
    }
}
