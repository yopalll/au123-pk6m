<?php

namespace App\Http\Responses;

use App\Http\Controllers\Auth\OtpController;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Setelah registrasi, arahkan ke halaman verifikasi OTP bila ada kode
     * yang menunggu (di-set oleh SendOtpAfterRegistration). Kalau tidak ada
     * (mis. email sudah terverifikasi), pakai perilaku default ke dashboard.
     */
    public function toResponse($request)
    {
        if ($request->session()->has(OtpController::SESSION_KEY)) {
            return $request->wantsJson()
                ? response()->json(['redirect' => route('otp.show')])
                : redirect()->route('otp.show');
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(config('fortify.home'));
    }
}
