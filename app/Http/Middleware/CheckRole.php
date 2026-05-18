<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to authenticated users whose `role` column
 * matches one of the roles passed to the middleware.
 *
 * Usage:
 *   Route::middleware('role:customer')->group(...);
 *   Route::middleware('role:salon_owner,admin')->group(...);
 *
 * Roles supported (per users.role enum):
 *   - customer
 *   - salon_owner
 *   - admin
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // BUG-A16: property_exists() always returns false for Eloquent dynamic attributes.
        // Simplified: check is_active directly.
        if ($user->is_active === false) {
            abort(403, 'Your account is currently inactive.');
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
