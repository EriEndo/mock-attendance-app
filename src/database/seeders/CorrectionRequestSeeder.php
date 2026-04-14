<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\RequestBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CorrectionRequestSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $users = User::where('role', 'user')->get();

        $patterns = [
            'late_clock_in',
            'early_clock_out',
            'extend_break',
            'delete_break',
            'add_break',
        ];

        foreach ($users as $user) {
            $targets = $this->getTargetAttendances($user->id);

            foreach ($targets as $index => $attendance) {
                $pattern = $patterns[$index % count($patterns)];
                $status = mt_rand(0, 1) ? 'approved' : 'pending';

                $requestData = $this->buildRequestData(
                    $attendance,
                    $pattern,
                    $user->id,
                    $admin->id,
                    $status
                );

                DB::transaction(function () use ($attendance, $requestData, $status) {
                    $correctionRequest = CorrectionRequest::create([
                        'attendance_id' => $attendance->id,
                        'requested_by' => $requestData['requested_by'],
                        'request_type' => 'user_request',
                        'status' => $status,
                        'requested_clock_in_at' => $requestData['requested_clock_in_at'],
                        'requested_clock_out_at' => $requestData['requested_clock_out_at'],
                        'note' => $requestData['note'],
                        'approved_by' => $requestData['approved_by'],
                        'approved_at' => $requestData['approved_at'],
                    ]);

                    foreach ($requestData['breaks'] as $i => $break) {
                        RequestBreak::create([
                            'correction_request_id' => $correctionRequest->id,
                            'break_no' => $i + 1,
                            'requested_break_start_at' => $break['requested_break_start_at'],
                            'requested_break_end_at' => $break['requested_break_end_at'],
                        ]);
                    }

                    if ($status === 'approved') {
                        $this->applyApprovedRequest($attendance, $requestData);
                    }
                });
            }
        }
    }

    private function getTargetAttendances(int $userId): array
    {
        return Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->whereBetween('work_date', ['2026-02-01', '2026-04-10'])
            ->get()
            ->filter(function ($attendance) {
                $dayOfWeek = Carbon::parse($attendance->work_date)->dayOfWeek;
                return in_array($dayOfWeek, [1, 2]); // 月曜・火曜
            })
            ->sortBy('work_date')
            ->values()
            ->all();
    }

    private function buildRequestData(
        Attendance $attendance,
        string $pattern,
        int $userId,
        int $adminId,
        string $status
    ): array {
        $baseBreaks = $attendance->breakTimes
            ->sortBy('break_no')
            ->map(function ($break) {
                return [
                    'requested_break_start_at' => $break->break_start_at,
                    'requested_break_end_at' => $break->break_end_at,
                ];
            })
            ->values()
            ->toArray();

        $data = [
            'requested_by' => $userId,
            'requested_clock_in_at' => $attendance->clock_in_at,
            'requested_clock_out_at' => $attendance->clock_out_at,
            'note' => '',
            'approved_by' => $status === 'approved' ? $adminId : null,
            'approved_at' => $status === 'approved' ? now() : null,
            'breaks' => $baseBreaks,
        ];

        switch ($pattern) {
            case 'late_clock_in':
                $data['requested_clock_in_at'] = '10:00:00';
                $data['note'] = '電車遅延のため';
                break;

            case 'early_clock_out':
                $data['requested_clock_out_at'] = '17:00:00';
                $data['note'] = '体調不良のため早退';
                break;

            case 'extend_break':
                $data['breaks'] = [
                    [
                        'requested_break_start_at' => '12:00:00',
                        'requested_break_end_at' => '13:30:00',
                    ],
                ];
                $data['note'] = '中抜けのため';
                break;

            case 'delete_break':
                $data['breaks'] = [];
                $data['note'] = '1日会議だったため';
                break;

            case 'add_break':
                $data['breaks'] = [
                    [
                        'requested_break_start_at' => '12:00:00',
                        'requested_break_end_at' => '13:00:00',
                    ],
                    [
                        'requested_break_start_at' => '15:00:00',
                        'requested_break_end_at' => '15:30:00',
                    ],
                ];
                $data['note'] = '外出する都合があったため';
                break;
        }

        return $data;
    }

    private function applyApprovedRequest(Attendance $attendance, array $requestData): void
    {
        $attendance->update([
            'clock_in_at' => $requestData['requested_clock_in_at'],
            'clock_out_at' => $requestData['requested_clock_out_at'],
        ]);

        $attendance->breakTimes()->delete();

        foreach ($requestData['breaks'] as $index => $break) {
            $attendance->breakTimes()->create([
                'break_no' => $index + 1,
                'break_start_at' => $break['requested_break_start_at'],
                'break_end_at' => $break['requested_break_end_at'],
            ]);
        }
    }
}
