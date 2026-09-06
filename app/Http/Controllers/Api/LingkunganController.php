<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLingkunganRequest;
use App\Http\Requests\Api\UpdateLingkunganRequest;
use App\Http\Response\ApiResponse;
use App\Models\Lingkungan;
use Illuminate\Http\JsonResponse;

class LingkunganController extends Controller
{
    use ApiResponse;

    public function store(StoreLingkunganRequest $request): JsonResponse
    {
        $lingkungan = Lingkungan::create($request->validated());

        return $this->created($this->payload($lingkungan), 'Lingkungan berhasil ditambahkan');
    }

    public function show(Lingkungan $lingkungan): JsonResponse
    {
        return $this->success($this->payload($lingkungan));
    }

    public function update(UpdateLingkunganRequest $request, Lingkungan $lingkungan): JsonResponse
    {
        $lingkungan->update($request->validated());

        return $this->success($this->payload($lingkungan), 'Lingkungan berhasil diperbarui');
    }

    public function destroy(Lingkungan $lingkungan): JsonResponse
    {
        if ($lingkungan->users()->exists()) {
            return $this->error('Lingkungan tidak dapat dihapus karena masih dipakai oleh profil pengguna', 422);
        }

        $lingkungan->delete();

        return $this->success(null, 'Lingkungan berhasil dihapus');
    }

    private function payload(Lingkungan $lingkungan): array
    {
        $lingkungan->load('wilayah')->loadCount('users');

        return [
            'id' => $lingkungan->id,
            'wilayah_id' => $lingkungan->wilayah_id,
            'wilayah_name' => $lingkungan->wilayah->name,
            'name' => $lingkungan->name,
            'area' => $lingkungan->area,
            'is_active' => $lingkungan->is_active,
            'users_count' => $lingkungan->users_count,
        ];
    }
}
