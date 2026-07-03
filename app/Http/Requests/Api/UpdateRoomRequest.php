<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:room_categories,id',
            'description' => 'nullable|string',
            'capacity' => 'sometimes|integer|min:1',
            'floor' => 'nullable|string|max:50',
            'building' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,maintenance,unavailable',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:room_facilities,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama ruangan',
            'category_id' => 'kategori',
            'description' => 'deskripsi',
            'capacity' => 'kapasitas',
            'floor' => 'lantai',
            'building' => 'gedung',
            'status' => 'status',
            'facilities' => 'fasilitas',
        ];
    }
}
