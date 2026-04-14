<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceActionController extends Controller
{
    public function index()
    {
        $attendance = $this->getTodayAttendance();

        $status = $this->determineStatus($attendance);

        return view('attendance.index', compact('attendance', 'status'));
    }


    public function clockIn()
    {
        $attendance = $this->getTodayAttendance();

        if ($attendance) {
            return back();
        }

        Attendance::create([
            'user_id' => Auth::id(),
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    public function clockOut()
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance) {
            return back();
        }

        if ($attendance->clock_out_at) {
            return back();
        }

        $isBreaking = $attendance->breakTimes()
            ->whereNull('break_end_at')
            ->exists();

        if ($isBreaking) {
            return back();
        }

        $attendance->update([
            'clock_out_at' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    public function breakStart()
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance) {
            return back();
        }

        if ($attendance->clock_out_at) {
            return back();
        }

        $isBreaking = $attendance->breakTimes()
            ->whereNull('break_end_at')
            ->exists();

        if ($isBreaking) {
            return back();
        }

        $breakNo = $attendance->breakTimes()->count() + 1;

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_no' => $breakNo,
            'break_start_at' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    public function breakEnd()
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance) {
            return back();
        }
        $break = $attendance->breakTimes()
            ->whereNull('break_end_at')
            ->latest()
            ->first();

        if (!$break) {
            return back();
        }

        $break->update([
            'break_end_at' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    private function getTodayAttendance()
    {
        $user = Auth::user();
        $today = now()->toDateString();
        return Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->whereDate('work_date', now()->toDateString())
            ->first();
    }



    private function determineStatus($attendance)
    {
        if (!$attendance) {
            return [
                'code' => 'off',
                'label' => '勤務外',
                'class' => 'badge--off',
            ];
        }

        if ($attendance->clock_out_at) {
            return [
                'code' => 'done',
                'label' => '退勤済',
                'class' => 'badge--done',
            ];
        }

        $isBreaking = $attendance->breakTimes
            ->whereNull('break_end_at')
            ->isNotEmpty();

        if ($isBreaking) {
            return [
                'code' => 'break',
                'label' => '休憩中',
                'class' => 'badge--break',
            ];
        }

        if ($attendance->clock_in_at) {
            return [
                'code' => 'working',
                'label' => '出勤中',
                'class' => 'badge--working',
            ];
        }

        return [
            'code' => 'off',
            'label' => '勤務外',
            'class' => 'badge--off',
        ];
    }
}
