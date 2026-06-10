<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const TTL_MINUTES = 10;

    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Generate a fresh 6-digit OTP for the given user & purpose,
     * invalidate any previous unused codes for the same pair,
     * and send the OTP email.
     */
    public function send(User $user, string $purpose = 'verify_email'): void
    {
        // Invalidate previous codes for this user+purpose so only one is valid at a time.
        EmailOtp::where('id_user', $user->id_user)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->delete();

        $plain = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtp::create([
            'id_user'    => $user->id_user,
            'email'      => $user->email,
            'code'       => Hash::make($plain),
            'purpose'    => $purpose,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        // Sinkron (bukan ->queue()) karena tidak ada queue worker — lihat OtpMail.
        Mail::to($user->email)->send(new OtpMail($user, $plain));
    }

    /**
     * Apakah user sudah boleh meminta kode baru? (cooldown anti-spam server-side)
     */
    public function canResend(User $user, string $purpose = 'verify_email'): bool
    {
        return $this->secondsUntilResend($user, $purpose) <= 0;
    }

    /**
     * Sisa detik sebelum tombol "kirim ulang" bisa ditekan lagi.
     * 0 berarti sudah boleh kirim ulang.
     */
    public function secondsUntilResend(User $user, string $purpose = 'verify_email'): int
    {
        $last = EmailOtp::where('id_user', $user->id_user)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if (! $last) {
            return 0;
        }

        $elapsed = $last->created_at->diffInSeconds(now());

        return (int) max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    /**
     * Verify the submitted code. Returns true and marks the OTP used on success.
     * Returns false if the code is wrong, expired, or already used.
     */
    public function verify(User $user, string $plain, string $purpose = 'verify_email'): bool
    {
        $otp = EmailOtp::where('id_user', $user->id_user)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp || ! Hash::check($plain, $otp->code)) {
            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }
}
