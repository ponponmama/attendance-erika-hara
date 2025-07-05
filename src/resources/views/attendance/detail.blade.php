@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/attendance_detail.css') }}">
@endsection

@section('content')
    <div class="attendance-detail-container">
        <h1 class="attendance-detail-title">
            勤怠詳細
        </h1>
        @if (session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($stampCorrectionRequest)
            <form class="attendance-detail-form"
                action="{{ route('stamp_correction_request.approve', $stampCorrectionRequest->id) }}" method="post">
                @csrf
            @else
                <form class="attendance-detail-form" method="post">
                    @csrf
        @endif
        <table class="attendance-detail-table">
            <tr class="attendance-detail-tr">
                <th class="attendance-detail-th">
                    名前
                </th>
                <td class="attendance-detail-td td-text">
                    {{ $attendance->user->name ?? '-' }}
                </td>
            </tr>
            <tr class="attendance-detail-tr">
                <th class="attendance-detail-th">日付</th>
                <td class="attendance-detail-td">
                    <span class="attendance-detail-date">
                        {{ $attendance->date->format('Y年') }}
                    </span>
                    <span class="attendance-detail-date">
                        {{ $attendance->date->format('n月j日') }}
                    </span>
                </td>
            </tr>
            <tr class="attendance-detail-tr">
                <th class="attendance-detail-th">
                    出勤・退勤
                </th>
                <td class="attendance-detail-td">
                    <input type="time" name="clock_in" class="attendance-detail-input"
                        value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}"
                        {{ $latestRequest && $latestRequest->status === 'pending' ? 'disabled' : '' }}>
                    <span class="attendance-detail-tilde">
                        〜
                    </span>
                    <input type="time" name="clock_out" class="attendance-detail-input"
                        value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}"
                        {{ $latestRequest && $latestRequest->status === 'pending' ? 'disabled' : '' }}>
                    <p class="form__error">
                        @error('clock_in')
                            {{ $message }}
                        @enderror
                        @error('clock_out')
                            {{ $message }}
                        @enderror
                    </p>
                </td>
            </tr>
            <tr class="attendance-detail-tr">
                <th class="attendance-detail-th">
                    休憩
                </th>
                <td class="attendance-detail-td">
                    @foreach ($attendance->breakTimes as $i => $break)
                        <input type="time" name="break_start_{{ $i + 1 }}" class="attendance-detail-input"
                            value="{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}"
                            {{ $latestRequest && $latestRequest->status === 'pending' ? 'disabled' : '' }}>
                        <span class="attendance-detail-tilde">〜</span>
                        <input type="time" name="break_end_{{ $i + 1 }}" class="attendance-detail-input"
                            value="{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}"
                            {{ $latestRequest && $latestRequest->status === 'pending' ? 'disabled' : '' }}>
                        <p class="form__error">
                            @error('break_start_' . ($i + 1))
                                {{ $message }}
                            @enderror
                        </p>
                        <p class="form__error">
                            @error('break_end_' . ($i + 1))
                                {{ $message }}
                            @enderror
                        </p>
                        <br>
                    @endforeach
                    {{-- 追加用の空欄フィールド --}}
                    <input type="time" name="break_start_{{ count($attendance->breakTimes) + 1 }}"
                        class="attendance-detail-input" value=""
                        {{ $latestRequest && $latestRequest->status === 'pending' ? 'disabled' : '' }}>
                    <span class="attendance-detail-tilde">〜</span>
                    <input type="time" name="break_end_{{ count($attendance->breakTimes) + 1 }}"
                        class="attendance-detail-input" value=""
                        {{ $latestRequest && $latestRequest->status === 'pending' ? 'disabled' : '' }}>
                    <p class="form__error">
                        @error('break_start_' . (count($attendance->breakTimes) + 1))
                            {{ $message }}
                        @enderror
                    </p>
                    <p class="form__error">
                        @error('break_end_' . (count($attendance->breakTimes) + 1))
                            {{ $message }}
                        @enderror
                    </p>
                </td>
            </tr>
            <tr class="attendance-detail-tr">
                <th class="attendance-detail-th">
                    備考
                </th>
                <td class="attendance-detail-td">
                    <textarea name="memo" class="attendance-detail-input attendance-detail-memo"
                        {{ $latestRequest && $latestRequest->status === 'pending' ? 'disabled' : '' }}>{{ old('memo', $attendance->memo ?? '') }}</textarea>
                    <p class="form__error"></p>
                    @error('memo')
                        {{ $message }}
                    @enderror
                    </p>
                </td>
            </tr>
        </table>
        <div class="attendance-detail-btn-area">
            @if (Auth::user()->role === 'admin')
                {{-- 管理者用：承認ボタンの分岐 --}}
                @if ($stampCorrectionRequest && $stampCorrectionRequest->status === 'pending')
                    <form method="POST"
                        action="{{ route('stamp_correction_request.approve', $stampCorrectionRequest->id) }}">
                        @csrf
                        <button type="submit" class="attendance-detail-edit-btn button">承認</button>
                    </form>
                @elseif ($stampCorrectionRequest && $stampCorrectionRequest->status === 'approved' && $stampCorrectionRequest->approved_by)
                    <button class="attendance-detail-edit-btn button" disabled>承認済み</button>
                @endif
            @else
                {{-- 一般ユーザー用：修正ボタンの分岐 --}}
                @if ($latestRequest && $latestRequest->status === 'pending')
                    <p class="pending-message">
                        *承認待ちのため修正はできません。
                    </p>
                @else
                    <button type="submit" name="action" value="request" class="attendance-detail-edit-btn button">
                        修正
                    </button>
                @endif
            @endif
        </div>
        </form>
    </div>
@endsection
