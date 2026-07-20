<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Kirim kode OTP registrasi lewat WhatsApp via Fonnte (https://fonnte.com).
 * Panggilan HTTP langsung (bukan Notification class Laravel) karena Fonnte cuma
 * satu endpoint POST sederhana — tidak ada channel 'mail'-setara untuk WA.
 */
class WhatsAppOtpService
{
    private const FONNTE_ENDPOINT = 'https://api.fonnte.com/send';

    /**
     * Normalisasi nomor HP Indonesia ke format 62xxxxxxxxxx (dipakai Fonnte & disimpan di DB),
     * menerima input 08xxx / +62xxx / 62xxx.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return '62'.$digits;
    }

    public function send(string $phone, string $code): void
    {
        $target = $this->normalizePhone($phone);
        $token = config('services.fonnte.token');

        if (! $token) {
            // Dev/testing tanpa token: catat ke log alih-alih gagal keras, supaya alur
            // registrasi tetap bisa diuji lewat tabel registration_verifications langsung.
            Log::warning('FONNTE_TOKEN kosong — OTP tidak dikirim, cuma dicatat di log.', [
                'target' => $target,
                'code' => $code,
            ]);

            return;
        }

        $response = Http::withHeaders(['Authorization' => $token])
            ->asForm()
            ->post(self::FONNTE_ENDPOINT, [
                'target' => $target,
                'message' => "Kode verifikasi pendaftaran E-Albertus Anda: {$code}\n\nBerlaku 15 menit. Jangan bagikan kode ini ke siapa pun.",
            ]);

        if ($response->failed()) {
            Log::error('Gagal mengirim OTP via Fonnte', [
                'target' => $target,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Gagal mengirim kode verifikasi ke WhatsApp');
        }
    }
}
