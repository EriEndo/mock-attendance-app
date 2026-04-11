@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/auth.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/form.css') }}">
@endsection

@section('content')
<div class="form__container">

    <div class="form__wrapper">
        <div class="form__title">ログイン</div>

        <div class="form__card">
            <form class="form__form" action="/login" method="post">
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
                <input type="hidden" name="login_type" value="user">
                <input class="form__btn" type="submit" value="ログインする">
            </form>

            <a href="/register" class="auth__link">会員登録はこちら</a>
        </div>
    </div>
</div>
@endsection