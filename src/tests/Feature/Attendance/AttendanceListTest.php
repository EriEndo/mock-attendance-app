<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceListTest extends TestCase
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

    private function createOtherUser(): User
    {
        return User::factory()->create([
            'name' => '他人ユーザー',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }

    public function test_all_of_my_attendance_information_is_displayed(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createOtherUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => Carbon::create(2026, 4, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 4, 10, 18, 0, 0),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
            'clock_in_at' => Carbon::create(2026, 4, 14, 10, 0, 0),
            'clock_out_at' => Carbon::create(2026, 4, 14, 19, 0, 0),
        ]);

        Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-04-20',
            'clock_in_at' => Carbon::create(2026, 4, 20, 8, 0, 0),
            'clock_out_at' => Carbon::create(2026, 4, 20, 17, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('days');

        $response->assertSee('4/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('4/14');
        $response->assertSee('10:00');
        $response->assertSee('19:00');

        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');

        $days = collect($response->viewData('days'));

        $day10 = $days->firstWhere('work_date', '2026-04-10');
        $day14 = $days->firstWhere('work_date', '2026-04-14');
        $day20 = $days->firstWhere('work_date', '2026-04-20');

        $this->assertNotNull($day10);
        $this->assertSame('09:00', $day10['clock_in_at']);
        $this->assertSame('18:00', $day10['clock_out_at']);

        $this->assertNotNull($day14);
        $this->assertSame('10:00', $day14['clock_in_at']);
        $this->assertSame('19:00', $day14['clock_out_at']);

        $this->assertNotNull($day20);
        $this->assertSame('', $day20['clock_in_at']);
        $this->assertSame('', $day20['clock_out_at']);
    }

    public function test_current_month_is_displayed_when_opening_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 14, 9, 0, 0));

        $user = $this->createUser();

        $response = $this->actingAs($user)->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertViewHas('targetMonth');

        $response->assertSee('2026/04');

        $targetMonth = $response->viewData('targetMonth');
        $this->assertSame('2026-04', $targetMonth->format('Y-m'));

        Carbon::setTestNow();
    }

    public function test_previous_month_is_displayed_when_pressing_prev_month(): void
    {
        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-03',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('targetMonth');
        $response->assertViewHas('days');

        $response->assertSee('2026/03');
        $response->assertSee('3/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $targetMonth = $response->viewData('targetMonth');
        $days = collect($response->viewData('days'));
        $day = $days->firstWhere('work_date', '2026-03-10');

        $this->assertSame('2026-03', $targetMonth->format('Y-m'));
        $this->assertNotNull($day);
        $this->assertSame('09:00', $day['clock_in_at']);
        $this->assertSame('18:00', $day['clock_out_at']);
    }

    public function test_next_month_is_displayed_when_pressing_next_month(): void
    {
        $user = $this->createUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-12',
            'clock_in_at' => Carbon::create(2026, 5, 12, 9, 30, 0),
            'clock_out_at' => Carbon::create(2026, 5, 12, 18, 30, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-05',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('targetMonth');
        $response->assertViewHas('days');

        $response->assertSee('2026/05');
        $response->assertSee('5/12');
        $response->assertSee('09:30');
        $response->assertSee('18:30');

        $targetMonth = $response->viewData('targetMonth');
        $days = collect($response->viewData('days'));
        $day = $days->firstWhere('work_date', '2026-05-12');

        $this->assertSame('2026-05', $targetMonth->format('Y-m'));
        $this->assertNotNull($day);
        $this->assertSame('09:30', $day['clock_in_at']);
        $this->assertSame('18:30', $day['clock_out_at']);
    }

    public function test_clicking_detail_navigates_to_attendance_detail_page(): void
    {
        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
            'clock_in_at' => Carbon::create(2026, 4, 14, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 4, 14, 18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('days');

        $response->assertSee(route('attendance.detail', $attendance->id), false);

        $days = collect($response->viewData('days'));
        $day = $days->firstWhere('work_date', '2026-04-14');

        $this->assertNotNull($day);
        $this->assertSame(route('attendance.detail', $attendance->id), $day['detail_url']);

        $detailResponse = $this->actingAs($user)->get(route('attendance.detail', $attendance->id));
        $detailResponse->assertStatus(200);
    }
}
