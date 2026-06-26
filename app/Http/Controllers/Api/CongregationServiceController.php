<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Models\CongregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CongregationServiceController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_type' => 'required|string|max:255',
            'applicant_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'contact' => 'required|string|max:255',
            'service_date' => 'nullable|date',
            'description' => 'required|string|max:2000',
            'dynamic_fields' => 'nullable|array',
        ]);

        $service = CongregationService::create([
            'user_id' => auth()->id(),
            'service_type' => $validated['service_type'],
            'applicant_name' => $validated['applicant_name'],
            'address' => $validated['address'],
            'contact' => $validated['contact'],
            'service_date' => $validated['service_date'],
            'description' => $validated['description'],
            'status' => 'pending',
            'dynamic_fields' => $validated['dynamic_fields'] ?? [],
        ]);

        return $this->created($service, 'Permohonan pelayanan umat berhasil dikirim');
    }
}
