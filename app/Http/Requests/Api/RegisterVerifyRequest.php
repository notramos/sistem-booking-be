<?php

namespace App\Http\Requests\Api;

use App\Services\WhatsAppOtpService;
use Illuminate\Foundation\Http\FormRequest;

class RegisterVerifyRequest extends FormRequest
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
            'code' => 'required|digits:6',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'nomor WhatsApp',
            'code' => 'kode verifikasi',
        ];
    }
}
