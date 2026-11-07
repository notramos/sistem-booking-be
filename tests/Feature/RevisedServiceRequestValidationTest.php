<?php

namespace Tests\Feature;

use App\Http\Requests\Api\StoreCongregationServiceRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RevisedServiceRequestValidationTest extends TestCase
{
    private function validateRequest(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $request = StoreCongregationServiceRequest::create('/api/congregation-services', 'POST', $data);

        return Validator::make($data, $request->rules());
    }

    public function test_misa_lingkungan_requires_kaling_and_accepts_the_four_current_kinds(): void
    {
        $base = [
            'service_type' => 'permohonan_misa',
            'applicant_name' => 'Ketua Lingkungan',
            'contact' => '081234567890',
            'region' => 'Wilayah 1',
            'neighborhood' => 'Lingkungan 1',
            'address' => 'Alamat pemohon',
            'description' => 'Permohonan misa',
            'dynamic_fields' => [
                'tanggal_misa_diminta' => now()->addWeek()->toDateString(),
                'jam_misa_diminta' => '18:30',
                'tempat_misa' => 'Balai lingkungan',
            ],
        ];

        foreach (['arwah', 'requiem', 'syukur'] as $kind) {
            $data = $base;
            $data['dynamic_fields']['jenis_misa'] = $kind;
            $this->assertFalse($this->validateRequest($data)->fails(), $kind);
        }

        $lingkungan = $base;
        $lingkungan['dynamic_fields']['jenis_misa'] = 'lingkungan';
        $this->assertArrayHasKey('dynamic_fields.nama_kaling', $this->validateRequest($lingkungan)->errors()->toArray());
        $lingkungan['dynamic_fields']['nama_kaling'] = 'Pak Budi';
        $this->assertFalse($this->validateRequest($lingkungan)->fails());

        $obsolete = $base;
        $obsolete['dynamic_fields']['jenis_misa'] = 'rumah';
        $this->assertArrayHasKey('dynamic_fields.jenis_misa', $this->validateRequest($obsolete)->errors()->toArray());
    }

    public function test_consultation_no_longer_requires_chronology_or_request_nature(): void
    {
        $data = [
            'service_type' => 'konsultasi_romo',
            'applicant_name' => 'Pemohon',
            'contact' => '081234567890',
            'region' => 'Wilayah 1',
            'neighborhood' => 'Lingkungan 1',
            'dynamic_fields' => [
                'topik_konsultasi' => 'pra_pernikahan',
                'tanggal_diminta' => now()->addWeek()->toDateString(),
                'waktu_preferensi' => 'pagi',
            ],
        ];

        $this->assertFalse($this->validateRequest($data)->fails());
    }

    public function test_anointing_requires_minister_recipient_and_place(): void
    {
        $data = [
            'service_type' => 'sakramen_minyak_suci',
            'applicant_name' => 'Keluarga Pasien',
            'contact' => '081234567890',
            'address' => 'Alamat pelayanan',
            'description' => 'Kondisi pasien',
            'dynamic_fields' => [
                'hubungan_keluarga' => 'Anak',
                'urgensi' => 'biasa',
                'reception_date' => now()->addWeek()->toDateString(),
            ],
        ];

        $errors = $this->validateRequest($data)->errors()->toArray();
        $this->assertArrayHasKey('dynamic_fields.pemberi_sakramen', $errors);
        $this->assertArrayHasKey('dynamic_fields.penerima_sakramen', $errors);
        $this->assertArrayHasKey('dynamic_fields.reception_place', $errors);

        $data['dynamic_fields']['pemberi_sakramen'] = 'Romo A';
        $data['dynamic_fields']['penerima_sakramen'] = 'Ibu B';
        $data['dynamic_fields']['reception_place'] = 'Rumah';
        $this->assertFalse($this->validateRequest($data)->fails());
    }
}
