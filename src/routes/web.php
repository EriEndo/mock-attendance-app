<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AttendanceViewController;
use App\Http\Controllers\AttendanceActionController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\AdminAuthViewController;
use App\Http\Controllers\StaffController;



Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-guide');
    })->name('verification.notice');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('attendance.index');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/attendance', [AttendanceActionController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock_in_at', [AttendanceActionController::class, 'clockIn'])->name('attendance.clock_in_at');
    Route::post('/attendance/clock_out_at', [AttendanceActionController::class, 'clockOut'])->name('attendance.clock_out_at');
    Route::post('/attendance/break_start', [AttendanceActionController::class, 'breakStart'])->name('attendance.break_start');
    Route::post('/attendance/break_end', [AttendanceActionController::class, 'breakEnd'])->name('attendance.break_end');

    Route::get('/attendance/list', [AttendanceViewController::class, 'userList'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceViewController::class, 'userDetail'])->name('attendance.detail');

    Route::post('/attendance/{attendance}/correction-request', [CorrectionRequestController::class, 'store'])->name('stamp_correction_request.store');
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'userList'])->name('stamp_correction_request.list');
    Route::get('/stamp_correction_request/{id}', [CorrectionRequestController::class, 'userDetail'])->name('stamp_correction_request.detail');
});



Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthViewController::class, 'create'])->name('login');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/attendance/list', [AttendanceViewController::class, 'adminList'])->name('attendance.list');
        Route::get('/attendance/{id}', [AttendanceViewController::class, 'adminDetail'])->name('attendance.detail');

        Route::get('/staff/list', [StaffController::class, 'staffList'])->name('staff.list');
        Route::get('/staff/{id}/attendance', [StaffController::class, 'staffAttendance'])->name('staff.attendance');
        Route::get('/staff/{id}/attendance/csv', [StaffController::class, 'exportCsv'])->name('staff.attendance.csv');

        Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'adminList'])->name('stamp_correction_request.list');
        Route::get('/stamp_correction_request/{id}', [CorrectionRequestController::class, 'adminDetail'])->name('stamp_correction_request.detail');
        Route::patch('/stamp_correction_request/approve/{id}', [CorrectionRequestController::class, 'adminApprove'])->name('stamp_correction_request.approve');
        Route::patch('/attendance/{id}', [CorrectionRequestController::class, 'adminCorrect'])->name('attendance.update');
    });
});
