@extends('layouts.list')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/list.css')}}">
@endsection

@section('title', '勤怠一覧')

@section('table')

<div class="date-pagination">
    <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="date-pagination__link">
        ← 前月
    </a>

    <div class="date-pagination__current">
        <i class="fa-solid fa-calendar calendar-icon"></i>
        {{ $targetMonth->format('Y/m') }}
    </div>

    <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="date-pagination__link">
        翌月 →
    </a>
</div>

<table class="list_table">
    <thead>
        <tr>
            <th>日付</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($days as $day)
        <tr class="{{ $day['row_class'] }}" data-testid="user_attendance_row_{{ $day['work_date'] }}">
            <td data-testid="user_date">{{ $day['date_label'] }}</td>
            <td data-testid="user_clock_in_at">{{ $day['clock_in_at'] }}</td>
            <td data-testid="user_clock_out_at">{{ $day['clock_out_at'] }}</td>
            <td data-testid="user_break_time">{{ $day['break_time'] }}</td>
            <td data-testid="user_work_time">{{ $day['work_time'] }}</td>
            <td>
                @if ($day['detail_url'])
                <a class="adetail-btn" data-testid="user_detail_url" href="{{ $day['detail_url'] }}">詳細</a>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection