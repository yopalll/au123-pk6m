<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public const SESSION_KEY = 'otp:id_user';

    public function __construct(private readonly OtpService $otp) {}

    /**
     * Tampilkan halaman verifikasi OTP.
     */
    public function show(Request $request)
    {
        $user = $this->resolveUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        // Kalau sudah terverifikasi, tidak perlu OTP lagi.
        if ($user->email_verified_at) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('dashboard');
        }

        return view('pages::auth.verify-otp', [
            'email'             => $this->maskEmail($user->email),
            'secondsUntilResend' => $this->otp->secondsUntilResend($user),
        ]);
    }

    /**
     * Verifikasi kode OTP yang dimasukkan user.
     */
    public function verify(Request $request): RedirectResponse
    {
        $user = $this->resolveUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.digits' => 'Kode OTP harus 6 angka.',
        ]);

        if (! $this->otp->verify($user, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Kode salah atau sudah kedaluwarsa. Coba lagi atau kirim ulang kode.',
            ]);
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('dashboard')
            ->with('status', 'Email kamu berhasil diverifikasi. Selamat datang di VIYGO!');
    }

    /**
     * Kirim ulang kode OTP (dengan cooldown 60 detik server-side).
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $this->resolveUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $wait = $this->otp->secondsUntilResend($user);

        if ($wait > 0) {
            throw ValidationException::withMessages([
                'code' => "Mohon tunggu {$wait} detik sebelum meminta kode baru.",
            ]);
        }

        $this->otp->send($user);

        return back()->with('status', 'Kode baru sudah dikirim ke email kamu.');
    }

    /**
     * Resolusi user: prioritaskan session flag yang di-set saat registrasi,
     * jatuh ke user yang sedang login.
     */
    private function resolveUser(Request $request): ?User
    {
        if ($id = $request->session()->get(self::SESSION_KEY)) {
            return User::find($id);
        }

        return $request->user();
    }

    /**
     * Samarkan email untuk ditampilkan: yo***@gmail.com
     */
    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);
        $visible = mb_substr($name, 0, min(2, mb_strlen($name)));

        return $visible.str_repeat('*', max(3, mb_strlen($name) - 2)).'@'.$domain;
    }
}
