<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadRoomImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_primary' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'image' => 'gambar',
            'is_primary' => 'gambar utama',
        ];
    }
}
