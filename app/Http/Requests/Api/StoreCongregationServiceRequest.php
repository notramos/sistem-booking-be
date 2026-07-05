<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCongregationServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serviceTypes = array_keys(config('congregation-services.types', []));
        $religions = config('congregation-services.religion_options', []);
        $genders = config('congregation-services.gender_options', []);
        $maritalStatuses = config('congregation-services.marital_status_options', []);

        return [
            'service_type' => ['required', 'string', Rule::in($serviceTypes)],
            'applicant_name' => 'required|string|max:255',
            'applicant_gender' => 'nullable|string|in:'.implode(',', $genders),
            'baptismal_name' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'contact' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile_phone' => 'nullable|string|max:20',
            'neighborhood' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'parish' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'father_religion' => 'nullable|string|in:'.implode(',', $religions),
            'mother_name' => 'nullable|string|max:255',
            'mother_religion' => 'nullable|string|in:'.implode(',', $religions),
            'school' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:50',
            'occupation' => 'nullable|string|max:255',
            'family_card_number' => 'nullable|string|max:50',
            'service_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
            'dynamic_fields' => 'nullable|array',
            'signature_pemohon' => ['nullable', 'string', 'regex:/^data:image\/png;base64,/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'service_type' => 'jenis pelayanan',
            'applicant_name' => 'nama lengkap',
            'applicant_gender' => 'jenis kelamin',
            'baptismal_name' => 'nama baptis',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'address' => 'alamat',
            'contact' => 'kontak',
            'phone' => 'telepon',
            'mobile_phone' => 'HP',
            'neighborhood' => 'lingkungan',
            'region' => 'wilayah',
            'parish' => 'paroki',
            'father_name' => 'nama ayah',
            'father_religion' => 'agama ayah',
            'mother_name' => 'nama ibu',
            'mother_religion' => 'agama ibu',
            'school' => 'sekolah',
            'grade' => 'kelas',
            'occupation' => 'pekerjaan',
            'family_card_number' => 'nomor KK',
            'service_date' => 'tanggal pelayanan',
            'description' => 'keterangan',
        ];
    }
}
