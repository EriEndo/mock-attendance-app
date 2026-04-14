<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $users = User::where('role', 'user')->get();
        $period = CarbonPeriod::create('2026-03-01', '2026-04-10');

        foreach ($users as $user) {
            foreach ($period as $date) {
                if ($date->isWeekend()) {
                    continue;
                }

                $attendanceData = $this->getAttendanceDataByWeekday($date->dayOfWeek);

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date->format('Y-m-d'),
                    'clock_in_at' => $attendanceData['clock_in_at'],
                    'clock_out_at' => $attendanceData['clock_out_at'],
                ]);

                foreach ($attendanceData['breaks'] as $index => $break) {
                    $attendance->breakTimes()->create([
                        'break_no' => $index + 1,
                        'break_start_at' => $break['break_start_at'],
                        'break_end_at' => $break['break_end_at'],
                    ]);
                }
            }
        }
    }

    private function getAttendanceDataByWeekday(int $dayOfWeek): array
    {
        switch ($dayOfWeek) {
            case 1:
            case 5:
                return [
                    'clock_in_at' => '09:00:00',
                    'clock_out_at' => '18:00:00',
                    'breaks' => [
                        [
                            'break_start_at' => '12:00:00',
                            'break_end_at' => '13:00:00',
                        ],
                    ],
                ];

            case 2:
                return [
                    'clock_in_at' => '10:00:00',
                    'clock_out_at' => '18:00:00',
                    'breaks' => [
                        [
                            'break_start_at' => '12:00:00',
                            'break_end_at' => '13:00:00',
                        ],
                    ],
                ];

            case 3:
                return [
                    'clock_in_at' => '09:00:00',
                    'clock_out_at' => '18:00:00',
                    'breaks' => [
                        [
                            'break_start_at' => '11:30:00',
                            'break_end_at' => '12:00:00',
                        ],
                        [
                            'break_start_at' => '12:30:00',
                            'break_end_at' => '13:00:00',
                        ],
                    ],
                ];

            case 4:
                return [
                    'clock_in_at' => '09:00:00',
                    'clock_out_at' => '18:00:00',
                    'breaks' => [],
                ];

            default:
                return [
                    'clock_in_at' => '09:00:00',
                    'clock_out_at' => '18:00:00',
                    'breaks' => [
                        [
                            'break_start_at' => '12:00:00',
                            'break_end_at' => '13:00:00',
                        ],
                    ],
                ];
        }
    }
}
