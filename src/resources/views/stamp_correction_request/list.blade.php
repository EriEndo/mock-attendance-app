@extends('layouts.list')

@section('title', '申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/list.css')}}">
@endsection


@section('table')

<div class="list-tabs">
    <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}"
        class="list-tabs__item {{ request('status', 'pending') === 'pending' ? 'active' : '' }}">
        承認待ち
    </a>

    <a href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}"
        class="list-tabs__item {{ request('status') === 'approved' ? 'active' : '' }}">
        承認済み
    </a>
</div>

<table class="list_table">
    <thead>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($correctionRequests as $correctionRequest)
        <tr data-testid="user_request_row_{{ $correctionRequest->id }}">
            <td data-testid="user_request_status">{{ $correctionRequest->status_label }}</td>
            <td data-testid="user_request_name">{{ $correctionRequest->attendance->user->name }}</td>
            <td data-testid="user_request_work_date">{{ optional($correctionRequest->attendance->work_date)->format('Y/m/d') }}</td>
            <td class="note" data-testid="user_request_note">{{ $correctionRequest->note }}</td>
            <td>{{ optional($correctionRequest->created_at)->format('Y/m/d') }}</td>
            <td><a href="{{ route('stamp_correction_request.detail', $correctionRequest->id) }}">詳細</a></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection