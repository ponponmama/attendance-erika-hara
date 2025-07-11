{{-- メール認証誘導画面（一般ユーザー） /email/verify --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', 'メール認証')

@section('content')
    <div class="auth-container">
        <p class="auth-title">メールアドレスの確認</p>
        <div class="auth-content">
            <p class="auth-message">
                ご登録のメールアドレスに認証メールを送信しました。<br>
                メール内のリンクをクリックして認証を完了してください。
            </p>

            @if (session('status') == 'verification-link-sent')
                <p class="auth-success-message">
                    認証メールを再送信しました。
                </p>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <div class="form-group">
                    <button type="submit" class="submit-button button">認証メール再送</button>
                </div>
            </form>

            <p class="login-register">
                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    ログアウト
                </a>
            </p>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </div>
@endsection
