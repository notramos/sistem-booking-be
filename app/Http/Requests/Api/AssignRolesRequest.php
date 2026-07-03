<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AssignRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ];
    }

    public function attributes(): array
    {
        return [
            'roles' => 'peran',
        ];
    }
}
