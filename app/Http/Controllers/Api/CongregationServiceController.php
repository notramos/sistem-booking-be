<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApproveActionRequest;
use App\Http\Requests\Api\RejectActionRequest;
use App\Http\Requests\Api\StoreCongregationServiceRequest;
use App\Http\Requests\Api\StoreManualCongregationServiceRequest;
use App\Http\Requests\Api\AdminUpdateCongregationServiceRequest;
use App\Http\Resources\CongregationServiceResource;
use App\Http\Response\ApiResponse;
use App\Models\CongregationService;
use App\Models\User;
use App\Notifications\CongregationServiceCreated;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Support\Pagination;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CongregationServiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuditService $auditService,
        private NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = CongregationService::with('user:id,name,email');

        if (! auth()->user()?->can('bookings.approve')) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Antrean pending diproses FIFO agar adil. Daftar riwayat tetap terbaru
        // di atas supaya mudah melihat perubahan terakhir.
        if ($request->input('status') === 'pending') {
            $query->orderBy('created_at', 'asc')->orderBy('service_date', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        return $this->paginated(CongregationServiceResource::collection($query->paginate(Pagination::perPage($request->per_page, 15))));
    }

    public function show(string $id): JsonResponse
    {
        $service = CongregationService::with('user:id,name,email')->findOrFail($id);

        if ($service->user_id !== auth()->id() && ! auth()->user()?->can('bookings.approve')) {
            return $this->error('Anda tidak memiliki akses', 403);
        }

        return $this->success(new CongregationServiceResource($service));
    }

    public function store(StoreCongregationServiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $service = CongregationService::create([
            'user_id' => auth()->id(),
            'service_type' => $validated['service_type'],
            'applicant_name' => $validated['applicant_name'],
            'applicant_gender' => $validated['applicant_gender'] ?? null,
            'baptismal_name' => $validated['baptismal_name'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'contact' => $validated['contact'],
            'phone' => $validated['phone'] ?? null,
            'mobile_phone' => $validated['mobile_phone'] ?? null,
            'neighborhood' => $validated['neighborhood'] ?? null,
            'region' => $validated['region'] ?? null,
            'parish' => $validated['parish'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'father_religion' => $validated['father_religion'] ?? null,
            'mother_name' => $validated['mother_name'] ?? null,
            'mother_religion' => $validated['mother_religion'] ?? null,
            'school' => $validated['school'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'family_card_number' => $validated['family_card_number'] ?? null,
            'service_date' => $validated['service_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'dynamic_fields' => $validated['dynamic_fields'] ?? [],
        ]);

        $this->auditService->log('congregation_service_created', $service);

        $sekretariat = User::role('sekretariat')->get();
        if ($sekretariat->isNotEmpty()) {
            Notification::send($sekretariat, new CongregationServiceCreated($service));
        }

        return $this->created(
            new CongregationServiceResource($service->load('user:id,name,email')),
            'Permohonan pelayanan umat berhasil dikirim'
        );
    }

    /**
     * Entri manual oleh staf (mis. backfill permohonan intensi misa yang datang
     * lewat kertas/WA) — beda dari store() biasa: user_id-nya staf yang input
     * (bukan otomatis dari akun pemohon), status bisa langsung diisi (tidak
     * dipaksa 'pending'), dan tidak mengirim notifikasi "permohonan baru" ke
     * sekretariat karena stafnya sendiri yang input. Pola sama seperti
     * BookingController::storeManual().
     */
    public function storeManual(StoreManualCongregationServiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $service = CongregationService::create([
            'user_id' => $request->user()->id,
            'service_type' => $validated['service_type'],
            'applicant_name' => $validated['applicant_name'],
            'applicant_gender' => $validated['applicant_gender'] ?? null,
            'baptismal_name' => $validated['baptismal_name'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'contact' => $validated['contact'],
            'phone' => $validated['phone'] ?? null,
            'mobile_phone' => $validated['mobile_phone'] ?? null,
            'neighborhood' => $validated['neighborhood'] ?? null,
            'region' => $validated['region'] ?? null,
            'parish' => $validated['parish'] ?? null,
            'father_name' => $validated['father_name'] ?? null,
            'father_religion' => $validated['father_religion'] ?? null,
            'mother_name' => $validated['mother_name'] ?? null,
            'mother_religion' => $validated['mother_religion'] ?? null,
            'school' => $validated['school'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'family_card_number' => $validated['family_card_number'] ?? null,
            'service_date' => $validated['service_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'received_at' => isset($validated['received_at'])
                ? Carbon::parse($validated['received_at'])->setTimezone(config('app.timezone'))
                : now(),
            'dynamic_fields' => $validated['dynamic_fields'] ?? [],
        ]);

        $this->auditService->log('congregation_service_created_manual', $service);

        return $this->created(
            new CongregationServiceResource($service->load('user:id,name,email')),
            'Permohonan pelayanan umat berhasil ditambahkan'
        );
    }

    public function approve(ApproveActionRequest $request, string $id): JsonResponse
    {
        $service = CongregationService::findOrFail($id);

        if ($service->status !== 'pending') {
            return $this->error('Permohonan sudah diproses', 422);
        }

        $service->update([
            'status' => 'approved',
            'notes' => $request->notes ?? $service->notes,
        ]);

        $this->auditService->log('congregation_service_approved', $service);
        $this->notificationService->congregationServiceApproved($service);

        return $this->success(
            new CongregationServiceResource($service->load('user:id,name,email')),
            'Permohonan pelayanan disetujui'
        );
    }

    public function reject(RejectActionRequest $request, string $id): JsonResponse
    {
        $service = CongregationService::findOrFail($id);

        if ($service->status !== 'pending') {
            return $this->error('Permohonan sudah diproses', 422);
        }

        $service->update([
            'status' => 'rejected',
            'notes' => $request->reason,
        ]);

        $this->auditService->log('congregation_service_rejected', $service);
        $this->notificationService->congregationServiceRejected($service);

        return $this->success(
            new CongregationServiceResource($service->load('user:id,name,email')),
            'Permohonan pelayanan ditolak'
        );
    }

    public function adminUpdate(AdminUpdateCongregationServiceRequest $request, string $id): JsonResponse
    {
        $service = CongregationService::findOrFail($id);
        $service->update($request->validated());
        return $this->success(new CongregationServiceResource($service->load('user:id,name,email')), 'Permohonan berhasil diperbarui');
    }

    public function destroy(string $id): JsonResponse
    {
        CongregationService::findOrFail($id)->delete();
        return $this->success(null, 'Permohonan berhasil dihapus');
    }
}
