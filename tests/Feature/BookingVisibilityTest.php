<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RoomController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Repositories\BookingRepository;
use App\Repositories\RoomRepository;
use App\Services\AuditService;
use App\Services\RoomService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('name');
        });
        Schema::create('rooms', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('name');
            $t->string('slug');
            $t->softDeletes();
        });
        Schema::create('bookings', function (Blueprint $t) {
            foreach (['id', 'user_id', 'room_id', 'title', 'description', 'booking_date', 'start_time', 'end_time', 'status', 'booking_type'] as $field) {
                $t->string($field);
            }
            $t->json('recurring_dates')->nullable();
            $t->timestamps();
        });
        Schema::create('maintenance_schedules', function (Blueprint $t) {
            $t->string('id');
            $t->date('start_date');
            $t->date('end_date');
        });
        DB::table('users')->insert([['id' => 'owner', 'name' => 'Pemilik'], ['id' => 'other', 'name' => 'Nama Rahasia']]);
        DB::table('rooms')->insert(['id' => 'room', 'name' => '401 / Auditorium', 'slug' => 'auditorium']);
        foreach (['owner', 'other'] as $owner) {
            foreach (['reguler', 'rutin'] as $type) {
                DB::table('bookings')->insert([
                    'id' => "$owner-$type", 'user_id' => $owner, 'room_id' => 'room', 'title' => "$owner kegiatan",
                    'description' => "$owner deskripsi", 'booking_date' => '2026-09-06',
                    'start_time' => '10:00:00', 'end_time' => '11:00:00', 'status' => 'approved', 'booking_type' => $type,
                    'recurring_dates' => $type === 'rutin' ? '["2026-09-06","2026-09-13"]' : null,
                ]);
            }
        }
    }

    private function user(string $id = 'owner', string $role = 'umat'): User
    {
        $user = new User;
        $user->id = $id;
        $user->setRelation('roles', collect([new Role(['name' => $role, 'guard_name' => 'web'])]));

        return $user;
    }

    public function test_calendar_shows_all_summaries_but_only_authorized_details(): void
    {
        $request = Request::create('/api/bookings/calendar', 'GET', ['start' => '2026-09-01', 'end' => '2026-09-30', 'user_id' => 'other']);
        $request->setUserResolver(fn () => $this->user());
        $response = app(BookingController::class)->calendar($request)->getData(true);
        $this->assertCount(6, $response['data']);
        foreach ($response['data'] as $event) {
            $own = str_starts_with($event['booking_id'], 'owner-');
            $this->assertSame($own, $event['can_view_detail']);
            if (! $own) {
                $this->assertArrayNotHasKey('description', $event['extendedProps']);
                $this->assertArrayNotHasKey('user', $event);
            }
        }
        $this->assertStringNotContainsString('Nama Rahasia', json_encode($response));
        foreach (['sekretariat', 'p2', 'pastor', 'it_admin'] as $role) {
            $request->setUserResolver(fn () => $this->user('staff', $role));
            $this->assertCount(6, app(BookingController::class)->calendar($request)->getData(true)['data']);
        }
    }

    public function test_policy_denies_direct_access_to_other_members_booking(): void
    {
        $policy = new BookingPolicy;
        $booking = Booking::find('other-reguler');
        $this->assertFalse($policy->view($this->user(), $booking));
        $this->assertTrue($policy->view($this->user('other'), $booking));
        $this->assertTrue($policy->view($this->user('staff', 'sekretariat'), $booking));
    }

    public function test_room_schedule_list_is_owner_scoped(): void
    {
        $service = Mockery::mock(RoomService::class);
        $service->shouldReceive('find')->with('room')->andReturn(Room::find('room'));
        $request = Request::create('/', 'GET', ['start' => '2026-09-01', 'end' => '2026-09-30']);
        $request->setUserResolver(fn () => $this->user());
        $data = (new RoomController($service))->schedules('room', $request)->getData(true)['data'];
        $this->assertCount(2, $data);
        foreach ($data as $booking) {
            $this->assertStringStartsWith('owner-', $booking['id']);
        }
    }

    public function test_occupied_slots_hide_other_titles_without_exposing_free_time(): void
    {
        $this->actingAs($this->user());
        $repository = Mockery::mock(RoomRepository::class);
        $repository->shouldReceive('findOrFail')->andReturn(Room::find('room'));
        $repository->shouldReceive('getDayBookedSlots')->andReturn(Booking::where('booking_type', 'reguler')->get());
        $service = new RoomService($repository, Mockery::mock(AuditService::class));
        $data = $service->getDayAvailability('room', '2026-09-06');
        $this->assertSame(['owner kegiatan', 'Terpakai'], array_column($data['booked_slots'], 'title'));
        $this->assertNull($data['booked_slots'][1]['status']);
        $this->assertArrayNotHasKey('id', $data['booked_slots'][1]);
        $this->assertSame('10:00', $data['booked_slots'][1]['start_time']);
        $this->assertSame('10:00', $data['free_slots'][0]['end_time']);
        $this->assertSame('11:00', $data['free_slots'][1]['start_time']);
    }

    public function test_completed_auditorium_uses_completed_color(): void
    {
        DB::table('bookings')->where('id', 'owner-reguler')->update(['status' => 'completed']);
        $events = app(BookingRepository::class)->getCalendarData('2026-09-01', '2026-09-30', null, $this->user());
        $completed = $events->firstWhere('booking_id', 'owner-reguler');
        $active = $events->firstWhere('booking_id', 'owner-rutin');
        $this->assertSame('#64748b', $completed['backgroundColor']);
        $this->assertSame('Selesai', $completed['extendedProps']['status_label']);
        $this->assertSame('#8b5cf6', $active['backgroundColor']);
    }
}
