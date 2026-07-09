<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Jobs\AutoCompleteBooking;
use App\Jobs\SendBookingReminder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'force.json' => ForceJsonResponse::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        // Autentikasi SPA Sanctum berbasis cookie session HttpOnly (bukan bearer
        // token) untuk request dari domain yang terdaftar di SANCTUM_STATEFUL_DOMAINS.
        $middleware->statefulApi();

        // Limiter global (definisi 'api' di AppServiceProvider::boot()).
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new AutoCompleteBooking)->hourly();
        $schedule->job(new SendBookingReminder)->dailyAt('08:00');
    })
    ->create();
