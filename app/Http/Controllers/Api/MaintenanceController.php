<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Models\MaintenanceSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $schedules = MaintenanceSchedule::with(['room:id,name', 'creator:id,name'])
            ->when($request->room_id, fn($q, $r) => $q->where('room_id', $r))
            ->when($request->start_date, fn($q, $d) => $q->where('end_date', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->where('start_date', '<=', $d))
            ->orderBy('start_date')
            ->paginate($request->per_page ?? 15);

        return $this->paginated($schedules);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_all_day' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $schedule = MaintenanceSchedule::create($validated);

        return $this->created($schedule->load(['room:id,name', 'creator:id,name']), 'Jadwal perbaikan berhasil dibuat');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'is_all_day' => 'boolean',
        ]);

        $schedule->update($validated);

        return $this->success($schedule->load(['room:id,name', 'creator:id,name']), 'Jadwal perbaikan berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);
        $schedule->delete();

        return $this->success(null, 'Jadwal perbaikan berhasil dihapus');
    }
}
