<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\CorrectionRequest;

class AttendanceViewController extends Controller
{
    public function userList(Request $request)
    {
        $user = Auth::user();

        $targetMonth = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();
        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');
        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));

        $days = [];

        foreach (CarbonPeriod::create($startOfMonth, $endOfMonth) as $date) {
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
                'work_date' => $date->format('Y-m-d'),
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
                'detail_url' => $attendance ? route('attendance.detail', $attendance->id) : '',
            ];
        }

        return view('attendance.list', compact(
            'targetMonth',
            'prevMonth',
            'nextMonth',
            'days'
        ));
    }


    public function adminList(Request $request)
    {
        $targetDate = $request->query('date')
            ? Carbon::createFromFormat('Y-m-d', $request->query('date'))->startOfDay()
            : now()->startOfDay();
        $prevDate = $targetDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $targetDate->copy()->addDay()->format('Y-m-d');

        $attendances = Attendance::with(['user', 'breakTimes', 'correctionRequests'])
            ->whereDate('work_date', $targetDate->toDateString())
            ->get();

        foreach ($attendances as $attendance) {
            $breakTotalMinutes = $attendance->breakTimes->sum(
                fn($break) => $this->getBreakMinutes($break)
            );

            $workTotalMinutes = ($attendance->clock_in_at && $attendance->clock_out_at)
                ? $this->timeToMinutes($attendance->clock_out_at)
                - $this->timeToMinutes($attendance->clock_in_at)
                - $breakTotalMinutes
                : 0;

            $canShowSummary = $attendance->clock_out_at;
            $attendance->break_time = $canShowSummary
                ? $this->formatMinutesToHoursMinutes($breakTotalMinutes)
                : '';
            $attendance->work_time = $canShowSummary
                ? $this->formatMinutesToHoursMinutes($workTotalMinutes)
                : '';
            $pendingRequest = $attendance->correctionRequests
                ->where('status', 'pending')
                ->sortByDesc('created_at')
                ->first();

            $attendance->detail_url = $pendingRequest
                ? route('admin.stamp_correction_request.detail', $pendingRequest->id)
                : route('admin.attendance.detail', $attendance->id);
        }

        return view('attendance.admin.list', compact(
            'targetDate',
            'prevDate',
            'nextDate',
            'attendances'
        ));
    }


    public function userDetail($id)
    {
        $attendance = Attendance::with(['user', 'breakTimes'])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $pendingRequest = CorrectionRequest::with('requestBreaks')
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingRequest) {
            return view('attendance.pending-detail', compact('attendance', 'pendingRequest'));
        }

        return view('attendance.detail', compact('attendance', 'pendingRequest'));
    }


    public function adminDetail($id)
    {
        $attendance = Attendance::with([
            'user',
            'breakTimes',
            'correctionRequests.requestBreaks',
        ])->findOrFail($id);

        $pendingRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();

        if ($pendingRequest) {
            return view('attendance.admin.pending-detail', compact('attendance', 'pendingRequest'));
        }

        return view('attendance.admin.detail', compact('attendance', 'pendingRequest'));
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
}
