<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catches authenticated users whose `is_active` has been flipped to false
 * (deactivated by an admin). Logs them out and redirects to the dedicated
 * "account deactivated" page so they get a clear message + a way back to login,
 * rather than a generic 403 or being silently kept signed in.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('account.deactivated');
        }

        return $next($request);
    }
}
