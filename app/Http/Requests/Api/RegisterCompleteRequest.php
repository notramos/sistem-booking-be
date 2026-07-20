<?php

namespace App\Http\Requests\Api;

use App\Models\Lingkungan;
use App\Services\WhatsAppOtpService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RegisterCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => app(WhatsAppOtpService::class)->normalizePhone($this->phone),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'verification_token' => 'required|string',
            'email' => 'required|email|max:255|unique:users,email',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'wilayah_id' => 'nullable|exists:wilayah,id',
            'lingkungan_id' => 'nullable|exists:lingkungan,id',
            'parish' => 'nullable|string|max:255',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $wilayahId = $this->input('wilayah_id');
            $lingkunganId = $this->input('lingkungan_id');

            if ($lingkunganId && $wilayahId) {
                $lingkungan = Lingkungan::find($lingkunganId);
                if ($lingkungan && $lingkungan->wilayah_id !== $wilayahId) {
                    $validator->errors()->add('lingkungan_id', 'Lingkungan yang dipilih tidak sesuai dengan wilayah.');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'phone' => 'nomor WhatsApp',
            'email' => 'email',
            'verification_token' => 'token verifikasi',
            'name' => 'nama',
            'password' => 'password',
            'wilayah_id' => 'wilayah',
            'lingkungan_id' => 'lingkungan',
            'parish' => 'paroki',
        ];
    }
}
