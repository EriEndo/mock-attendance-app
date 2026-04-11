<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CorrectionRequestStoreRequest;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\BreakTime;
use App\Models\RequestBreak;


class CorrectionRequestController extends Controller
{
    public function store(CorrectionRequestStoreRequest $request, Attendance $attendance)
    {
        if ($attendance->user_id !== auth()->id()) {
            abort(403);
        }

        DB::transaction(function () use ($request, $attendance) {
            $correctionRequest = CorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'requested_by' => Auth::id(),
                'request_type' => 'user_request',
                'status' => 'pending',
                'requested_clock_in_at' => $request->clock_in_at,
                'requested_clock_out_at' => $request->clock_out_at,
                'note' => $request->note,
                
            ]);

        foreach ($request->input('breaks', []) as $index => $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;
            if (!$start && !$end) {
                continue;
            }
            RequestBreak::create([
                'correction_request_id' => $correctionRequest->id,
                'break_no' => $index + 1,
                'requested_break_start_at' => $break['start'] ?: null,
                'requested_break_end_at' => $break['end'] ?: null,
            ]);
        }
        });

        return redirect()->route('stamp_correction_request.list');
    }

public function userList(Request $request)
{

 $status = $request->input('status', 'pending');

    $correctionRequests = CorrectionRequest::with(['user', 'attendance'])
        ->where('status', $status)
        ->whereHas('attendance', function ($query) {
            $query->where('user_id', Auth::id());
        })  
        ->latest()
        ->get();

    return view('stamp_correction_request.list', compact('correctionRequests', 'status'));
}


public function adminList(Request $request)
{

 $status = $request->input('status', 'pending');

    $correctionRequests = CorrectionRequest::with(['attendance.user'])
    ->where('status', $status)   
    ->latest()
    ->get();

    return view('stamp_correction_request.admin.list', compact('correctionRequests', 'status'));
}

public function adminDetail($id)
{
  
    $correctionRequest = CorrectionRequest::with(['attendance.user','requestBreaks',])
        ->findOrFail($id);

    return view('stamp_correction_request.admin.detail', compact('correctionRequest'));
}

public function adminApprove($id)
{
    $correctionRequest = CorrectionRequest::with(['requestBreaks', 'attendance.breakTimes'])
        ->findOrFail($id);
    
        if ($correctionRequest->status === 'approved') {
            return redirect()->back()->with('error', 'すでに承認済みです');
        }

    DB::transaction(function () use ($correctionRequest) {
        $attendance = $correctionRequest->attendance;

        $attendance->clock_in_at = $correctionRequest->requested_clock_in_at;
        $attendance->clock_out_at = $correctionRequest->requested_clock_out_at;
        $attendance->save();

        $attendance->breakTimes()->delete();

        foreach ($correctionRequest->requestBreaks as $break) {
            $attendance->breakTimes()->create([
                'break_no' => $break->break_no,
                'break_start_at' => $break->requested_break_start_at,
                'break_end_at' => $break->requested_break_end_at,
            ]);
        }

        $correctionRequest->status = 'approved';
        $correctionRequest->save();
    });

    return redirect()->back();
}

public function adminCorrect(CorrectionRequestStoreRequest $request, Attendance $attendance)
{
    $attendance = Attendance::with(['breakTimes', 'user'])
        ->findOrFail($id);

    DB::transaction(function () use ($request, $attendance) {
        $correctionRequest = CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_by' => Auth::id(),
            'request_type' => 'admin_direct',
            'status' => 'approved',
            'requested_clock_in_at' => $request->clock_in_at ?: null,
            'requested_clock_out_at' => $request->clock_out_at ?: null,
            'note' => $request->note,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        foreach ($request->input('breaks', []) as $index => $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;
            $hasInput = !empty($start) || !empty($end);

            if ($hasInput) {
                RequestBreak::create([
                    'correction_request_id' => $correctionRequest->id,
                    'break_no' => $index + 1,
                    'requested_break_start_at' => $start ?: null,
                    'requested_break_end_at' => $end ?: null,
                ]);
            }
        }

        $attendance->clock_in_at = $request->clock_in_at ?: null;
        $attendance->clock_out_at = $request->clock_out_at ?: null;
        $attendance->save();

        $existingBreaks = $attendance->breakTimes->values();

        foreach ($request->input('breaks', []) as $index => $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;
            $hasInput = !empty($start) || !empty($end);

            if (isset($existingBreaks[$index])) {
                if ($hasInput) {
                    $existingBreaks[$index]->break_start_at = $start ?: null;
                    $existingBreaks[$index]->break_end_at = $end ?: null;
                    $existingBreaks[$index]->save();
                } else {
                    $existingBreaks[$index]->delete();
                }
            } elseif ($hasInput) {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_no' => $index + 1,
                    'break_start_at' => $start ?: null,
                    'break_end_at' => $end ?: null,
                ]);
            }
        }
    });

    return redirect()
    ->route('admin.attendance.detail', $attendance->id)
    ->with('success', '修正が完了しました');
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
