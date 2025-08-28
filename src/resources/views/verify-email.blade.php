{{-- メール認証誘導画面（一般ユーザー） /email/verify --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify_email.css') }}">
@endsection

@section('title', 'メール認証')

@section('content')
    <div class="auth-container">
        <div class="auth-content">
            <p class="auth-message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>
            <div class="form-group">
                <button type="button" class="verify-email-button button"
                    onclick="window.open('https://mailtrap.io', '_blank')">
                    認証はこちらから
                </button>
            </div>
            <form method="POST" action="{{ route('verification.send') }}" class="auth-resend-button-container">
                @csrf
                <button type="submit" class="auth-resend-button button">認証メールを再送する</button>
            </form>
        </div>
    </div>
@endsection
