<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_name_is_displayed_in_correct_field(): void
    {
        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="detail_name_row"]');
        $this->assertCount(1, $row);

        $name = $row->filter('[data-testid="detail_name"]');
        $this->assertCount(1, $name);

        $this->assertSame($user->name, trim($name->text()));
    }

    public function test_date_is_displayed_in_correct_field(): void
    {
        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="detail_date_row"]');
        $this->assertCount(1, $row);

        $date = $row->filter('[data-testid="detail_date"]');
        $this->assertCount(1, $date);

        $this->assertStringContainsString('2026年', $date->text());
        $this->assertStringContainsString('4月14日', $date->text());
    }

    public function test_clock_in_out_are_in_correct_fields(): void
    {
        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="detail_clock_inout_row"]');
        $this->assertCount(1, $row);

        $clockIn = $row->filter('[data-testid="detail_clock_in_at"]');
        $this->assertCount(1, $clockIn);

        $clockOut = $row->filter('[data-testid="detail_clock_out_at"]');
        $this->assertCount(1, $clockOut);

        $this->assertSame('09:00', $clockIn->attr('value'));
        $this->assertSame('18:00', $clockOut->attr('value'));
    }

    public function test_break_time_is_displayed_in_correct_field(): void
    {
        $user = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-14',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => '12:00',
            'break_end_at' => '13:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="detail_breaks_row_0"]');
        $this->assertCount(1, $row);

        $start = $row->filter('[data-testid="detail_break_start_at"]');
        $this->assertCount(1, $start);

        $end = $row->filter('[data-testid="detail_break_end_at"]');
        $this->assertCount(1, $end);

        $this->assertSame('12:00', $start->attr('value'));
        $this->assertSame('13:00', $end->attr('value'));
    }
}
