{{-- 勤怠登録画面（一般ユーザー） /attendance --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/attendance.css') }}">
@endsection

@section('title', '打刻')

@section('content')
    <div class="attendance-container">
        <div class="attendance-status">
            @if ($status === 'not_clocked_in')
                <span class="status-text">勤務外</span>
            @elseif($status === 'clocked_in')
                <span class="status-text">勤務中</span>
            @elseif($status === 'on_break')
                <span class="status-text">休憩中</span>
            @else
                <span class="status-text">終了済</span>
            @endif
        </div>
        <p class="attendance-date">{{ $now->format('Y年n月j日') }}({{ ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek] }})</p>
        <p class="attendance-time">{{ $now->format('H:i') }}</p>
    </div>
    <div class="attendance-actions">
        @if ($status === 'not_clocked_in')
            <form class="attendance-actions__form" action="{{ route('attendance_clock_in') }}" method="POST">
                @csrf
                <button type="submit" class="action-button button">出勤</button>
            </form>
        @elseif ($status === 'clocked_in')
            <form class="attendance-actions__form" method="POST" action="{{ route('attendance_clock_out') }}">
                @csrf
                @if (session('message'))
                    <p class="alert-success>
                        {{ session('message') }}
                    </p>
                @else
                    <button type="submit" class="action-button button">退勤</button>
                @endif
            </form>
            <form class="attendance-actions__form" action="{{ route('attendance_break_start') }}" method="POST">
                @csrf
                <button type="submit" class="action-break-button button">休憩入</button>
            </form>
        @elseif ($status === 'on_break')
            <form class="attendance-actions__form" action="{{ route('attendance_break_end') }}" method="POST">
                @csrf
                <button type="submit" class="action-break-button button">休憩戻</button>
            </form>
        @endif
        @if (session('message'))
            <p class="alert alert-success">
                {{ session('message') }}
            </p>
        @endif
    </div>
@endsection
