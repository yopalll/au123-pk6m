<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'   => CheckRole::class,
            'active' => EnsureUserIsActive::class,
        ]);

        // Catch authenticated users whose account got deactivated mid-session —
        // log them out and bounce to a "page expired" screen with a Back-to-login button.
        $middleware->web(append: [
            EnsureUserIsActive::class,
        ]);

        // Trust proxies so Laravel correctly detects HTTPS when behind ngrok
        // or other reverse proxies. Without this, Livewire generates http://
        // URLs while the browser uses https:// → CSRF / upload mismatches.
        $middleware->trustProxies(at: '*');

        // Midtrans posts to /midtrans/webhook — verified via signature_key,
        // not via Laravel CSRF.
        $middleware->validateCsrfTokens(except: [
            'midtrans/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
