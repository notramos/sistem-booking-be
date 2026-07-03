<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRoomCategoryRequest;
use App\Http\Requests\Api\UpdateRoomCategoryRequest;
use App\Http\Resources\RoomCategoryResource;
use App\Http\Response\ApiResponse;
use App\Models\RoomCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RoomCategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(RoomCategoryResource::collection(RoomCategory::where('is_active', true)->get()));
    }

    public function store(StoreRoomCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        $category = RoomCategory::create($validated);

        return $this->created(new RoomCategoryResource($category), 'Kategori berhasil dibuat');
    }

    public function update(UpdateRoomCategoryRequest $request, string $id): JsonResponse
    {
        $category = RoomCategory::findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return $this->success(new RoomCategoryResource($category), 'Kategori berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $category = RoomCategory::findOrFail($id);

        if ($category->rooms()->exists()) {
            return $this->error('Kategori tidak dapat dihapus karena masih memiliki ruangan', 422);
        }

        $category->delete();

        return $this->success(null, 'Kategori berhasil dihapus');
    }
}
