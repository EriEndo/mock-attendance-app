<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BreakTest extends TestCase
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

    private function createWorkingAttendance(User $user): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
        ]);
    }

    public function test_break_start_button_works_correctly(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 12, 0, 0));

        $this->createWorkingAttendance($user);

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $response = $this->actingAs($user)->post(route('attendance.break_start'));
        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'break_no' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    public function test_break_start_can_be_taken_multiple_times_in_a_day(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post(route('attendance.break_start'));

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 30, 0));
        $this->actingAs($user)->post(route('attendance.break_end'));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩入');

        Carbon::setTestNow();
    }

    public function test_break_end_button_works_correctly(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post(route('attendance.break_start'));

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertSee('休憩戻');

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 30, 0));
        $this->actingAs($user)->post(route('attendance.break_end'));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    public function test_break_end_can_be_done_multiple_times_in_a_day(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post(route('attendance.break_start'));

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 15, 0));
        $this->actingAs($user)->post(route('attendance.break_end'));

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 10, 0, 0));
        $this->actingAs($user)->post(route('attendance.break_start'));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    public function test_total_break_time_is_displayed_correctly_on_attendance_list(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
            'clock_in_at' => Carbon::create(2026, 4, 14, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 4, 14, 18, 0, 0),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => Carbon::create(2026, 4, 14, 12, 0, 0),
            'break_end_at' => Carbon::create(2026, 4, 14, 12, 30, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('00:30');

        Carbon::setTestNow();
    }
}
