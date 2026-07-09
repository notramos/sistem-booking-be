<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Room;
use App\Observers\BookingObserver;
use App\Repositories\BookingRepository;
use App\Repositories\RoomRepository;
use App\Repositories\UserRepository;
use App\Services\ApprovalService;
use App\Services\AuditService;
use App\Services\BookingService;
use App\Services\CalendarService;
use App\Services\NotificationService;
use App\Services\ReportService;
use App\Services\RoomService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BookingRepository::class);
        $this->app->singleton(RoomRepository::class);
        $this->app->singleton(UserRepository::class);

        $this->app->singleton(BookingService::class);
        $this->app->singleton(RoomService::class);
        $this->app->singleton(ApprovalService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(ReportService::class);
        $this->app->singleton(CalendarService::class);
    }

    public function boot(): void
    {
        \App\Models\Booking::observe(\App\Observers\BookingObserver::class);

        // Limiter global untuk seluruh route 'api' (lihat bootstrap/app.php ->throttleApi()).
        // Endpoint yang butuh batas lebih ketat (mis. login) tetap punya throttle sendiri.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
