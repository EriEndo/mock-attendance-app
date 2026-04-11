@extends('layouts.list')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/list.css')}}">

@endsection

@section('title', 'スタッフ一覧')

@section('table')
    <table class="list_table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($staffs as $staff)
                <tr>
                    <td>{{ $staff->name }}</td>
                    <td>{{ $staff->email }}</td>
                    <td><a class="admin__detail-btn" href="{{ route('admin.staff.attendance', $staff->id) }}">詳細</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
