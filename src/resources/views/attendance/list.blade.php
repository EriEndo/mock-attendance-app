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
        <tr class="{{ $day['row_class'] }}">
            <td>{{ $day['date_label'] }}</td>
            <td>{{ $day['clock_in_at'] }}</td>
            <td>{{ $day['clock_out_at'] }}</td>
            <td>{{ $day['break_time'] }}</td>
            <td>{{ $day['work_time'] }}</td>
            <td>
                @if ($day['detail_url'])
                <a class="adetail-btn" href="{{ $day['detail_url'] }}">詳細</a>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection