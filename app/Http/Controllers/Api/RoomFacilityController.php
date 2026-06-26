<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Models\RoomFacility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomFacilityController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(RoomFacility::where('is_active', true)->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:room_facilities,name',
            'icon' => 'nullable|string|max:100',
        ]);

        $facility = RoomFacility::create($validated);

        return $this->created($facility, 'Fasilitas berhasil dibuat');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $facility = RoomFacility::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:room_facilities,name,' . $id,
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $facility->update($validated);

        return $this->success($facility, 'Fasilitas berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $facility = RoomFacility::findOrFail($id);
        $facility->rooms()->detach();
        $facility->delete();

        return $this->success(null, 'Fasilitas berhasil dihapus');
    }
}
