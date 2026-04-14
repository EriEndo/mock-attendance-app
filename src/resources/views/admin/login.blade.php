@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/form.css') }}">
@endsection

@section('content')
<div class="form__container">

    <div class="form__wrapper">
        <div class="form__title">管理者ログイン</div>

        <div class="form__card">
            <form class="form__form" action="{{ route('login') }}" method="post">
                @csrf

                @if ($errors->has('login_error'))
                <div class="form__error">
                    {{ $errors->first('login_error') }}
                </div>
                @endif

                <div class="form__group">
                    <label class="form__label" for="email">メールアドレス</label>
                    <input class="form__input" type="text" name="email" id="email" value="{{ old('email') }}">

                    @error('email')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <div class="form__group">
                    <label class="form__label" for="password">パスワード</label>
                    <input class="form__input" type="password" name="password" id="password">

                    @error('password')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <input type="hidden" name="login_type" value="admin">
                <input class="form__btn" type="submit" value="管理者ログインする">
            </form>
        </div>
    </div>
</div>
@endsection