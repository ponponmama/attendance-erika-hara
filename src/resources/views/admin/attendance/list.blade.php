{{-- 勤怠一覧画面（管理者） /admin/attendance/list --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/attendance_list.css') }}">
@endsection

@section('content')
    <div class="attendance-list-container">
        <div class="attendance-list-header">
            @if (session('success'))
                <p class="alert-success">
                    {{ session('success') }}
                </p>
            @endif
            <div class="title-container">
                <span class="title-border"></span>
                <h1 class="attendance-title">{{ $currentDate->format('Y年m月d日') }}の勤怠</h1>
            </div>
            <div class="date-switcher">
                <a href="{{ route('admin.attendance.list', ['date' => $currentDate->copy()->subDay()->format('Y-m-d')]) }}"
                    class="date-arrow link">
                    <img src="{{ asset('images/arrow.png') }}" alt="前日" class="arrow-icon">
                    <span class="arrow-text">前日</span>
                </a>
                <span class="current-date">
                    <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
                    {{ $currentDate->format('Y/m/d') }}
                </span>
                <a href="{{ route('admin.attendance.list', ['date' => $currentDate->copy()->addDay()->format('Y-m-d')]) }}"
                    class="date-arrow link">
                    <span class="arrow-text">翌日</span>
                    <img src="{{ asset('images/arrow.png') }}" alt="翌日" class="arrow-icon arrow-icon-right">
                    </a>
            </div>
        </div>
        <div class="attendance-table-container">
            <table class="attendance-table">
                <thead>
                    <tr class="table-header-tr">
                        <th class="table-th table-th-name">名前</th>
                        <th class="table-th">出勤</th>
                        <th class="table-th">退勤</th>
                        <th class="table-th">休憩</th>
                        <th class="table-th">合計</th>
                        <th class="table-th detail-link-th">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendances as $attendance)
                        @php
                            $clockIn = $attendance->clock_in ? Carbon\Carbon::parse($attendance->clock_in) : null;
                            $clockOut = $attendance->clock_out ? Carbon\Carbon::parse($attendance->clock_out) : null;

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
                        @endphp
                        <tr class="table-tr">
                            <td class="table-td table-td-name">{{ $attendance->user->name ?? '-' }}</td>
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
                                    <span class="total-hours">{{ sprintf('%02d:%02d', $total_h, $total_m) }}</span>
                                @endif
                            </td>
                            <td class="table-td detail-link-td">
                                <a href="{{ route('attendance_detail', $attendance->id) }}" class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
