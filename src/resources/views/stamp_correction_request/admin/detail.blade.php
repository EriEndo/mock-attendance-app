@extends('layouts.list')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/list.css') }}">
@endsection

@section('title', '勤怠詳細')

@section('table')

<form action="{{ route('admin.stamp_correction_request.approve', $correctionRequest->id) }}" method="POST">
    @csrf
    @method('PATCH')

    <table class="attendance-detail_table">
        <tr class="detail__row">
            <th class="detail__label">名前</th>
            <td class="detail__data">
                {{ $correctionRequest->attendance->user->name }}
            </td>
        </tr>

        <tr class="detail__row">
            <th class="detail__label">日付</th>
            <td class="detail__data">
                <div class="detail__date-group">
                    <span>{{ $correctionRequest->attendance->work_date->format('Y年') }}</span>
                    <span>{{ $correctionRequest->attendance->work_date->format('n月j日') }}</span>
                </div>
            </td>
        </tr>

        <tr class="detail__row">
            <th class="detail__label">出勤・退勤</th>
            <td class="detail__data">
                <div class="detail__time-group">
                    <span>{{ optional($correctionRequest->requested_clock_in_at)->format('H:i') ?? '-' }}</span>
                    <span>～</span>
                    <span>{{ optional($correctionRequest->requested_clock_out_at)->format('H:i') ?? '-' }}</span>
                </div>
            </td>
        </tr>

        @forelse ($correctionRequest->requestBreaks as $index => $break)
        <tr class="detail__row">
            <th class="detail__label">
                {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
            </th>
            <td class="detail__data">
                <div class="detail__time-group">
                    <span>{{ optional($break->requested_break_start_at)->format('H:i') ?? '-' }}</span>
                    <span>～</span>
                    <span>{{ optional($break->requested_break_end_at)->format('H:i') ?? '-' }}</span>
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
                {{ $correctionRequest->note }}
            </td>
        </tr>
    </table>

    @if ($correctionRequest->status === 'approved')
    <button type="button" class="submit-btn is-disabled" disabled>承認済み</button>
    @else
    <button type="submit" class="submit-btn">承認</button>
    @endif
</form>

@endsection