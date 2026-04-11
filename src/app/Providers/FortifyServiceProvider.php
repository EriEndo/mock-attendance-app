<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Responses\LoginResponse;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\RegisterResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Models\User;
use App\Models\Admin;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Http\Responses\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

public function boot(): void
{
    Fortify::createUsersUsing(CreateNewUser::class);

    Fortify::registerView(function () {
        return view('auth.register');
    });

    Fortify::loginView(function (Request $request) {
        if ($request->is('admin/login')) {
            return view('admin.auth.login');
        }

        return view('auth.login');
    });

    RateLimiter::for('login', function (Request $request) {
        $email = (string) $request->input('email');

        return Limit::perMinute(10)->by(Str::lower($email).'|'.$request->ip());
    });

    $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);

    Fortify::authenticateUsing(function (Request $request) {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login_error' => ['ログイン情報が登録されていません'],
            ]);
        }

        $loginType = $request->input('login_type');

        if ($loginType === 'admin' && $user->role !== 'admin') {
            throw ValidationException::withMessages([
                'login_error' => ['管理者アカウントではありません'],
            ]);
        }

        if ($loginType === 'user' && $user->role !== 'user') {
            throw ValidationException::withMessages([
                'login_error' => ['一般ユーザーアカウントではありません'],
            ]);
        }

        return $user;
    });
    }
}
