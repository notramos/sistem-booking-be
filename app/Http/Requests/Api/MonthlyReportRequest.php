<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => 'required|digits:4',
            'month' => 'required|digits:2|between:01,12',
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => 'tahun',
            'month' => 'bulan',
        ];
    }
}
