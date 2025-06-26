@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('title', '打刻')

@section('content')
    <div class="attendance-container">
        <div class="attendance-status">
            @if ($status === 'not_clocked_in')
                <span>勤務外</span>
            @elseif($status === 'clocked_in')
                <span>出勤中</span>
            @elseif($status === 'on_break')
                <span>休憩中</span>
            @else
                <span>勤務終了</span>
            @endif
        </div>
        <p class="attendance-date">
            {{ $now->format('Y年n月j日') }}({{ ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek] }})</p>
        <p class="attendance-time">{{ $now->format('H:i') }}</p>
    </div>
    <div class="attendance-actions">
        @if ($status === 'not_clocked_in')
            <form class="attendance-actions__form" action="{{ route('attendance_clock_in') }}" method="POST">
                @csrf
                <button type="submit" class="action-button button">出勤</button>
            </form>
        @elseif ($status === 'clocked_in')
            <form method="POST" action="{{ route('attendance_clock_out') }}">
                @csrf
                @if (session('message'))
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                @else
                    <button type="submit" class="action-button button">退勤</button>
                @endif
            </form>
            <form class="attendance-actions__form" action="{{ route('attendance_break_start') }}" method="POST">
                @csrf
                <button type="submit" class="action-break-button button">休憩中</button>
            </form>
        @elseif ($status === 'on_break')
            <form class="attendance-actions__form" action="{{ route('attendance_break_end') }}" method="POST">
                @csrf
                <button type="submit" class="action-break-button button">休憩戻</button>
            </form>
        @endif
        @if (session('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
    </div>
@endsection
