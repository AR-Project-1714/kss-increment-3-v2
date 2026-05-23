<?php

use App\Http\Middleware\EnsureRole;
use App\Models\Role;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Guests hitting protected pages go to the login screen; already
        // authenticated users hitting guest pages go to THEIR OWN dashboard.
        $middleware->redirectTo(
            guests: fn () => route('login'),
            users: fn (Request $request) => route(Role::homeRoute($request->user()?->role->name ?? null)),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Session/CSRF expired ("Page Expired" / 419): send the user straight to
        // the login page with a flash message instead of the error screen.
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            $message = 'Sesi Anda telah habis, silakan login kembali.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            return redirect()->guest(route('login'))->with('error', $message);
        });
    })->create();
