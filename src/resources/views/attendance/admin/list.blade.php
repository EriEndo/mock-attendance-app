@extends('layouts.list')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/list.css')}}">
@endsection

@section('title', $targetDate->format('Y年n月j日') . 'の勤怠一覧')

@section('table')

<div class="date-pagination">
    <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="date-pagination__link">
        ← 前日
    </a>

  <div class="date-pagination__current">
    <i class="fa-solid fa-calendar calendar-icon"></i>
    {{ $targetDate->format('Y/m/d') }}
</div>

    <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="date-pagination__link">
        翌日 →
    </a>
</div>

<table class="list_table">
    <thead>
        <tr>
            <th>名前</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>
    </thead>
    <tbody>
        @foreach  ($attendances as $attendance)
        <tr>
            <td>{{ $attendance->user->name }}</td>
            <td>{{ optional($attendance->clock_in_at)->format('H:i') }}</td>
            <td>{{ optional($attendance->clock_out_at)->format('H:i') }}</td>
            <td>{{ $attendance->break_time }}</td>
            <td>{{ $attendance->work_time }}</td>
            <td>
                <a class="admin__detail-btn" href="{{ route('admin.attendance.detail', $attendance->id) }}">詳細</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection