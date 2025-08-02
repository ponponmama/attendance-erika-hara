{{-- ログイン画面（一般ユーザー・管理者共通） /login, /admin/login --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', 'ログイン')

@section('content')
    <div class="auth-container">
        @if (request()->is('admin*'))
            <p class="auth-title">管理者ログイン</p>
        @else
            <p class="auth-title">ログイン</p>
        @endif
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
                    @if (request()->is('admin*'))
                        <button type="submit" class="submit-button button">管理者ログインする</button>
                    @else
                        <button type="submit" class="submit-button button">ログインする</button>
                    @endif
                </div>
            </form>
            @unless (request()->is('admin*'))
                <a class="login-register-link link" href="{{ route('register') }}">会員登録はこちら</a>
            @endunless
        </div>
    </div>
@endsection
