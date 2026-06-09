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

        Mail::to($user->email)->queue(new OtpMail($user, $plain));
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
