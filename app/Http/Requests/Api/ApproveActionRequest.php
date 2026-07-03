<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApproveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'notes' => 'catatan',
        ];
    }
}
