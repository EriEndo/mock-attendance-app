@extends('layouts.list')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/list.css') }}">
@endsection

@section('title', '勤怠詳細')

@section('table')



<table class="attendance-detail_table">
    <tr class="detail__row" data-testid="user_detail_name_row">
        <th class="detail__label">名前</th>
        <td class="detail__data" data-testid="user_detail_name">
            {{ $correctionRequest->attendance->user->name }}
        </td>
    </tr>

    <tr class="detail__row" data-testid="user_detail_date_row">
        <th class="detail__label">日付</th>
        <td class="detail__data" data-testid="user_detail_date">
            <div class="detail__date-group">
                <span>{{ $correctionRequest->attendance->work_date->format('Y年') }}</span>
                <span>{{ $correctionRequest->attendance->work_date->format('n月j日') }}</span>
            </div>
        </td>
    </tr>

    <tr class="detail__row" data-testid="user_detail_clock_inout_row">
        <th class="detail__label">出勤・退勤</th>
        <td class="detail__data">
            <div class="detail__time-group">
                <span data-testid="user_detail_clock_in_at">{{ optional($correctionRequest->requested_clock_in_at)->format('H:i') ?? '-' }}</span>
                <span>～</span>
                <span data-testid="user_detail_clock_out_at">{{ optional($correctionRequest->requested_clock_out_at)->format('H:i') ?? '-' }}</span>
            </div>
        </td>
    </tr>

    @forelse ($correctionRequest->requestBreaks as $index => $break)
    <tr class="detail__row" data-testid="user_detail_break_row_{{ $index }}">
        <th class="detail__label">
            {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
        </th>
        <td class="detail__data">
            <div class="detail__time-group">
                <span data-testid="user_detail_break_start_at">{{ optional($break->requested_break_start_at)->format('H:i') ?? '-' }}</span>
                <span>～</span>
                <span data-testid="user_detail_break_end_at">{{ optional($break->requested_break_end_at)->format('H:i') ?? '-' }}</span>
            </div>
        </td>
    </tr>
    @empty
    <tr class="detail__row">
        <th class="detail__label">休憩</th>
        <td class="detail__data">-</td>
    </tr>
    @endforelse

    <tr class="detail__row" data-testid="user_detail_note_row">
        <th class="detail__label">備考</th>
        <td class="detail__data" data-testid="user_detail_note">
            {{ $correctionRequest->note }}
        </td>
    </tr>
</table>

@if($correctionRequest->status === 'pending')
<p class="pending-message">※承認待ちのため修正はできません。</p>
@elseif($correctionRequest->status === 'approved')
<button type="button" class="submit-btn is-disabled" disabled>承認済み</button>
@endif


@endsection