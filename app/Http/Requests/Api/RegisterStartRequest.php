<?php

namespace App\Http\Requests\Api;

use App\Services\WhatsAppOtpService;
use Illuminate\Foundation\Http\FormRequest;

class RegisterStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi ke format 62xxx SEBELUM validasi `unique:users,phone` jalan —
     * kolom `users.phone` selalu disimpan ternormalisasi, jadi cek unique juga
     * harus dibandingkan dalam format yang sama (bukan format mentah 08xxx/+62xxx).
     */
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
            'phone' => 'required|regex:/^628[1-9][0-9]{6,11}$/|unique:users,phone',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'nomor WhatsApp',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Format nomor WhatsApp tidak valid. Gunakan format 08xxx atau +62xxx.',
            'phone.unique' => 'Nomor ini sudah terdaftar. Silakan masuk atau gunakan nomor lain.',
        ];
    }
}
