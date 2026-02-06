<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            \App\Http\Middleware\SetCurrentHospital::class,
        ]);

        $middleware->alias([
            'super_admin' => \App\Http\Middleware\SuperAdmin::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        // Configure authentication redirect - return null for API routes to get JSON 401
        $middleware->redirectGuestsTo(fn ($request) => $request->expectsJson() ? null : '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
