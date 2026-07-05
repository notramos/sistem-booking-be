<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WilayahResource;
use App\Http\Response\ApiResponse;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;

class WilayahController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $wilayah = Wilayah::active()
            ->with(['lingkungan' => fn ($q) => $q->active()->orderBy('name')])
            ->orderBy('name')
            ->get();

        return $this->success(WilayahResource::collection($wilayah));
    }
}
