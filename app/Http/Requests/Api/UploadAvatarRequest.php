<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:1024',
        ];
    }

    public function attributes(): array
    {
        return [
            'avatar' => 'avatar',
        ];
    }
}
