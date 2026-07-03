<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Http\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        return $this->success(new UserResource(auth()->user()->load('roles')));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $user->update($request->validated());

        return $this->success(new UserResource($user), 'Profil berhasil diperbarui');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error('Password saat ini salah', 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return $this->success(null, 'Password berhasil diubah');
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = auth()->user();
        $path = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => $path]);

        return $this->success(['avatar_url' => asset('storage/'.$path)], 'Avatar berhasil diunggah');
    }
}
