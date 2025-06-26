@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
    <div class="attendance-detail-container">
        <h1 class="attendance-detail-title">勤怠詳細</h1>
        <div class="attendance-detail-card">
            <table class="attendance-detail-table">
                <tr>
                    <th class="attendance-detail-th">日付</th>
                    <td class="attendance-detail-td">{{ $attendance->date->format('Y年m月d日 (D)') }}</td>
                </tr>
                <tr>
                    <th class="attendance-detail-th">出勤時間</th>
                    <td class="attendance-detail-td">
                        {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-' }}</td>
                </tr>
                <tr>
                    <th class="attendance-detail-th">退勤時間</th>
                    <td class="attendance-detail-td">
                        {{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-' }}
                    </td>
                </tr>
                <tr>
                    <th class="attendance-detail-th">休憩時間</th>
                    <td class="attendance-detail-td">
                        @if ($attendance->breakTimes->count() > 0)
                            @foreach ($attendance->breakTimes as $break)
                                @if ($break->break_start && $break->break_end)
                                    <span class="attendance-detail-break">
                                        {{ \Carbon\Carbon::parse($break->break_start)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($break->break_end)->format('H:i') }}
                                    </span>
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endif
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="attendance-detail-th">メモ</th>
                    <td class="attendance-detail-td">{{ $attendance->memo ?? '-' }}</td>
                </tr>
            </table>
            <div class="attendance-detail-back">
                <a href="{{ url('/attendance/list') }}" class="attendance-detail-back-link">一覧に戻る</a>
            </div>
        </div>
    </div>
@endsection
