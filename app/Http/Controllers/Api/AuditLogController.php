<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::with('user:id,name')
            ->when($request->user_id, fn($q, $u) => $q->where('user_id', $u))
            ->when($request->action, fn($q, $a) => $q->where('action', $a))
            ->when($request->entity_type, fn($q, $t) => $q->where('entity_type', $t))
            ->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 25);

        return $this->paginated($logs);
    }
}
