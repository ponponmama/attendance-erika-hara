{{-- メール認証誘導画面（一般ユーザー） /email/verify --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
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
                    <button type="submit" class="verify-email-button button">認証はこちらから</button>
                </div>
                <p class="auth-success-message">
                    認証メールを再送する
                </p>
            </form>
        </div>
    </div>
@endsection
