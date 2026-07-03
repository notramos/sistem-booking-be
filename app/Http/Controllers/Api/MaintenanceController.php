<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMaintenanceRequest;
use App\Http\Requests\Api\UpdateMaintenanceRequest;
use App\Http\Resources\MaintenanceScheduleResource;
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
            ->when($request->room_id, fn ($q, $r) => $q->where('room_id', $r))
            ->when($request->start_date, fn ($q, $d) => $q->where('end_date', '>=', $d))
            ->when($request->end_date, fn ($q, $d) => $q->where('start_date', '<=', $d))
            ->orderBy('start_date')
            ->paginate($request->per_page ?? 15);

        return $this->paginated(MaintenanceScheduleResource::collection($schedules));
    }

    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();
        $schedule = MaintenanceSchedule::create($validated);

        return $this->created(
            new MaintenanceScheduleResource($schedule->load(['room:id,name', 'creator:id,name'])),
            'Jadwal perbaikan berhasil dibuat'
        );
    }

    public function update(UpdateMaintenanceRequest $request, string $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);
        $schedule->update($request->validated());

        return $this->success(
            new MaintenanceScheduleResource($schedule->load(['room:id,name', 'creator:id,name'])),
            'Jadwal perbaikan berhasil diperbarui'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::findOrFail($id);
        $schedule->delete();

        return $this->success(null, 'Jadwal perbaikan berhasil dihapus');
    }
}
