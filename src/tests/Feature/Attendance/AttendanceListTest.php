<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\DomCrawler\Crawler;
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
            'email_verified_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_all_of_my_attendance_information_is_displayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 15, 9, 0, 0));

        $user = $this->createUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('2026/04');

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="user_attendance_row_2026-04-10"]');
        $this->assertCount(1, $row);

        $clockIn = $row->filter('[data-testid="user_clock_in_at"]');
        $this->assertCount(1, $clockIn);
        $this->assertSame('09:00', trim($clockIn->text()));

        $clockOut = $row->filter('[data-testid="user_clock_out_at"]');
        $this->assertCount(1, $clockOut);
        $this->assertSame('18:00', trim($clockOut->text()));

        $breakTime = $row->filter('[data-testid="user_break_time"]');
        $this->assertCount(1, $breakTime);
        $this->assertSame('01:00', trim($breakTime->text()));

        $workTime = $row->filter('[data-testid="user_work_time"]');
        $this->assertCount(1, $workTime);
        $this->assertSame('08:00', trim($workTime->text()));
    }

    public function test_current_month_is_displayed_when_user_opens_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 15, 9, 0, 0));

        $user = $this->createUser();

        $response = $this->actingAs($user)->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('2026/04');
    }

    public function test_previous_month_is_displayed(): void
    {
        $user = $this->createUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '08:30:00',
            'clock_out_at' => '17:30:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-03',
        ]));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $this->assertStringContainsString('2026/03', $crawler->text());

        $row = $crawler->filter('[data-testid="user_attendance_row_2026-03-10"]');
        $this->assertCount(1, $row);

        $clockIn = $row->filter('[data-testid="user_clock_in_at"]');
        $this->assertCount(1, $clockIn);
        $this->assertSame('08:30', trim($clockIn->text()));

        $clockOut = $row->filter('[data-testid="user_clock_out_at"]');
        $this->assertCount(1, $clockOut);
        $this->assertSame('17:30', trim($clockOut->text()));

        $breakTime = $row->filter('[data-testid="user_break_time"]');
        $this->assertCount(1, $breakTime);
        $this->assertSame('01:00', trim($breakTime->text()));

        $workTime = $row->filter('[data-testid="user_work_time"]');
        $this->assertCount(1, $workTime);
        $this->assertSame('08:00', trim($workTime->text()));
    }

    public function test_next_month_is_displayed(): void
    {
        $user = $this->createUser();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-05-10',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => '13:00:00',
            'break_end_at' => '14:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-05',
        ]));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $this->assertStringContainsString('2026/05', $crawler->text());

        $row = $crawler->filter('[data-testid="user_attendance_row_2026-05-10"]');
        $this->assertCount(1, $row);

        $clockIn = $row->filter('[data-testid="user_clock_in_at"]');
        $this->assertCount(1, $clockIn);
        $this->assertSame('10:00', trim($clockIn->text()));

        $clockOut = $row->filter('[data-testid="user_clock_out_at"]');
        $this->assertCount(1, $clockOut);
        $this->assertSame('19:00', trim($clockOut->text()));

        $breakTime = $row->filter('[data-testid="user_break_time"]');
        $this->assertCount(1, $breakTime);
        $this->assertSame('01:00', trim($breakTime->text()));

        $workTime = $row->filter('[data-testid="user_work_time"]');
        $this->assertCount(1, $workTime);
        $this->assertSame('08:00', trim($workTime->text()));
    }

    public function test_user_can_view_attendance_detail_from_attendance_list(): void
    {
        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-04'
        ]));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="user_attendance_row_2026-04-10"]');
        $this->assertCount(1, $row);

        $detailUrl = $row->filter('[data-testid="user_detail_url"]');
        $this->assertCount(1, $detailUrl);

        $this->assertSame(
            route('attendance.detail', $attendance->id),
            $detailUrl->attr('href')
        );

        $detailResponse = $this->actingAs($user)->get($detailUrl->attr('href'));
        $detailResponse->assertStatus(200);
    }
}
