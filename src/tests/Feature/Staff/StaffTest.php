<?php

namespace Tests\Feature\Staff;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff1;
    private User $staff2;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 4, 15, 9, 0, 0));

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->staff1 = User::factory()->create([
            'role' => 'user',
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'email_verified_at' => now(),
        ]);

        $this->staff2 = User::factory()->create([
            'role' => 'user',
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
            'email_verified_at' => now(),
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->staff1->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'break_no' => 1,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_view_all_staff_names_and_emails(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff.list'));

        $response->assertStatus(200);

        $response->assertSee('山田太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('佐藤花子');
        $response->assertSee('sato@example.com');
    }

    public function test_selected_users_attendance_is_displayed_correctly(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff.attendance', ['id' => $this->staff1->id]));

        $response->assertStatus(200);

        $crawler = new \Symfony\Component\DomCrawler\Crawler($response->getContent());

        $this->assertStringContainsString('山田太郎さんの勤怠一覧', $crawler->text());
        $this->assertStringContainsString('2026/04', $crawler->text());

        $row = $crawler->filter('[data-testid="staff_attendance_row_2026-04-10"]');
        $this->assertCount(1, $row);

        $clockIn = $row->filter('[data-testid="staff_clock_in_at"]');
        $this->assertCount(1, $clockIn);
        $this->assertSame('09:00', trim($clockIn->text()));

        $clockOut = $row->filter('[data-testid="staff_clock_out_at"]');
        $this->assertCount(1, $clockOut);
        $this->assertSame('18:00', trim($clockOut->text()));

        $breakTime = $row->filter('[data-testid="staff_break_time"]');
        $this->assertCount(1, $breakTime);
        $this->assertSame('01:00', trim($breakTime->text()));

        $workTime = $row->filter('[data-testid="staff_work_time"]');
        $this->assertCount(1, $workTime);
        $this->assertSame('08:00', trim($workTime->text()));
    }

    public function test_previous_month_attendance_is_displayed(): void
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->staff1->id,
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

        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff.attendance', [
                'id' => $this->staff1->id,
                'month' => '2026-03',
            ]));

        $response->assertStatus(200);

        $crawler = new \Symfony\Component\DomCrawler\Crawler($response->getContent());

        $this->assertStringContainsString('2026/03', $crawler->text());

        $row = $crawler->filter('[data-testid="staff_attendance_row_2026-03-10"]');
        $this->assertCount(1, $row);

        $clockIn = $row->filter('[data-testid="staff_clock_in_at"]');
        $this->assertCount(1, $clockIn);
        $this->assertSame('08:30', trim($clockIn->text()));

        $clockOut = $row->filter('[data-testid="staff_clock_out_at"]');
        $this->assertCount(1, $clockOut);
        $this->assertSame('17:30', trim($clockOut->text()));

        $breakTime = $row->filter('[data-testid="staff_break_time"]');
        $this->assertCount(1, $breakTime);
        $this->assertSame('01:00', trim($breakTime->text()));

        $workTime = $row->filter('[data-testid="staff_work_time"]');
        $this->assertCount(1, $workTime);
        $this->assertSame('08:00', trim($workTime->text()));
    }

    public function test_next_month_attendance_is_displayed(): void
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->staff1->id,
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

        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff.attendance', [
                'id' => $this->staff1->id,
                'month' => '2026-05',
            ]));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $this->assertStringContainsString('2026/05', $crawler->text());

        $row = $crawler->filter('[data-testid="staff_attendance_row_2026-05-10"]');
        $this->assertCount(1, $row);

        $clockIn = $row->filter('[data-testid="staff_clock_in_at"]');
        $this->assertCount(1, $clockIn);
        $this->assertSame('10:00', trim($clockIn->text()));

        $clockOut = $row->filter('[data-testid="staff_clock_out_at"]');
        $this->assertCount(1, $clockOut);
        $this->assertSame('19:00', trim($clockOut->text()));

        $breakTime = $row->filter('[data-testid="staff_break_time"]');
        $this->assertCount(1, $breakTime);
        $this->assertSame('01:00', trim($breakTime->text()));

        $workTime = $row->filter('[data-testid="staff_work_time"]');
        $this->assertCount(1, $workTime);
        $this->assertSame('08:00', trim($workTime->text()));
    }

    public function test_admin_can_view_attendance_detail_from_staff_list(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff.attendance', [
                'id' => $this->staff1->id,
                'month' => '2026-04',
            ]));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="staff_attendance_row_2026-04-10"]');
        $this->assertCount(1, $row);

        $detailUrl = $row->filter('[data-testid="staff_detail_url"]');
        $this->assertCount(1, $detailUrl);

        $this->assertSame(
            route('admin.attendance.detail', $this->attendance->id),
            $detailUrl->attr('href')
        );

        $detailResponse = $this->actingAs($this->admin)->get($detailUrl->attr('href'));
        $detailResponse->assertStatus(200);
    }
}
