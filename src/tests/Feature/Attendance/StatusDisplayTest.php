<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }

    public function test_status_is_displayed_as_off(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('勤務外');

        Carbon::setTestNow();
    }

    public function test_status_is_displayed_as_working(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in_at' => Carbon::create(2026, 4, 14, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_status_is_displayed_as_break(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 12, 0, 0));

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in_at' => Carbon::create(2026, 4, 14, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => Carbon::create(2026, 4, 14, 12, 0, 0),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    public function test_status_is_displayed_as_done(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 18, 0, 0));

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in_at' => Carbon::create(2026, 4, 14, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 4, 14, 18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }
}
