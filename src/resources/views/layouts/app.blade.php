<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勤怠アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/layouts/common.css')}}">
    @yield('css')
</head>

<body>
    @php
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $navItems = config("navigation.$role", []);
        $showHeaderNav = !request()->routeIs('login')
            && !request()->routeIs('register')
            && !request()->routeIs('admin.login');
    @endphp

    <div class="app">
        <header class="header">
            <div class="header-left">
                <h1 class="header__heading">
                    <a href="{{ $role === 'admin' ? route('admin.attendance.list') : route('attendance.index') }}">
                        <img src="{{ asset('images/header_logo.png') }}" alt="coachtech ヘッダーロゴ" class="header__logo">
                    </a>
                </h1>
            </div>

            <div class="header-right">
                @if ($showHeaderNav)
                    <nav class="header-nav">
                        <ul class="header-nav-list">
                            @foreach ($navItems as $item)
                                @php
                                    $label = $item['label'];

                                    if ($role === 'user') {
                                        if ($item['route'] === 'attendance.list' && request()->routeIs('attendance.index')) {
                                            $label = '今月の出勤一覧';
                                        }

                                        if ($item['route'] === 'stamp_correction_request.list' && request()->routeIs('attendance.index')) {
                                            $label = '申請一覧';
                                        }
                                    }
                                @endphp

                                <li class="header-nav-link">
                                    <a href="{{ route($item['route']) }}">
                                        {{ $label }}
                                    </a>
                                </li>
                            @endforeach

                            <li class="header-nav-link">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="logout_redirect" value="{{ $role }}">
                                    <button type="submit" class="logout-btn">ログアウト</button>
                                </form>
                            </li>
                        </ul>
                    </nav>
                @endif
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </div>
</body>

</html>