<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CongregationService;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingCreated;
use App\Notifications\BookingForwardedToAdmin;
use App\Notifications\BookingMovedToAdminReview;
use App\Notifications\BookingRejected;
use App\Notifications\BookingReminder;
use App\Notifications\CongregationServiceApproved;
use App\Notifications\CongregationServiceCreated;
use App\Notifications\CongregationServiceRejected;
use App\Notifications\RecurringBookingCreated;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use Tests\TestCase;

class NotificationPushChannelsTest extends TestCase
{
    public function test_every_booking_and_service_notification_supports_web_push(): void
    {
        config()->set('services.frontend.url', 'https://app.santoalbertus.web.id');

        $booking = new Booking;
        $booking->setAttribute('id', 'booking-test');
        $booking->setAttribute('title', 'Kegiatan Uji');
        $booking->setRelation('user', new User(['name' => 'Pemohon Uji']));

        $service = new CongregationService;
        $service->setAttribute('id', 'service-test');
        $service->setAttribute('service_type', 'intensi_misa');

        $notifications = [
            new BookingApproved($booking),
            new BookingCancelled($booking),
            new BookingCreated($booking),
            new BookingForwardedToAdmin($booking),
            new BookingMovedToAdminReview($booking),
            new BookingRejected($booking),
            new BookingReminder($booking),
            new RecurringBookingCreated($booking, 0),
            new CongregationServiceApproved($service),
            new CongregationServiceCreated($service),
            new CongregationServiceRejected($service),
        ];

        foreach ($notifications as $notification) {
            $this->assertSame(
                ['database', WebPushChannel::class, 'mail'],
                $notification->via(new User),
                $notification::class
            );
            $this->assertInstanceOf(
                WebPushMessage::class,
                $notification->toWebPush(new User, $notification),
                $notification::class
            );
        }
    }
}
