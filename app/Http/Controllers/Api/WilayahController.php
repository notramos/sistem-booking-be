<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWilayahRequest;
use App\Http\Requests\Api\UpdateWilayahRequest;
use App\Http\Resources\WilayahResource;
use App\Http\Response\ApiResponse;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;

class WilayahController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $wilayah = Wilayah::active()
            ->with(['lingkungan' => fn ($q) => $q->active()->withCount('users')->orderBy('name')])
            ->withCount(['lingkungan' => fn ($q) => $q->active(), 'users'])
            ->orderBy('name')
            ->get();

        return $this->success(WilayahResource::collection($wilayah));
    }

    public function show(Wilayah $wilayah): JsonResponse
    {
        $wilayah->load(['lingkungan' => fn ($q) => $q->withCount('users')->orderBy('name')])
            ->loadCount(['lingkungan', 'users']);

        return $this->success(new WilayahResource($wilayah));
    }

    public function store(StoreWilayahRequest $request): JsonResponse
    {
        $wilayah = Wilayah::create($request->validated());

        return $this->created(new WilayahResource($wilayah->load('lingkungan')->loadCount(['lingkungan', 'users'])), 'Wilayah berhasil ditambahkan');
    }

    public function update(UpdateWilayahRequest $request, Wilayah $wilayah): JsonResponse
    {
        $wilayah->update($request->validated());

        return $this->success(new WilayahResource($wilayah->load('lingkungan')->loadCount(['lingkungan', 'users'])), 'Wilayah berhasil diperbarui');
    }

    public function destroy(Wilayah $wilayah): JsonResponse
    {
        $lingkunganIds = $wilayah->lingkungan()->pluck('id');
        $linkedUsers = User::query()
            ->where('wilayah_id', $wilayah->id)
            ->orWhereIn('lingkungan_id', $lingkunganIds)
            ->count();

        if ($linkedUsers > 0) {
            return $this->error("Wilayah tidak dapat dihapus karena masih dipakai oleh {$linkedUsers} profil pengguna", 422);
        }

        $wilayah->delete();

        return $this->success(null, 'Wilayah dan seluruh lingkungan di dalamnya berhasil dihapus');
    }
}
