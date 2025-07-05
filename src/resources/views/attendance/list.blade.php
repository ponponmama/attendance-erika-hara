@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/attendance_list.css') }}">
@endsection

@section('content')
    <div class="{{ Auth::user()->role === 'admin' ? 'admin-list-container' : 'attendance-list-container' }}">
        <div class="{{ Auth::user()->role === 'admin' ? 'admin-list-header' : 'attendance-list-header' }}">
            @if (session('success'))
                <div class="alert-success">
                    {{ session('success') }}
                </div>
            @endif
            @if (Auth::user()->role !== 'admin')
                <h1 class="attendance-list-title">
                    勤怠一覧
                </h1>
            @endif
            <div class="month-switcher">
                @if (Auth::user()->role === 'admin')
                    <a href="{{ route('admin.attendance.list', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}"
                        class="month-arrow">← 前月</a>
                    <span class="current-month">
                        <img src="{{ asset('images/calendar.svg') }}" alt="カレンダー" class="calendar-icon">
                        {{ $currentMonth->format('Y/m/d') }}
                    </span>
                    <a href="{{ route('admin.attendance.list', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}"
                        class="month-arrow">翌月 →</a>
                @else
                    <a href="{{ route('attendance_list', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}"
                        class="month-arrow">← 前月</a>
                    <span class="current-month">
                        <img src="{{ asset('images/calendar.svg') }}" alt="カレンダー" class="calendar-icon">
                        {{ $currentMonth->format('Y/m') }}
                    </span>
                    <a href="{{ route('attendance_list', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}"
                        class="month-arrow">翌月 →</a>
                @endif
            </div>
        </div>
        <div
            class="{{ Auth::user()->role === 'admin' ? 'admin-attendance-list-table-container' : 'attendance-table-container' }}">
            @if ($attendances->count() > 0)
                <table class="{{ Auth::user()->role === 'admin' ? 'admin-attendance-list-table' : 'attendance-table' }}">
                    <thead>
                        <tr class="table-header-tr">
                            @if (Auth::user()->role === 'admin')
                                <th class="table-th">名前</th>
                            @endif
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
                                $totalBreakMinutes = 0;
                                $break_h = 0;
                                $break_m = 0;

                                if ($clockIn && $clockOut) {
                                    $workHours = $clockOut->diffInSeconds($clockIn) / 3600;

                                    foreach ($attendance->breakTimes as $break) {
                                        if ($break->break_start && $break->break_end) {
                                            $start = \Carbon\Carbon::parse($break->break_start);
                                            $end = \Carbon\Carbon::parse($break->break_end);
                                            $totalBreakMinutes += $end->diffInMinutes($start);
                                        }
                                    }
                                    $break_h = floor($totalBreakMinutes / 60);
                                    $break_m = $totalBreakMinutes % 60;

                                    $breakHours = $totalBreakMinutes / 60;

                                    $netWorkHours = $workHours - $breakHours;
                                }

                                $status = 'incomplete';
                                $statusText = '未完了';
                                if ($clockIn && $clockOut) {
                                    $status = 'complete';
                                    $statusText = '完了';
                                }
                            @endphp
                            <tr class="table-tr">
                                @if (Auth::user()->role === 'admin')
                                    <td class="table-td">{{ $attendance->user->name ?? '-' }}</td>
                                @endif
                                <td class="table-td">
                                    {{ $attendance->date->format('m/d') }}（{{ ['日', '月', '火', '水', '木', '金', '土'][$attendance->date->dayOfWeek] }}）
                                </td>
                                <td class="table-td">{{ $clockIn ? $clockIn->format('H:i') : '' }}</td>
                                <td class="table-td">{{ $clockOut ? $clockOut->format('H:i') : '' }}</td>
                                <td class="table-td">
                                    @if ($totalBreakMinutes > 0)
                                        <span class="break-hours">{{ sprintf('%02d:%02d', $break_h, $break_m) }}</span>
                                    @endif
                                </td>
                                <td class="table-td">
                                    @if ($netWorkHours > 0)
                                        @php
                                            $total_h = floor($netWorkHours);
                                            $total_m = round(($netWorkHours - $total_h) * 60);
                                        @endphp
                                        <span
                                            class="{{ Auth::user()->role === 'admin' ? 'total-hours' : 'work-hours' }}">{{ sprintf('%02d:%02d', $total_h, $total_m) }}</span>
                                    @endif
                                </td>
                                <td class="table-td">
                                    <a href="{{ route('attendance_detail', $attendance->id) }}" class="detail-link">詳細</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
