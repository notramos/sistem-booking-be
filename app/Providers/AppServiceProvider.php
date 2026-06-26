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
        Booking::observe(BookingObserver::class);
    }
}
