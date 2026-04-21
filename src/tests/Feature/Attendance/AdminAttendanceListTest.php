<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 4, 15, 9, 0, 0));

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->user1 = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $this->user2 = User::factory()->create([
            'name' => '佐藤花子',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_confirm_all_users_attendance_of_the_day(): void
    {
        $attendance1 = Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'work_date' => '2026-04-15',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance1->id,
            'break_no' => 1,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $this->user2->id,
            'work_date' => '2026-04-15',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '17:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_no' => 1,
            'break_start_at' => '13:00:00',
            'break_end_at' => '13:30:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'work_date' => '2026-04-14',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list'));

        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '山田太郎',
            '09:00',
            '18:00',
            '01:00',
            '08:00',
        ]);

        $response->assertSeeInOrder([
            '佐藤花子',
            '10:00',
            '17:00',
            '00:30',
            '06:30',
        ]);
    }


    public function test_current_date_is_displayed_when_admin_opens_attendance_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('2026/04/15');
        $response->assertSee('2026年4月15日の勤怠一覧');
    }

    public function test_previous_day_attendance_is_displayed_when_admin_clicks_previous_day(): void
    {
        Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'work_date' => '2026-04-14',
            'clock_in_at' => '08:30:00',
            'clock_out_at' => '17:30:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user2->id,
            'work_date' => '2026-04-15',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]);

        $response = $this->actingAs($this->admin)->get(
            route('admin.attendance.list', ['date' => '2026-04-14'])
        );

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '2026/04/14',
            '08:30',
            '17:30',
        ]);
        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
    }

    public function test_next_day_attendance_is_displayed_when_admin_clicks_next_day(): void
    {
        Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'work_date' => '2026-04-16',
            'clock_in_at' => '09:30:00',
            'clock_out_at' => '18:30:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user2->id,
            'work_date' => '2026-04-15',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]);

        $response = $this->actingAs($this->admin)->get(
            route('admin.attendance.list', ['date' => '2026-04-16'])
        );

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            '2026/04/16',
            '09:30',
            '18:30',
        ]);
        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
    }
}
