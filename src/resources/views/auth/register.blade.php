@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/components/form.css') }}">
@endsection

@section('content')
<div class="form__container">
    <div class="form__wrapper">
        <div class="form__title">会員登録</div>

        <div class="form__card">
            <form class="form__form" action="{{ route('register') }}" method="post">
                @csrf

                <div class="form__group">
                    <label class="form__label" for="name">名前</label>
                    <input class="form__input" type="text" name="name" id="name" value="{{ old('name') }}">
                    @error('name')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

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

                <div class="form__group">
                    <label class="form__label" for="password_confirmation">パスワード確認</label>
                    <input class="form__input" type="password" name="password_confirmation" id="password_confirmation">
                    @error('password_confirmation')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                </div>

                <input class="form__btn" type="submit" value="登録する">
            </form>

            <a href="/login" class="auth__link">ログインはこちら</a>
        </div>
    </div>
</div>
@endsection