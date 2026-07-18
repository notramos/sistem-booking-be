<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterCompleteRequest;
use App\Http\Requests\Api\RegisterStartRequest;
use App\Http\Requests\Api\RegisterVerifyRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Http\Response\ApiResponse;
use App\Models\RegistrationVerification;
use App\Models\User;
use App\Notifications\RegistrationVerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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

        $this->logUserIn($request, $user);

        return $this->success([
            'user' => new UserResource($user->load('roles')),
        ], 'Login berhasil');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        cookie()->queue(cookie()->forget('auth_hint', config('session.path'), config('session.domain')));

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

    /**
     * Registrasi mandiri jemaat, tahap 1: kirim kode OTP 6 digit ke email.
     * Belum membuat baris User apa pun — cuma menyimpan hash kode di
     * registration_verifications sampai pemohon selesai verifikasi + isi profil.
     */
    public function registerStart(RegisterStartRequest $request): JsonResponse
    {
        $email = $request->email;
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        RegistrationVerification::updateOrCreate(
            ['email' => $email],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(15),
                'verified_at' => null,
                'verification_token' => null,
            ]
        );

        Notification::route('mail', $email)->notify(new RegistrationVerificationCode($code));

        return $this->success(null, 'Kode verifikasi telah dikirim ke email Anda');
    }

    /**
     * Registrasi mandiri jemaat, tahap 2: cocokkan kode OTP. Kalau benar, terbitkan
     * verification_token acak (dikembalikan ke frontend) sebagai bukti kepemilikan
     * email untuk tahap 3 — pemohon belum punya password/sesi di titik ini.
     */
    public function registerVerify(RegisterVerifyRequest $request): JsonResponse
    {
        $verification = RegistrationVerification::where('email', $request->email)->first();

        if (! $verification || $verification->expires_at->isPast()) {
            return $this->error('Kode verifikasi tidak valid atau sudah kedaluwarsa', 422);
        }

        if ($verification->attempts >= 5) {
            return $this->error('Terlalu banyak percobaan salah, silakan minta kode baru', 429);
        }

        if (! Hash::check($request->code, $verification->code_hash)) {
            $verification->increment('attempts');

            return $this->error('Kode verifikasi salah', 422);
        }

        $token = Str::random(64);
        $verification->update([
            'verified_at' => now(),
            'verification_token' => $token,
        ]);

        return $this->success(['verification_token' => $token], 'Email berhasil diverifikasi');
    }

    /**
     * Registrasi mandiri jemaat, tahap 3 (final): buat akun User sungguhan, role
     * jemaat, langsung login-kan. verification_token dari tahap 2 wajib cocok &
     * masih berlaku 30 menit sejak verifikasi — mencegah orang lain menyelesaikan
     * registrasi atas nama email yang bukan miliknya.
     */
    public function registerComplete(RegisterCompleteRequest $request): JsonResponse
    {
        $verification = RegistrationVerification::where('email', $request->email)
            ->where('verification_token', $request->verification_token)
            ->first();

        if (! $verification || ! $verification->verified_at || $verification->verified_at->lt(now()->subMinutes(30))) {
            return $this->error('Sesi verifikasi tidak valid atau sudah kedaluwarsa, silakan ulangi dari awal', 422);
        }

        if (User::where('email', $request->email)->exists()) {
            return $this->error('Email ini sudah terdaftar', 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'wilayah_id' => $request->wilayah_id,
            'lingkungan_id' => $request->lingkungan_id,
            'parish' => $request->parish,
            'is_active' => true,
        ]);
        // email_verified_at sengaja tidak di $fillable (menghindari mass-assignment dari
        // input mana pun) — di-set eksplisit di sini karena baris ini cuma tercapai setelah
        // OTP terverifikasi.
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole('jemaat');

        $verification->delete();

        $this->logUserIn($request, $user);

        return $this->created([
            'user' => new UserResource($user->load('roles')),
        ], 'Registrasi berhasil');
    }

    /**
     * Sama seperti akhir login(): mulai sesi Sanctum + cookie auth_hint. Dipakai
     * ulang oleh login() dan registerComplete() supaya keduanya konsisten.
     */
    private function logUserIn(Request $request, User $user): void
    {
        Auth::login($user);
        $request->session()->regenerate();

        // Cookie sesi Sanctum selalu ada (bahkan untuk sesi anonim), jadi middleware.ts
        // frontend tak bisa memakainya untuk deteksi status login — cookie penanda
        // terpisah ini yang eksplisit di-set saat login & dihapus saat logout.
        cookie()->queue(cookie(
            'auth_hint', '1', config('session.lifetime'),
            config('session.path'), config('session.domain'),
            config('session.secure'), true, false, config('session.same_site')
        ));
    }
}
