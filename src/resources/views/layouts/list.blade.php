@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/list.css')}}">
@endsection

@section('content')
<div class="list-page">
    <div class="list-page__inner">

        <h1 class="list-title">
            @yield('title')
        </h1>

        <div class="list-content">
            @yield('table')
        </div>

    </div>
</div>
@endsection