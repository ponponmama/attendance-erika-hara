@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
    <div class="attendance-list-container">
        <div class="attendance-list-header">
            <h1 class="attendance-list-title">勤怠一覧</h1>
            <div class="month-selector">
                <label for="month-select">月を選択:</label>
                <select id="month-select" onchange="changeMonth(this.value)">
                    @for ($i = 0; $i < 12; $i++)
                        @php
                            $month = Carbon\Carbon::now()->subMonths($i);
                            $selected = $month->format('Y-m') === $currentMonth->format('Y-m') ? 'selected' : '';
                        @endphp
                        <option value="{{ $month->format('Y-m') }}" {{ $selected }}>
                            {{ $month->format('Y年m月') }}
                        </option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-card__title">出勤日数</div>
                <div class="stat-card__value">{{ $monthlyStats['totalWorkDays'] }}日</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__title">総勤務時間</div>
                <div class="stat-card__value">{{ $monthlyStats['totalWorkHours'] }}時間</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__title">総休憩時間</div>
                <div class="stat-card__value">{{ $monthlyStats['totalBreakHours'] }}時間</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__title">実働時間</div>
                <div class="stat-card__value">{{ $monthlyStats['netWorkHours'] }}時間</div>
            </div>
        </div>

        <div class="attendance-table-container">
            @if ($attendances->count() > 0)
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>出勤時間</th>
                            <th>退勤時間</th>
                            <th>勤務時間</th>
                            <th>休憩時間</th>
                            <th>実働時間</th>
                            <th>ステータス</th>
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

                                $status = 'incomplete';
                                $statusText = '未完了';
                                if ($clockIn && $clockOut) {
                                    $status = 'complete';
                                    $statusText = '完了';
                                }
                            @endphp
                            <tr>
                                <td>{{ $attendance->date->format('m/d (D)') }}</td>
                                <td>{{ $clockIn ? $clockIn->format('H:i') : '-' }}</td>
                                <td>{{ $clockOut ? $clockOut->format('H:i') : '-' }}</td>
                                <td>
                                    @if ($workHours > 0)
                                        <span class="work-hours">{{ number_format($workHours, 1) }}時間</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($breakHours > 0)
                                        <span class="break-hours">{{ number_format($breakHours, 1) }}時間</span>
                                        <div class="break-times">
                                            @foreach ($attendance->breakTimes as $break)
                                                @if ($break->break_start && $break->break_end)
                                                    @php
                                                        $breakStart = Carbon\Carbon::parse($break->break_start);
                                                        $breakEnd = Carbon\Carbon::parse($break->break_end);
                                                    @endphp
                                                    {{ $breakStart->format('H:i') }}-{{ $breakEnd->format('H:i') }}
                                                    @if (!$loop->last)
                                                        ,
                                                    @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($netWorkHours > 0)
                                        <span class="work-hours">{{ number_format($netWorkHours, 1) }}時間</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $status }}">{{ $statusText }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-data">
                    この月の勤怠データがありません。
                </div>
            @endif
        </div>
    </div>

    <script>
        function changeMonth(monthValue) {
            // 月が変更されたときの処理（将来的にAjaxで実装可能）
            window.location.href = `/attendance/list?month=${monthValue}`;
        }
    </script>
@endsection

