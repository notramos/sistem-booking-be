<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Http\Response\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah'],
            ]);
        }

        if (! $user->is_active) {
            return $this->error('Akun Anda telah dinonaktifkan', 403);
        }

        $user->update(['last_login_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->success([
            'user' => new UserResource($user->load('roles')),
        ], 'Login berhasil');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Logout berhasil');
    }

    public function user(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()->load('roles.permissions')));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? $this->success(null, 'Link reset password telah dikirim ke email Anda')
            : $this->error('Gagal mengirim link reset password', 400);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->success(null, 'Password berhasil direset')
            : $this->error('Gagal mereset password', 400);
    }
}
