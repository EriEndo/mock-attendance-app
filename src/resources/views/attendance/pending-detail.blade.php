@extends('layouts.list')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/list.css') }}">
@endsection

@section('title', '勤怠詳細')

@section('table')

<form action="{{ route('stamp_correction_request.store', $attendance->id) }}" method="POST">
    @csrf

    <table class="attendance-detail_table">
        <tr class="detail__row">
            <th class="detail__label">名前</th>
            <td class="detail__data">
                {{ $pendingRequest->attendance->user->name }}
            </td>
        </tr>

        <tr class="detail__row">
            <th class="detail__label">日付</th>
            <td class="detail__data">
                <div class="detail__date-group">
                    <span>{{ $pendingRequest->attendance->work_date->format('Y年') }}</span>
                    <span>{{ $pendingRequest->attendance->work_date->format('n月j日') }}</span>
                </div>
            </td>
        </tr>

        <tr class="detail__row">
            <th class="detail__label">出勤・退勤</th>
            <td class="detail__data">
                <div class="detail__time-group">
                    <span>{{ optional($pendingRequest->requested_clock_in_at)->format('H:i') ?? '-' }}</span>
                    <span>～</span>
                    <span>{{ optional($pendingRequest->requested_clock_out_at)->format('H:i') ?? '-' }}</span>
                </div>
            </td>
        </tr>

        @forelse ($pendingRequest->requestBreaks as $index => $break)
            <tr class="detail__row">
                <th class="detail__label">
                    {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                </th>
                <td class="detail__data">
                    <div class="detail__time-group">
                        <span>{{ ($break->requested_break_start_at)->format('H:i') ?? '-' }}</span>
                        <span>～</span>
                        <span>{{ ($break->requested_break_end_at)->format('H:i') ?? '-' }}</span>
                    </div>
                </td>
            </tr>
        @empty
            <tr class="detail__row">
                <th class="detail__label">休憩</th>
                <td class="detail__data">-</td>
            </tr>
        @endforelse

        <tr class="detail__row">
            <th class="detail__label">備考</th>
            <td class="detail__data">
                {{ $pendingRequest->note }}
            </td>
        </tr>
    </table>

        <p class="pending-message">
            ※承認待ちのため修正はできません。
        </p>

</form>

@endsection