<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Response\ApiResponse;
use App\Support\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(Pagination::perPage($request->per_page, 20));

        return $this->paginated(NotificationResource::collection($notifications));
    }

    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->unreadNotifications()->count();

        return $this->success(['unread_count' => $count]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(null, 'Notifikasi ditandai telah dibaca');
    }

    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(null, 'Semua notifikasi ditandai telah dibaca');
    }
}
