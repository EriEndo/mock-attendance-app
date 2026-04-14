<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;


class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }

        if ($user->role === 'admin') {
            return redirect()->route('admin.attendance.list');
        }

        return redirect()->route('attendance.index');
    }
}
