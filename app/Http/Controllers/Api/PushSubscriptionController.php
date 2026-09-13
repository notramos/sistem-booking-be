<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePushSubscriptionRequest;
use App\Http\Response\ApiResponse;
use App\Notifications\PushTestNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushSubscriptionController extends Controller
{
    use ApiResponse;

    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Satu endpoint browser hanya boleh dimiliki satu akun aktif.
        DB::table('push_subscriptions')->where('endpoint', $data['endpoint'])->delete();
        $user->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['content_encoding'] ?? 'aes128gcm',
        );

        return $this->success(null, 'Perangkat berhasil didaftarkan untuk notifikasi');
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['required', 'url', 'max:2048']]);
        $request->user()->deletePushSubscription($request->string('endpoint')->toString());

        return $this->success(null, 'Perangkat dihapus dari notifikasi');
    }

    public function test(Request $request): JsonResponse
    {
        $request->user()->notify(new PushTestNotification());

        return $this->success(null, 'Notifikasi percobaan dikirim');
    }
}
