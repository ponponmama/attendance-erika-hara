{{-- ログイン画面（一般ユーザー・管理者共通） /login, /admin/login --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', 'ログイン')

@section('content')
    <div class="auth-container">
        <p class="auth-title">ログイン</p>
        <div class="auth-content">
            <p class="auth-error-message">
                @error('failed')
                    {{ $message }}
                @enderror
            </p>
            <form action="{{ route('login') }}" method="POST">
                @csrf
                @if (request()->is('admin*'))
                    <input type="hidden" name="admin_login" value="1">
                @endif
                <div class="form-group">
                    <label class="form-label" for="email">メールアドレス</label>
                    <input class="form-input" type="email" name="email" id="email" value="{{ old('email') }}"
                        autocomplete="email">
                </div>
                <p class="form__error">
                    @error('email')
                        {{ $message }}
                    @enderror
                </p>
                <div class="form-group">
                    <label class="form-label" for="password">パスワード</label>
                    <input class="form-input" type="password" name="password" id="password"
                        autocomplete="current-password">
                </div>
                <p class="form__error">
                    @error('password')
                        {{ $message }}
                    @enderror
                </p>
                <div class="form-group">
                    <button type="submit" class="submit-button button">ログインする</button>
                </div>
            </form>
            @unless (request()->is('admin*'))
                <p class="login-register-link"><a href="{{ route('register') }}">会員登録はこちら</a></p>
            @endunless
        </div>
    </div>
@endsection
