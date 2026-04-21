@extends('layouts.list')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/list.css') }}">
@endsection

@section('title', '勤怠詳細')

@section('table')

<form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST">
    @csrf
    @method('PATCH')

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <table class="attendance-detail_table">
        @error('no_change')
        <tr>
            <td colspan="2">
                <p class="error-message">{{ $message }}</p>
            </td>
        </tr>
        @enderror
        <tr class="detail__row">
            <th class="detail__label">名前</th>
            <td class="detail__data" data-testid="detail_name">{{ $attendance->user->name }}</td>
        </tr>

        <tr class="detail__row">
            <th class="detail__label">日付</th>
            <td class="detail__data" data-testid="detail_date">
                <div class="detail__date-group">
                    <span>{{ $attendance->work_date->format('Y年') }}</span>
                    <span>{{ $attendance->work_date->format('n月j日') }}</span>
                </div>
            </td>
        </tr>

        <tr class="detail__row">
            <th class="detail__label">出勤・退勤</th>
            <td class="detail__data">
                <div class="detail__time-group">
                    <input type="time" name="clock_in_at" data-testid="detail_clock_in_at" value="{{ old('clock_in_at', $attendance->clock_in_at?->format('H:i')) }}">
                    <span>～</span>
                    <input type="time" name="clock_out_at" data-testid="detail_clock_out_at" value="{{ old('clock_out_at', $attendance->clock_out_at?->format('H:i')) }}">
                </div>

                @error('clock_in_at')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </td>
        </tr>

        @foreach ($attendance->breakTimes as $index => $break)
        <tr class="detail__row">
            <th class="detail__label">
                {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
            </th>
            <td class="detail__data">
                <div class="time-group">
                    <input type="time" name="breaks[{{ $index }}][start]" data-testid="detail_break_start_at" value="{{ old("breaks.$index.start", $break->break_start_at?->format('H:i')) }}">
                    <span>～</span>
                    <input type="time" name="breaks[{{ $index }}][end]" data-testid="detail_break_end_at" value="{{ old("breaks.$index.end", $break->break_end_at?->format('H:i')) }}">
                </div>

                @error("breaks.$index.start")
                <p class="error-message">{{ $message }}</p>
                @enderror

                @error("breaks.$index.end")
                <p class="error-message">{{ $message }}</p>
                @enderror
            </td>
        </tr>
        @endforeach

        <tr class="detail__row">
            <th class="detail__label">
                {{ $attendance->breakTimes->count() === 0 ? '休憩' : '休憩' . ($attendance->breakTimes->count() + 1) }}
            </th>
            <td class="detail__data">
                <div class="time-group">
                    <input type="time" name="breaks[{{ $attendance->breakTimes->count() }}][start]" value="{{ old('breaks.' . $attendance->breakTimes->count() . '.start') }}">
                    <span>～</span>
                    <input type="time" name="breaks[{{ $attendance->breakTimes->count() }}][end]" value="{{ old('breaks.' . $attendance->breakTimes->count() . '.end') }}">
                </div>

                @error('breaks.' . $attendance->breakTimes->count() . '.start')
                <p class="error-message">{{ $message }}</p>
                @enderror

                @error('breaks.' . $attendance->breakTimes->count() . '.end')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </td>
        </tr>

        <tr class="detail__row">
            <th class="detail__label">備考</th>
            <td class="detail__data">
                <textarea name="note" id="note">{{ old('note', $attendance->note) }}</textarea>

                @error('note')
                <p class="error-message">{{ $message }}</p>
                @enderror
            </td>
        </tr>
    </table>

    <button type="submit" class="submit-btn">修正</button>
</form>

@endsection