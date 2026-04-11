<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\User;
use App\Models\Attendance;

class StaffController extends Controller
{
   public function staffList()
{
    $staffs = User::where('role', 'user')
    ->select('id', 'name', 'email')
    ->get();

    return view('staff.list', compact('staffs'));
}

public function staffAttendance(Request $request, $id)
{

    $staff = User::findOrFail($id);

    $targetMonth = $request->query('month')
        ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
        : now()->startOfMonth();
    $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');
    $startOfMonth = $targetMonth->copy()->startOfMonth();
    $endOfMonth = $targetMonth->copy()->endOfMonth();

    $attendances = Attendance::with('breakTimes')
        ->where('user_id', $staff->id)
        ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
        ->get()
        ->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));

    $days = [];

    
    foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $date){
        $attendance = $attendances->get($date->format('Y-m-d'));
        $hasAttendance = (bool) $attendance;
        $canShowSummary = $hasAttendance && $attendance?->clock_out_at;

        $breakTotalMinutes = $attendance
            ? $attendance->breakTimes->sum(fn($break) => $this->getBreakMinutes($break))
            : 0;

        $workTotalMinutes = ($attendance && $attendance->clock_in_at && $attendance->clock_out_at)
            ? $this->timeToMinutes($attendance->clock_out_at)
                - $this->timeToMinutes($attendance->clock_in_at)
                - $breakTotalMinutes
            : 0;

        $days[] = [
            'row_class' => $hasAttendance ? 'worked-day' : 'empty-day',
            'date_label' => $date->isoFormat('M/D(ddd)'),
            'clock_in_at' => $attendance?->clock_in_at?->format('H:i') ?? '',
            'clock_out_at' => $attendance?->clock_out_at?->format('H:i') ?? '',
            'break_time' => $canShowSummary
                ? $this->formatMinutesToHoursMinutes($breakTotalMinutes)
                : '',
            'work_time' => $canShowSummary
                ? $this->formatMinutesToHoursMinutes($workTotalMinutes)
                : '',
            'detail_url' => $attendance ? route('admin.attendance.detail', $attendance->id) : '',
        ];
    }

    return view('staff.attendancelist', compact(
        'staff',
        'targetMonth',
        'prevMonth',
        'nextMonth',
        'days'
    ));
}



public function exportCsv(Request $request, $id)
{
    $staff = User::findOrFail($id);

    $targetMonth = $request->query('month')
        ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
        : now()->startOfMonth();

    $startOfMonth = $targetMonth->copy()->startOfMonth();
    $endOfMonth = $targetMonth->copy()->endOfMonth();

    $attendances = Attendance::with('breakTimes')
        ->where('user_id', $staff->id)
        ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
        ->get()
        ->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));

    $filename = $staff->name . '_' . $targetMonth->format('Y_m') . '_attendance.csv';

    $headers = [
        'Content-Type' => 'text/csv; charset=Shift_JIS',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];

    $callback = function () use ($attendances, $startOfMonth, $endOfMonth) {
        $handle = fopen('php://output', 'w');

        $this->writeCsvRow($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

        foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $date) {
            $attendance = $attendances->get($date->format('Y-m-d'));
            $canShowSummary = $attendance && $attendance->clock_out_at;

            $breakTotalMinutes = $attendance
                ? $attendance->breakTimes->sum(fn($break) => $this->getBreakMinutes($break))
                : 0;

            $workTotalMinutes = ($attendance && $attendance->clock_in_at && $attendance->clock_out_at)
                ? $this->timeToMinutes($attendance->clock_out_at)
                    - $this->timeToMinutes($attendance->clock_in_at)
                    - $breakTotalMinutes
                : 0;

            $this->writeCsvRow($handle, [
                $date->format('Y/m/d'),
                $attendance?->clock_in_at?->format('H:i') ?? '',
                $attendance?->clock_out_at?->format('H:i') ?? '',
                $canShowSummary
                    ? $this->formatMinutesToHoursMinutes($breakTotalMinutes)
                    : '',
                $canShowSummary
                    ? $this->formatMinutesToHoursMinutes($workTotalMinutes)
                    : '',
            ]);
        }

        fclose($handle);
    };

    return response()->stream($callback, 200, $headers);
}

private function getBreakMinutes($break): int
{
    if (!$break->break_start_at || !$break->break_end_at) {
        return 0;
    }
   return $this->timeToMinutes($break->break_end_at)
    - $this->timeToMinutes($break->break_start_at);
}

private function timeToMinutes(Carbon $time): int
{
    return $time->hour * 60 + $time->minute;
}

private function formatMinutesToHoursMinutes(int $minutes): string
{
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

private function writeCsvRow($handle, array $row)
{
    $temp = fopen('php://temp', 'r+');
    fputcsv($temp, $row);
    rewind($temp);

    $csvLine = stream_get_contents($temp);
    fclose($temp);

    $csvLine = str_replace("\n", "\r\n", $csvLine);

    fwrite($handle, mb_convert_encoding($csvLine, 'SJIS-win', 'UTF-8'));
}
}
