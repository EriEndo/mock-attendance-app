<?php

namespace Tests\Feature\CorrectionRequest;

use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\DomCrawler\Crawler;

class UserCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setLocale('ja');

        $this->user = User::factory()->create([
            'name' => '山田太郎',
            'email_verified_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::create(2026, 4, 10),
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
            'note' => '電車遅延のため',
        ], $overrides);
    }

    public function test_validation_error_is_shown_when_clock_in_is_after_clock_out(): void
    {
        $detailResponse = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance->id));

        $detailResponse->assertStatus(200);

        $response = $this->actingAs($this->user)
            ->from(route('attendance.detail', $this->attendance->id))
            ->post(route('stamp_correction_request.store', $this->attendance->id), $this->validPayload([
                'clock_in_at' => '19:00',
                'clock_out_at' => '18:00',
            ]));

        $response->assertRedirect(route('attendance.detail', $this->attendance->id));
        $response->assertSessionHasErrors([
            'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_shown_when_break_start_is_after_clock_out(): void
    {
        $detailResponse = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance->id));

        $detailResponse->assertStatus(200);

        $response = $this->actingAs($this->user)
            ->from(route('attendance.detail', $this->attendance->id))
            ->post(route('stamp_correction_request.store', $this->attendance->id), $this->validPayload([
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

        $response->assertRedirect(route('attendance.detail', $this->attendance->id));
        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_shown_when_break_end_is_after_clock_out(): void
    {
        $detailResponse = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance->id));

        $detailResponse->assertStatus(200);

        $response = $this->actingAs($this->user)
            ->from(route('attendance.detail', $this->attendance->id))
            ->post(route('stamp_correction_request.store', $this->attendance->id), $this->validPayload([
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

        $response->assertRedirect(route('attendance.detail', $this->attendance->id));
        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_shown_when_note_is_empty(): void
    {
        $detailResponse = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance->id));

        $detailResponse->assertStatus(200);

        $response = $this->actingAs($this->user)
            ->from(route('attendance.detail', $this->attendance->id))
            ->post(route('stamp_correction_request.store', $this->attendance->id), $this->validPayload([
                'note' => '',
            ]));

        $response->assertRedirect(route('attendance.detail', $this->attendance->id));
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    public function test_correction_request_is_created_and_displayed_on_admin_detail_and_list(): void
    {
        $detailResponse = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance->id));

        $detailResponse->assertStatus(200);

        $storeResponse = $this->actingAs($this->user)
            ->post(route('stamp_correction_request.store', $this->attendance->id), $this->validPayload());

        $storeResponse->assertRedirect();

        $correctionRequest = CorrectionRequest::latest('id')->first();

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'attendance_id' => $this->attendance->id,
            'requested_by' => $this->user->id,
            'status' => 'pending',
            'requested_clock_in_at' => '09:30:00',
            'requested_clock_out_at' => '18:00:00',
            'note' => '電車遅延のため',
        ]);

        $adminDetailResponse = $this->actingAs($this->admin)
            ->get(route('admin.stamp_correction_request.detail', $correctionRequest->id));

        $adminDetailResponse->assertStatus(200);

        $detailCrawler = new Crawler($adminDetailResponse->getContent());

        $nameRow = $detailCrawler->filter('[data-testid="admin_detail_name_row"]');
        $this->assertCount(1, $nameRow);
        $this->assertSame('山田太郎', trim($nameRow->filter('[data-testid="admin_detail_name"]')->text()));

        $clockRow = $detailCrawler->filter('[data-testid="admin_detail_clock_inout_row"]');
        $this->assertCount(1, $clockRow);
        $this->assertSame('09:30', trim($clockRow->filter('[data-testid="admin_detail_clock_in_at"]')->text()));
        $this->assertSame('18:00', trim($clockRow->filter('[data-testid="admin_detail_clock_out_at"]')->text()));

        $noteRow = $detailCrawler->filter('[data-testid="admin_detail_note_row"]');
        $this->assertCount(1, $noteRow);
        $this->assertSame('電車遅延のため', trim($noteRow->filter('[data-testid="admin_detail_note"]')->text()));

        $adminListResponse = $this->actingAs($this->admin)
            ->get(route('admin.stamp_correction_request.list', ['status' => 'pending']));

        $adminListResponse->assertStatus(200);

        $listCrawler = new Crawler($adminListResponse->getContent());

        $listRow = $listCrawler->filter('[data-testid="admin_request_row_' . $correctionRequest->id . '"]');
        $this->assertCount(1, $listRow);
        $this->assertSame('承認待ち', trim($listRow->filter('[data-testid="admin_request_status"]')->text()));
        $this->assertSame('山田太郎', trim($listRow->filter('[data-testid="admin_request_name"]')->text()));
        $this->assertSame('2026/04/10', trim($listRow->filter('[data-testid="admin_request_work_date"]')->text()));
        $this->assertSame('電車遅延のため', trim($listRow->filter('[data-testid="admin_request_note"]')->text()));
    }

    public function test_pending_requests_are_displayed_on_user_list(): void
    {
        $detailResponse1 = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance->id));

        $detailResponse1->assertStatus(200);

        $this->actingAs($this->user)
            ->post(route('stamp_correction_request.store', $this->attendance->id), $this->validPayload([
                'note' => '承認待ち1',
            ]))
            ->assertRedirect();

        $pendingRequest = CorrectionRequest::latest('id')->first();

        $attendance2 = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => '2026-04-11',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]);

        $detailResponse2 = $this->actingAs($this->user)
            ->get(route('attendance.detail', $attendance2->id));

        $detailResponse2->assertStatus(200);

        $this->actingAs($this->user)
            ->post(route('stamp_correction_request.store', $attendance2->id), [
                'clock_in_at' => '09:15',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'start' => '',
                        'end' => '',
                    ],
                    [
                        'start' => '',
                        'end' => '',
                    ],
                ],
                'note' => '承認済み1',
            ])
            ->assertRedirect();

        $approvedRequest = CorrectionRequest::latest('id')->first();

        $this->actingAs($this->admin)
            ->patch(route('admin.stamp_correction_request.approve', $approvedRequest->id))
            ->assertRedirect();

        $response = $this->actingAs($this->user)
            ->get(route('stamp_correction_request.list', ['status' => 'pending']));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="user_request_row_' . $pendingRequest->id . '"]');
        $this->assertCount(1, $row);
        $this->assertSame('承認待ち', trim($row->filter('[data-testid="user_request_status"]')->text()));
        $this->assertSame('山田太郎', trim($row->filter('[data-testid="user_request_name"]')->text()));
        $this->assertSame('2026/04/10', trim($row->filter('[data-testid="user_request_work_date"]')->text()));
        $this->assertSame('承認待ち1', trim($row->filter('[data-testid="user_request_note"]')->text()));

        $approvedRow = $crawler->filter('[data-testid="user_request_row_' . $approvedRequest->id . '"]');
        $this->assertCount(0, $approvedRow);
    }

    public function test_approved_requests_are_displayed_on_user_list(): void
    {
        $detailResponse1 = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance->id));

        $detailResponse1->assertStatus(200);

        $this->actingAs($this->user)
            ->post(route('stamp_correction_request.store', $this->attendance->id), $this->validPayload([
                'note' => '承認済み1',
            ]))
            ->assertRedirect();

        $approvedRequest = CorrectionRequest::latest('id')->first();

        $this->actingAs($this->admin)
            ->patch(route('admin.stamp_correction_request.approve', $approvedRequest->id))
            ->assertRedirect();

        $attendance2 = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => '2026-04-11',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]);

        $detailResponse2 = $this->actingAs($this->user)
            ->get(route('attendance.detail', $attendance2->id));

        $detailResponse2->assertStatus(200);

        $this->actingAs($this->user)
            ->post(route('stamp_correction_request.store', $attendance2->id), [
                'clock_in_at' => '09:15',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'start' => '',
                        'end' => '',
                    ],
                    [
                        'start' => '',
                        'end' => '',
                    ],
                ],
                'note' => '承認待ち1',
            ])
            ->assertRedirect();

        $pendingRequest = CorrectionRequest::latest('id')->first();

        $response = $this->actingAs($this->user)
            ->get(route('stamp_correction_request.list', ['status' => 'approved']));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $row = $crawler->filter('[data-testid="user_request_row_' . $approvedRequest->id . '"]');
        $this->assertCount(1, $row);
        $this->assertSame('承認済み', trim($row->filter('[data-testid="user_request_status"]')->text()));
        $this->assertSame('山田太郎', trim($row->filter('[data-testid="user_request_name"]')->text()));
        $this->assertSame('2026/04/10', trim($row->filter('[data-testid="user_request_work_date"]')->text()));
        $this->assertSame('承認済み1', trim($row->filter('[data-testid="user_request_note"]')->text()));

        $pendingRow = $crawler->filter('[data-testid="user_request_row_' . $pendingRequest->id . '"]');
        $this->assertCount(0, $pendingRow);
    }

    public function test_user_can_view_correction_request_detail(): void
    {
        $detailResponse = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance->id));

        $detailResponse->assertStatus(200);

        $this->actingAs($this->user)
            ->post(route('stamp_correction_request.store', $this->attendance->id), $this->validPayload([
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
                'note' => '電車遅延のため',
            ]))
            ->assertRedirect();

        $correctionRequest = CorrectionRequest::latest('id')->first();

        $response = $this->actingAs($this->user)
            ->get(route('stamp_correction_request.detail', $correctionRequest->id));

        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $nameRow = $crawler->filter('[data-testid="user_detail_name_row"]');
        $this->assertCount(1, $nameRow);
        $this->assertSame($this->user->name, trim($nameRow->filter('[data-testid="user_detail_name"]')->text()));

        $dateRow = $crawler->filter('[data-testid="user_detail_date_row"]');
        $this->assertCount(1, $dateRow);
        $this->assertStringContainsString('2026年', $dateRow->filter('[data-testid="user_detail_date"]')->text());
        $this->assertStringContainsString('4月10日', $dateRow->filter('[data-testid="user_detail_date"]')->text());

        $clockRow = $crawler->filter('[data-testid="user_detail_clock_inout_row"]');
        $this->assertCount(1, $clockRow);
        $this->assertSame('09:30', trim($clockRow->filter('[data-testid="user_detail_clock_in_at"]')->text()));
        $this->assertSame('18:30', trim($clockRow->filter('[data-testid="user_detail_clock_out_at"]')->text()));

        $breakRow = $crawler->filter('[data-testid="user_detail_break_row_0"]');
        $this->assertCount(1, $breakRow);
        $this->assertSame('12:15', trim($breakRow->filter('[data-testid="user_detail_break_start_at"]')->text()));
        $this->assertSame('13:15', trim($breakRow->filter('[data-testid="user_detail_break_end_at"]')->text()));

        $noteRow = $crawler->filter('[data-testid="user_detail_note_row"]');
        $this->assertCount(1, $noteRow);
        $this->assertSame('電車遅延のため', trim($noteRow->filter('[data-testid="user_detail_note"]')->text()));
    }
}
