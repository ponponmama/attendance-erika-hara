@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/attendance_list.css') }}">
@endsection

@section('content')
    <div class="attendance-list-container">
        <div class="attendance-list-header">
            <h1 class="attendance-list-title">
                勤怠一覧
            </h1>
            <div class="month-switcher">
                <a href="{{ route('attendance_list', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}"
                    class="month-arrow">← 前月</a>
                <span class="current-month">
                    <img src="{{ asset('images/calendar.svg') }}" alt="カレンダー" class="calendar-icon">
                    {{ $currentMonth->format('Y/m') }}
                </span>
                <a href="{{ route('attendance_list', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}"
                    class="month-arrow">翌月 →</a>
            </div>
        </div>
        <div class="attendance-table-container">
            @if ($attendances->count() > 0)
                <table class="attendance-table">
                    <thead>
                        <tr class="table-header-tr">
                            <th class="table-th">日付</th>
                            <th class="table-th">出勤</th>
                            <th class="table-th">退勤</th>
                            <th class="table-th">休憩</th>
                            <th class="table-th">合計</th>
                            <th class="table-th">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            @php
                                $clockIn = $attendance->clock_in ? Carbon\Carbon::parse($attendance->clock_in) : null;
                                $clockOut = $attendance->clock_out
                                    ? Carbon\Carbon::parse($attendance->clock_out)
                                    : null;

                                $workHours = 0;
                                $breakHours = 0;
                                $netWorkHours = 0;

                                if ($clockIn && $clockOut) {
                                    $workHours = $clockOut->diffInSeconds($clockIn) / 3600;

                                    foreach ($attendance->breakTimes as $break) {
                                        if ($break->break_start && $break->break_end) {
                                            $breakStart = Carbon\Carbon::parse($break->break_start);
                                            $breakEnd = Carbon\Carbon::parse($break->break_end);
                                            $breakHours += $breakEnd->diffInSeconds($breakStart) / 3600;
                                        }
                                    }

                                    $netWorkHours = $workHours - $breakHours;
                                }
                            @endphp
                            <tr class="table-tr">
                                <td class="table-td">
                                    {{ $attendance->date->format('m/d') }}（{{ ['日', '月', '火', '水', '木', '金', '土'][$attendance->date->dayOfWeek] }}）
                                </td>
                                <td class="table-td">{{ $clockIn ? $clockIn->format('H:i') : '' }}</td>
                                <td class="table-td">{{ $clockOut ? $clockOut->format('H:i') : '' }}</td>
                                <td class="table-td">
                                    @if ($breakHours > 0)
                                        @php
                                            $break_h = floor($breakHours);
                                            $break_m = ($breakHours - $break_h) * 60;
                                        @endphp
                                        <span class="break-hours">{{ sprintf('%02d:%02d', $break_h, $break_m) }}</span>
                                    @endif
                                </td>
                                <td class="table-td">
                                    @if ($netWorkHours > 0)
                                        @php
                                            $hours = floor($netWorkHours);
                                            $minutes = ($netWorkHours - $hours) * 60;
                                        @endphp
                                        <span class="work-hours">{{ sprintf('%02d:%02d', $hours, $minutes) }}</span>
                                    @endif
                                </td>
                                <td class="table-td">
                                    <a href="{{ url('/attendance/' . $attendance->id) }}" class="detail-link">
                                        詳細
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
