<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClockInTest extends TestCase
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

    public function test_clock_in_button_works_correctly(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('出勤');

        $response = $this->actingAs($user)->post(route('attendance.clock_in_at'));
        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_clock_in_can_only_be_done_once_per_day(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 18, 0, 0));

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
            'clock_in_at' => Carbon::create(2026, 4, 14, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 4, 14, 18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertDontSee('出勤</button>', false);

        Carbon::setTestNow();
    }

    public function test_clock_in_time_is_visible_on_attendance_list(): void
    {
        $user = $this->createUser();

        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        $this->actingAs($user)->post(route('attendance.clock_in_at'));

        $response = $this->actingAs($user)->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('09:00');

        Carbon::setTestNow();
    }
}
