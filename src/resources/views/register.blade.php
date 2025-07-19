{{-- 会員登録画面（一般ユーザー） /register --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', '会員登録')

@section('content')
    <div class="auth-container">
        <p class="auth-title">会員登録</p>
        <div class="auth-content">
            <form action="{{ route('register') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="register-label" for="name">名前</label>
                    <input class="register-input" type="text" name="name" id="name" value="{{ old('name') }}"
                        autocomplete="name">
                </div>
                <p class="form__error">
                    @error('name')
                        {{ $message }}
                    @enderror
                </p>
                <div class="form-group">
                    <label class="register-label" for="email">メールアドレス</label>
                    <input class="register-input" type="email" name="email" id="email" value="{{ old('email') }}"
                        autocomplete="email">
                </div>
                <p class="form__error">
                    @error('email')
                        {{ $message }}
                    @enderror
                </p>
                <div class="form-group">
                    <label class="register-label" for="password">パスワード</label>
                    <input class="register-input" type="password" name="password" id="password" autocomplete="new-password">
                </div>
                <p class="form__error">
                    @error('password')
                        {{ $message }}
                    @enderror
                </p>
                <div class="form-group">
                    <label class="register-label" for="password_confirmation">パスワード確認</label>
                    <input class="register-input" type="password" name="password_confirmation" id="password_confirmation"
                        autocomplete="new-password">
                </div>
                <p class="form__error">
                    @error('password_confirmation')
                        {{ $message }}
                    @enderror
                </p>
                <button type="submit" class="submit-button button">登録する</button>
            </form>
            <p class="login-register-link"><a href="{{ route('login') }}">ログインはこちら</a></p>
        </div>
    </div>
@endsection
