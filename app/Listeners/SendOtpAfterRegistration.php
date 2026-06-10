<?php

namespace App\Listeners;

use App\Http\Controllers\Auth\OtpController;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;

class SendOtpAfterRegistration
{
    public function __construct(private readonly OtpService $otp) {}

    /**
     * Saat user baru mendaftar (Fortify men-dispatch Registered), kirim kode OTP
     * dan tandai session supaya halaman /otp tahu user mana yang sedang verifikasi.
     *
     * Login via Google sudah memberi email terverifikasi, jadi dilewati.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->email_verified_at) {
            return;
        }

        session([OtpController::SESSION_KEY => $user->id_user]);

        $this->otp->send($user);
    }
}
