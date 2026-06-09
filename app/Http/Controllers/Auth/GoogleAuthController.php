<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Arahkan user ke halaman consent Google.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback dari Google setelah user memberi izin.
     * - Sudah pernah pakai Google → login.
     * - Email-nya sudah terdaftar lokal → tautkan google_id, login.
     * - Belum ada → buat akun baru tanpa password (email otomatis terverifikasi).
     */
    public function callback()
    {
        try {
            $g = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal login dengan Google. Silakan coba lagi.',
            ]);
        }

        // 1) Sudah pernah login via Google.
        $user = User::where('google_id', $g->getId())->first();

        // 2) Atau email-nya sudah terdaftar lokal → tautkan.
        if (! $user) {
            $user = User::where('email', $g->getEmail())->first();
        }

        if ($user) {
            $user->google_id = $user->google_id ?: $g->getId();
            $user->avatar = $g->getAvatar();
            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }
            $user->save();
        } else {
            // 3) Buat akun baru tanpa password.
            $parts = preg_split('/\s+/', trim($g->getName() ?? $g->getNickname() ?? 'User'), 2);

            $user = new User();
            $user->first_name = $parts[0] ?? 'User';
            $user->last_name = $parts[1] ?? null;
            $user->email = $g->getEmail();
            $user->google_id = $g->getId();
            $user->avatar = $g->getAvatar();
            $user->password = null;            // login hanya via Google
            $user->email_verified_at = now();
            $user->save();                     // role default 'customer' dari migration
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }
}
