<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRoomFacilityRequest;
use App\Http\Requests\Api\UpdateRoomFacilityRequest;
use App\Http\Resources\RoomFacilityResource;
use App\Http\Response\ApiResponse;
use App\Models\RoomFacility;
use Illuminate\Http\JsonResponse;

class RoomFacilityController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(RoomFacilityResource::collection(RoomFacility::where('is_active', true)->get()));
    }

    public function store(StoreRoomFacilityRequest $request): JsonResponse
    {
        $facility = RoomFacility::create($request->validated());

        return $this->created(new RoomFacilityResource($facility), 'Fasilitas berhasil dibuat');
    }

    public function update(UpdateRoomFacilityRequest $request, string $id): JsonResponse
    {
        $facility = RoomFacility::findOrFail($id);
        $facility->update($request->validated());

        return $this->success(new RoomFacilityResource($facility), 'Fasilitas berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $facility = RoomFacility::findOrFail($id);
        $facility->rooms()->detach();
        $facility->delete();

        return $this->success(null, 'Fasilitas berhasil dihapus');
    }
}
