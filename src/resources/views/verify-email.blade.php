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
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <div class="form-group">
                    {{-- 開発環境のメール確認画面（MailHog）を開く --}}
                    <button type="button" class="verify-email-button button" onclick="window.open('http://localhost:8025', '_blank')">認証はこちらから</button>
                </div>
                <p class="auth-success-message">
                    <a href="#" class="link" onclick="this.closest('form').submit(); return false;">認証メールを再送する</a>
                </p>
            </form>
        </div>
    </div>
@endsection
