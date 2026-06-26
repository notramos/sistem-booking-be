<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        return $this->success(auth()->user()->load('roles'));
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        return $this->success($user, 'Profil berhasil diperbarui');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Password saat ini salah', 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return $this->success(null, 'Password berhasil diubah');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:1024',
        ]);

        $user = auth()->user();
        $path = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => $path]);

        return $this->success(['avatar_url' => asset('storage/' . $path)], 'Avatar berhasil diunggah');
    }
}
