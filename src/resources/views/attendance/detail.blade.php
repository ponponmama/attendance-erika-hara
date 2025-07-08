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

        @if (Auth::user()->role === 'admin')
            @php
                $latestRequest = $attendance->stampCorrectionRequests()->latest()->first();
            @endphp
            <table class="attendance-detail-table">
                <tr class="attendance-detail-tr">
                    <th class="attendance-detail-th">名前</th>
                    <td class="attendance-detail-td td-text">{{ $attendance->user->name ?? '-' }}</td>
                </tr>
                <tr class="attendance-detail-tr">
                    <th class="attendance-detail-th">日付</th>
                    <td class="attendance-detail-td">
                        <span class="attendance-detail-date">{{ $attendance->date->format('Y年') }}</span>
                        <span class="attendance-detail-date">{{ $attendance->date->format('n月j日') }}</span>
                    </td>
                </tr>
                <tr class="attendance-detail-tr">
                    <th class="attendance-detail-th">出勤・退勤</th>
                    <td class="attendance-detail-td">
                        <span>{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</span>
                        <span class="attendance-detail-tilde">〜</span>
                        <span>{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</span>
                    </td>
                </tr>
                @foreach ($attendance->breakTimes as $i => $break)
                    <tr class="attendance-detail-tr">
                        <th class="attendance-detail-th">休憩{{ $i + 1 }}</th>
                        <td class="attendance-detail-td">
                            <span>{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}</span>
                            <span class="attendance-detail-tilde">〜</span>
                            <span>{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}</span>
                        </td>
                    </tr>
                @endforeach
                <tr class="attendance-detail-tr">
                    <th class="attendance-detail-th">備考</th>
                    <td class="attendance-detail-td">
                        {{ $attendance->memo }}
                    </td>
                </tr>
            </table>
            <div class="attendance-detail-btn-area">
                @if ($latestRequest)
                    @if ($latestRequest->status === 'pending')
                        <form method="POST" action="{{ route('stamp_correction_request.approve', $latestRequest->id) }}">
                            @csrf
                            <button type="submit" class="attendance-detail-edit-btn button">承認</button>
                        </form>
                    @elseif ($latestRequest->status === 'approved')
                        <span class="status-approved">承認済み</span>
                    @endif
                @else
                    <form class="attendance-detail-form" action="{{ route('admin.attendance.update', $attendance->id) }}"
                        method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="attendance-detail-edit-btn button">修正</button>
                    </form>
                @endif
            </div>
        @else
            <form class="attendance-detail-form" action="{{ route('stamp_correction_request.store') }}" method="POST">
                @csrf
                <table class="attendance-detail-table">
                    <tr class="attendance-detail-tr">
                        <th class="attendance-detail-th">名前</th>
                        <td class="attendance-detail-td td-text">{{ $attendance->user->name ?? '-' }}</td>
                    </tr>
                    <tr class="attendance-detail-tr">
                        <th class="attendance-detail-th">日付</th>
                        <td class="attendance-detail-td">
                            <span class="attendance-detail-date">{{ $attendance->date->format('Y年') }}</span>
                            <span class="attendance-detail-date">{{ $attendance->date->format('n月j日') }}</span>
                        </td>
                    </tr>
                    <tr class="attendance-detail-tr">
                        <th class="attendance-detail-th">出勤・退勤</th>
                        <td class="attendance-detail-td">
                            <input type="time" name="clock_in" class="attendance-detail-input"
                                value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}"
                                {{ $latestRequest && in_array($latestRequest->status, ['pending', 'approved']) ? 'disabled' : '' }}>
                            <span class="attendance-detail-tilde">〜</span>
                            <input type="time" name="clock_out" class="attendance-detail-input"
                                value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}"
                                {{ $latestRequest && in_array($latestRequest->status, ['pending', 'approved']) ? 'disabled' : '' }}>
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
                    @foreach ($attendance->breakTimes as $i => $break)
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">休憩{{ $i + 1 }}</th>
                            <td class="attendance-detail-td">
                                @if ($latestRequest && in_array($latestRequest->status, ['pending', 'approved']))
                                    <span>{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}</span>
                                    <span class="attendance-detail-tilde">〜</span>
                                    <span>{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}</span>
                                @else
                                    <input type="time" name="break_start_{{ $i + 1 }}"
                                        class="attendance-detail-input"
                                        value="{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}">
                                    <span class="attendance-detail-tilde">〜</span>
                                    <input type="time" name="break_end_{{ $i + 1 }}"
                                        class="attendance-detail-input"
                                        value="{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if (!($latestRequest && in_array($latestRequest->status, ['pending', 'approved'])))
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">休憩{{ count($attendance->breakTimes) + 1 }}</th>
                            <td class="attendance-detail-td">
                                <input type="time" name="break_start_{{ count($attendance->breakTimes) + 1 }}"
                                    class="attendance-detail-input" value="">
                                <span class="attendance-detail-tilde">〜</span>
                                <input type="time" name="break_end_{{ count($attendance->breakTimes) + 1 }}"
                                    class="attendance-detail-input" value="">
                            </td>
                        </tr>
                    @endif
                    <tr class="attendance-detail-tr">
                        <th class="attendance-detail-th">備考</th>
                        <td class="attendance-detail-td">
                            @if ($latestRequest && $latestRequest->status === 'pending')
                                <textarea name="memo" class="attendance-detail-input attendance-detail-memo" disabled>{{ $latestRequest->reason ?? $attendance->memo }}</textarea>
                            @else
                                <textarea name="memo" class="attendance-detail-input attendance-detail-memo">{{ old('memo', $attendance->memo ?? '') }}</textarea>
                            @endif
                            <p class="form__error">
                                @error('memo')
                                    {{ $message }}
                                @enderror
                            </p>
                        </td>
                    </tr>
                </table>
                <div class="attendance-detail-btn-area">
                    @if ($latestRequest)
                        @if ($latestRequest->status === 'pending')
                            <p class="pending-message">*承認待ちのため修正はできません。</p>
                        @endif
                    @else
                        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                        <button type="submit" class="attendance-detail-edit-btn button">修正</button>
                    @endif
                </div>
            </form>
        @endif
    </div>
@endsection
