<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClockOutTest extends TestCase
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

    public function test_clock_out_is_recorded_and_status_is_updated(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 18, 0, 0));

        $this->createWorkingAttendance($user);

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('退勤');

        $response = $this->actingAs($user)->post(route('attendance.clock_out_at'));
        $response->assertRedirect(route('attendance.index'));

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertSee('退勤済');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
            'clock_out_at' => '18:00:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_clock_out_time_is_visible_on_attendance_list(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('勤務外');
        $response->assertSee('出勤');

        $response = $this->post(route('attendance.clock_in_at'));
        $response->assertRedirect(route('attendance.index'));

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 18, 0, 0));

        $response = $this->post(route('attendance.clock_out_at'));
        $response->assertRedirect(route('attendance.index'));

        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('18:00');

        Carbon::setTestNow();
    }
}
