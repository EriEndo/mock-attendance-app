<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->user = User::factory()->create([
            'name' => '山田太郎',
            'email_verified_at' => now(),
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::create(2026, 4, 10),
            'clock_in_at' =>  '09:00:00',
            'clock_out_at' => '18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'break_no' => 1,
            'break_start_at' =>  '12:00:00',
            'break_end_at' =>  '13:00:00',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'clock_in_at' => '09:30',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'start' => '12:00',
                    'end' => '13:00',
                ],
                [
                    'start' => '',
                    'end' => '',
                ],
            ],
            'note' => '管理者修正',
        ], $overrides);
    }

    public function test_admin_can_view_selected_attendance_detail(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.detail', $this->attendance->id));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $name = $crawler->filter('[data-testid="detail_name"]');
        $this->assertCount(1, $name);
        $this->assertSame('山田太郎', trim($name->text()));

        $date = $crawler->filter('[data-testid="detail_date"]');
        $this->assertCount(1, $date);
        $this->assertStringContainsString('2026年', $date->text());
        $this->assertStringContainsString('4月10日', $date->text());

        $clockIn = $crawler->filter('[data-testid="detail_clock_in_at"]');
        $this->assertCount(1, $clockIn);
        $this->assertSame('09:00', $clockIn->attr('value'));

        $clockOut = $crawler->filter('[data-testid="detail_clock_out_at"]');
        $this->assertCount(1, $clockOut);
        $this->assertSame('18:00', $clockOut->attr('value'));

        $breakStart = $crawler->filter('[data-testid="detail_break_start_at"]');
        $this->assertCount(1, $breakStart);
        $this->assertSame('12:00', $breakStart->attr('value'));

        $breakEnd = $crawler->filter('[data-testid="detail_break_end_at"]');
        $this->assertCount(1, $breakEnd);
        $this->assertSame('13:00', $breakEnd->attr('value'));
    }

    public function test_validation_error_is_shown_when_clock_in_is_after_clock_out(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.attendance.detail', $this->attendance->id))
            ->patch(route('admin.attendance.update', $this->attendance->id), $this->validPayload([
                'clock_in_at' => '19:00',
                'clock_out_at' => '18:00',
            ]));

        $response->assertRedirect(route('admin.attendance.detail', $this->attendance->id));

        $response->assertSessionHasErrors([
            'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_shown_when_break_start_is_after_clock_out(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.attendance.detail', $this->attendance->id))
            ->patch(route('admin.attendance.update', $this->attendance->id), $this->validPayload([
                'breaks' => [
                    [
                        'start' => '18:30',
                        'end' => '19:00',
                    ],
                    [
                        'start' => '',
                        'end' => '',
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.attendance.detail', $this->attendance->id));

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_shown_when_break_end_is_after_clock_out(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.attendance.detail', $this->attendance->id))
            ->patch(route('admin.attendance.update', $this->attendance->id), $this->validPayload([
                'breaks' => [
                    [
                        'start' => '17:30',
                        'end' => '18:30',
                    ],
                    [
                        'start' => '',
                        'end' => '',
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.attendance.detail', $this->attendance->id));

        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_shown_when_note_is_empty(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.attendance.detail', $this->attendance->id))
            ->patch(route('admin.attendance.update', $this->attendance->id), $this->validPayload([
                'note' => '',
            ]));

        $response->assertRedirect(route('admin.attendance.detail', $this->attendance->id));

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    public function test_attendance_record_is_updated_after_admin_submits_correction(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.attendance.detail', $this->attendance->id))
            ->patch(route('admin.attendance.update', $this->attendance->id), [
                'clock_in_at' => '09:30',
                'clock_out_at' => '18:30',
                'breaks' => [
                    [
                        'start' => '12:15',
                        'end' => '13:15',
                    ],
                    [
                        'start' => '',
                        'end' => '',
                    ],
                ],
                'note' => '電車遅延のため管理者修正',
            ]);

        $response->assertRedirect(route('admin.attendance.detail', $this->attendance->id));

        $this->attendance->refresh();

        $this->assertSame('09:30:00', $this->attendance->clock_in_at->format('H:i:s'));
        $this->assertSame('18:30:00', $this->attendance->clock_out_at->format('H:i:s'));

        $break = BreakTime::where('attendance_id', $this->attendance->id)
            ->where('break_no', 1)
            ->first();

        $this->assertNotNull($break);
        $this->assertSame('12:15:00', $break->break_start_at->format('H:i:s'));
        $this->assertSame('13:15:00', $break->break_end_at->format('H:i:s'));

        $listResponse = $this->actingAs($this->admin)
            ->get(route('admin.attendance.list', ['date' => '2026-04-10']));

        $listResponse->assertStatus(200);

        $crawler = new Crawler($listResponse->getContent());

        $row = $crawler->filter('[data-testid="admin_attendance_row_' . $this->attendance->id . '"]');
        $this->assertCount(1, $row);

        $name = $row->filter('[data-testid="admin_name"]');
        $this->assertCount(1, $name);
        $this->assertSame('山田太郎', trim($name->text()));

        $clockIn = $row->filter('[data-testid="admin_clock_in_at"]');
        $this->assertCount(1, $clockIn);
        $this->assertSame('09:30', trim($clockIn->text()));

        $clockOut = $row->filter('[data-testid="admin_clock_out_at"]');
        $this->assertCount(1, $clockOut);
        $this->assertSame('18:30', trim($clockOut->text()));

        $breakTime = $row->filter('[data-testid="admin_break_time"]');
        $this->assertCount(1, $breakTime);
        $this->assertSame('01:00', trim($breakTime->text()));

        $workTime = $row->filter('[data-testid="admin_work_time"]');
        $this->assertCount(1, $workTime);
        $this->assertSame('08:00', trim($workTime->text()));
    }
}
