<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CongregationServiceController;
use App\Http\Controllers\Api\RoomCategoryController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomFacilityController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WilayahController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);

// Authenticated routes
Route::middleware(['auth:sanctum', 'force.json'])->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/user', [AuthController::class, 'user']);

    // Profile
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::put('profile/password', [ProfileController::class, 'changePassword']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::put('profile/signature', [ProfileController::class, 'updateSignature']);

    // Room categories & facilities (viewable by all)
    Route::get('room-categories', [RoomCategoryController::class, 'index']);
    Route::get('room-facilities', [RoomFacilityController::class, 'index']);

    // Rooms (viewable by all authenticated)
    Route::get('rooms', [RoomController::class, 'index']);
    Route::get('rooms/{room}', [RoomController::class, 'show']);
    Route::get('rooms/{room}/schedules', [RoomController::class, 'schedules']);
    Route::get('rooms/{room}/availability', [RoomController::class, 'availability']);

    // Bookings
    Route::get('bookings', [BookingController::class, 'index']);
    Route::get('bookings/my', [BookingController::class, 'myBookings']);
    Route::get('bookings/pending', [BookingController::class, 'pending'])->middleware('can:bookings.approve');
    Route::get('bookings/calendar', [BookingController::class, 'calendar']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('bookings/{booking}', [BookingController::class, 'show']);
    Route::put('bookings/{booking}', [BookingController::class, 'update']);
    Route::delete('bookings/{booking}', [BookingController::class, 'destroy']);
    Route::post('bookings/{booking}/signature', [BookingController::class, 'sign']);

    // Approvals (sekretariat/admin)
    Route::post('bookings/{booking}/approve', [ApprovalController::class, 'approve'])->middleware('can:bookings.approve');
    Route::post('bookings/{booking}/reject', [ApprovalController::class, 'reject'])->middleware('can:bookings.reject');

    // Congregation Services
    Route::get('congregation-services', [CongregationServiceController::class, 'index']);
    Route::get('congregation-services/{id}', [CongregationServiceController::class, 'show']);
    Route::post('congregation-services', [CongregationServiceController::class, 'store']);
    Route::post('congregation-services/{id}/approve', [CongregationServiceController::class, 'approve'])->middleware('can:bookings.approve');
    Route::post('congregation-services/{id}/reject', [CongregationServiceController::class, 'reject'])->middleware('can:bookings.reject');

    // Wilayah & Lingkungan (master data untuk form Pelayanan Umat)
    Route::get('wilayah', [WilayahController::class, 'index']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Reports (sekretariat & admin)
    Route::get('reports/bookings', [ReportController::class, 'bookings'])->middleware('can:reports.view');
    Route::get('reports/room-utilization', [ReportController::class, 'roomUtilization'])->middleware('can:reports.view');
    Route::get('reports/user-activity', [ReportController::class, 'userActivity'])->middleware('can:reports.view');
    Route::get('reports/monthly', [ReportController::class, 'monthly'])->middleware('can:reports.view');
    Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->middleware('can:reports.export');
    Route::get('reports/export/excel', [ReportController::class, 'exportExcel'])->middleware('can:reports.export');

    // Admin-only routes
    Route::middleware('can:rooms.create')->group(function () {
        Route::post('rooms', [RoomController::class, 'store']);
        Route::put('rooms/{room}', [RoomController::class, 'update']);
        Route::delete('rooms/{room}', [RoomController::class, 'destroy']);
        Route::post('rooms/{room}/images', [RoomController::class, 'uploadImage']);
        Route::delete('rooms/{room}/images/{image}', [RoomController::class, 'deleteImage']);
        Route::put('rooms/{room}/images/{image}/primary', [RoomController::class, 'setPrimaryImage']);

        Route::post('room-categories', [RoomCategoryController::class, 'store']);
        Route::put('room-categories/{roomCategory}', [RoomCategoryController::class, 'update']);
        Route::delete('room-categories/{roomCategory}', [RoomCategoryController::class, 'destroy']);

        Route::post('room-facilities', [RoomFacilityController::class, 'store']);
        Route::put('room-facilities/{roomFacility}', [RoomFacilityController::class, 'update']);
        Route::delete('room-facilities/{roomFacility}', [RoomFacilityController::class, 'destroy']);

        Route::apiResource('maintenance-schedules', MaintenanceController::class)->except(['show']);

        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
        Route::put('users/{user}/activate', [UserController::class, 'toggleActive']);
        Route::put('users/{user}/roles', [UserController::class, 'assignRoles']);

        Route::get('audit-logs', [AuditLogController::class, 'index']);
    });
});
