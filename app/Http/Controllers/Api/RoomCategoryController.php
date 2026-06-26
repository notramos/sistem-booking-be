<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Models\RoomCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomCategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(RoomCategory::where('is_active', true)->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:room_categories,name',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $category = RoomCategory::create($validated);

        return $this->created($category, 'Kategori berhasil dibuat');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = RoomCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:room_categories,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return $this->success($category, 'Kategori berhasil diperbarui');
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
