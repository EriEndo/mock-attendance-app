@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css')}}">
@endsection

@section('content')
<div class="index-page">
    <div class="index-page__inner">
        <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>

        <div class="current-datetime">
            <div id="current-date">
                {{ \Carbon\Carbon::now()->locale('ja')->isoFormat('YYYY年M月D日(dd)') }}
            </div>
            <div id="current-time">
                {{ \Carbon\Carbon::now()->format('H:i') }}
            </div>
        </div>

        <div class="attendance-action">
            @if ($status['code'] === 'off')
            <form action="{{ route('attendance.clock_in_at') }}" method="POST">
                @csrf
                <button type="submit" class="attendance-btn">出勤</button>
            </form>

            @elseif ($status['code'] === 'working')
            <div class="attendance-action__btns">
                <form action="{{ route('attendance.clock_out_at') }}" method="POST">
                    @csrf
                    <button type="submit" class="attendance-btn">退勤</button>
                </form>

                <form action="{{ route('attendance.break_start') }}" method="POST">
                    @csrf
                    <button type="submit" class="attendance-btn attendance-btn--sub">休憩入</button>
                </form>
            </div>

            @elseif ($status['code'] === 'break')
            <form action="{{ route('attendance.break_end') }}" method="POST">
                @csrf
                <button type="submit" class="attendance-btn--sub">休憩戻</button>
            </form>

            @elseif ($status['code'] === 'done')
            <p class="attendance-message">お疲れ様でした。</p>
            @endif
        </div>

    </div>
</div>

<script>
    const serverNow = "{{ now()->format('Y-m-d H:i:s') }}";
</script>

<script>
    function createServerBasedNow() {
        const baseTime = new Date(serverNow);
        const startTime = new Date();
        return function() {
            const now = new Date();
            const diff = now - startTime;
            return new Date(baseTime.getTime() + diff);
        };
    }

    function updateDateTime(getNow) {
        const now = getNow();
        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        const day = now.getDate();
        const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        const weekday = weekdays[now.getDay()];
        const date = `${year}年${month}月${day}日(${weekday})`;

        const time = now.toLocaleTimeString('ja-JP', {
            hour: '2-digit',
            minute: '2-digit'
        });

        document.getElementById('current-date').textContent = date;
        document.getElementById('current-time').textContent = time;
    }

    function startClock() {
        const getNow = createServerBasedNow();
        updateDateTime(getNow);

        const now = getNow();
        const delay =
            (60 - now.getSeconds()) * 1000 - now.getMilliseconds();

        setTimeout(() => {
            updateDateTime(getNow);
            setInterval(() => updateDateTime(getNow), 60000);
        }, delay);
    }

    startClock();
</script>

@endsection