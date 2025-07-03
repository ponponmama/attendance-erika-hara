@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', '管理者ログイン')

@section('content')
    <div class="auth-container">
        <p class="auth-title">管理者ログイン</p>
        <div class="auth-content">
            <p class="auth-error-message">
                @error('failed')
                    {{ $message }}
                @enderror
            </p>
            <form action="{{ route('login') }}" method="POST">
                <input type="hidden" name="admin_login" value="1">
                @csrf
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
                    <button type="submit" class="submit-button button">管理者ログインする</button>
                </div>
            </form>
        </div>
    </div>
@endsection
