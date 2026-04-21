<?php

namespace Tests\Feature\CorrectionRequest;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\RequestBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setLocale('ja');

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

    public function test_pending_requests_are_displayed_on_admin_list(): void
    {
        $attendance1 = Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'work_date' => '2026-04-10',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $this->user2->id,
            'work_date' => '2026-04-11',
        ]);

        $pendingRequest1 = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'requested_by' => $this->user1->id,
            'status' => 'pending',
            'requested_clock_in_at' => '09:30:00',
            'requested_clock_out_at' => '18:30:00',
            'note' => '承認待ち1',
        ]);

        $pendingRequest2 = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance2->id,
            'requested_by' => $this->user2->id,
            'status' => 'pending',
            'requested_clock_in_at' => '10:00:00',
            'requested_clock_out_at' => '19:00:00',
            'note' => '承認待ち2',
        ]);

        $approvedRequest = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'requested_by' => $this->user1->id,
            'status' => 'approved',
            'requested_clock_in_at' => '08:45:00',
            'requested_clock_out_at' => '17:45:00',
            'note' => '承認済み1',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.stamp_correction_request.list', ['status' => 'pending']));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row1 = $crawler->filter('[data-testid="admin_request_row_' . $pendingRequest1->id . '"]');
        $this->assertCount(1, $row1);
        $this->assertSame('承認待ち', trim($row1->filter('[data-testid="admin_request_status"]')->text()));
        $this->assertSame('山田太郎', trim($row1->filter('[data-testid="admin_request_name"]')->text()));
        $this->assertSame('2026/04/10', trim($row1->filter('[data-testid="admin_request_work_date"]')->text()));
        $this->assertSame('承認待ち1', trim($row1->filter('[data-testid="admin_request_note"]')->text()));

        $row2 = $crawler->filter('[data-testid="admin_request_row_' . $pendingRequest2->id . '"]');
        $this->assertCount(1, $row2);
        $this->assertSame('承認待ち', trim($row2->filter('[data-testid="admin_request_status"]')->text()));
        $this->assertSame('佐藤花子', trim($row2->filter('[data-testid="admin_request_name"]')->text()));
        $this->assertSame('2026/04/11', trim($row2->filter('[data-testid="admin_request_work_date"]')->text()));
        $this->assertSame('承認待ち2', trim($row2->filter('[data-testid="admin_request_note"]')->text()));

        $approvedRow = $crawler->filter('[data-testid="admin_request_row_' . $approvedRequest->id . '"]');
        $this->assertCount(0, $approvedRow);
    }

    public function test_approved_requests_are_displayed_on_admin_list(): void
    {
        $attendance1 = Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'work_date' => '2026-04-10',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $this->user2->id,
            'work_date' => '2026-04-11',
        ]);

        $approvedRequest1 = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'requested_by' => $this->user1->id,
            'status' => 'approved',
            'requested_clock_in_at' => '08:45:00',
            'requested_clock_out_at' => '17:45:00',
            'note' => '承認済み1',
        ]);

        $approvedRequest2 = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance2->id,
            'requested_by' => $this->user2->id,
            'status' => 'approved',
            'requested_clock_in_at' => '09:00:00',
            'requested_clock_out_at' => '18:00:00',
            'note' => '承認済み2',
        ]);

        $pendingRequest = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'requested_by' => $this->user1->id,
            'status' => 'pending',
            'requested_clock_in_at' => '09:30:00',
            'requested_clock_out_at' => '18:30:00',
            'note' => '承認待ち1',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.stamp_correction_request.list', ['status' => 'approved']));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row1 = $crawler->filter('[data-testid="admin_request_row_' . $approvedRequest1->id . '"]');
        $this->assertCount(1, $row1);
        $this->assertSame('承認済み', trim($row1->filter('[data-testid="admin_request_status"]')->text()));
        $this->assertSame('山田太郎', trim($row1->filter('[data-testid="admin_request_name"]')->text()));
        $this->assertSame('2026/04/10', trim($row1->filter('[data-testid="admin_request_work_date"]')->text()));
        $this->assertSame('承認済み1', trim($row1->filter('[data-testid="admin_request_note"]')->text()));

        $row2 = $crawler->filter('[data-testid="admin_request_row_' . $approvedRequest2->id . '"]');
        $this->assertCount(1, $row2);
        $this->assertSame('承認済み', trim($row2->filter('[data-testid="admin_request_status"]')->text()));
        $this->assertSame('佐藤花子', trim($row2->filter('[data-testid="admin_request_name"]')->text()));
        $this->assertSame('2026/04/11', trim($row2->filter('[data-testid="admin_request_work_date"]')->text()));
        $this->assertSame('承認済み2', trim($row2->filter('[data-testid="admin_request_note"]')->text()));

        $pendingRow = $crawler->filter('[data-testid="admin_request_row_' . $pendingRequest->id . '"]');
        $this->assertCount(0, $pendingRow);
    }

    public function test_admin_can_view_correction_request_detail(): void
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'work_date' => '2026-04-10',
        ]);

        $correctionRequest = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_by' => $this->user1->id,
            'status' => 'pending',
            'requested_clock_in_at' => '09:30:00',
            'requested_clock_out_at' => '18:30:00',
            'note' => '電車遅延のため',
        ]);

        $requestBreak = RequestBreak::factory()->create([
            'correction_request_id' => $correctionRequest->id,
            'break_no' => 1,
            'requested_break_start_at' => '12:15:00',
            'requested_break_end_at' => '13:15:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.stamp_correction_request.detail', $correctionRequest->id));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $nameRow = $crawler->filter('[data-testid="admin_detail_name_row"]');
        $this->assertCount(1, $nameRow);
        $this->assertSame('山田太郎', trim($nameRow->filter('[data-testid="admin_detail_name"]')->text()));

        $dateRow = $crawler->filter('[data-testid="admin_detail_date_row"]');
        $this->assertCount(1, $dateRow);
        $this->assertStringContainsString('2026年', $dateRow->filter('[data-testid="admin_detail_date"]')->text());
        $this->assertStringContainsString('4月10日', $dateRow->filter('[data-testid="admin_detail_date"]')->text());

        $clockInOutRow = $crawler->filter('[data-testid="admin_detail_clock_inout_row"]');
        $this->assertCount(1, $clockInOutRow);
        $this->assertSame('09:30', trim($clockInOutRow->filter('[data-testid="admin_detail_clock_in_at"]')->text()));
        $this->assertSame('18:30', trim($clockInOutRow->filter('[data-testid="admin_detail_clock_out_at"]')->text()));

        $breakRow = $crawler->filter('[data-testid="admin_detail_break_row_0"]');
        $this->assertCount(1, $breakRow);
        $this->assertSame('12:15', trim($breakRow->filter('[data-testid="admin_detail_break_start_at"]')->text()));
        $this->assertSame('13:15', trim($breakRow->filter('[data-testid="admin_detail_break_end_at"]')->text()));

        $noteRow = $crawler->filter('[data-testid="admin_detail_note_row"]');
        $this->assertCount(1, $noteRow);
        $this->assertSame('電車遅延のため', trim($noteRow->filter('[data-testid="admin_detail_note"]')->text()));
    }

    public function test_admin_can_approve_correction_request_and_attendance_is_updated(): void
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'work_date' => '2026-04-10',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_no' => 1,
        ]);

        $correctionRequest = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_by' => $this->user1->id,
            'status' => 'pending',
            'requested_clock_in_at' => '09:30:00',
            'requested_clock_out_at' => '18:30:00',
            'note' => '電車遅延のため',
        ]);

        RequestBreak::factory()->create([
            'correction_request_id' => $correctionRequest->id,
            'break_no' => 1,
            'requested_break_start_at' => '12:15:00',
            'requested_break_end_at' => '13:15:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.stamp_correction_request.approve', $correctionRequest->id));

        $response->assertStatus(302);

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in_at' => '09:30:00',
            'clock_out_at' => '18:30:00',
        ]);

        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => '12:15:00',
            'break_end_at' => '13:15:00',
        ]);
    }
}
