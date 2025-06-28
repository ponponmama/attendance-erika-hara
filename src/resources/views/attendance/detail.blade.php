@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
    <div class="attendance-detail-container">
        <h1 class="attendance-detail-title">
            勤怠詳細
        </h1>
        <div class="attendance-detail-card">
            <form action="{{ url('/attendance/update/' . $attendance->id) }}" method="post">
                @csrf
                <table class="attendance-detail-table">
                    <tr>
                        <th class="attendance-detail-th">
                            名前
                        </th>
                        <td class="attendance-detail-td">
                            {{ $attendance->user->name ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th class="attendance-detail-th">
                            日付
                        </th>
                        <td class="attendance-detail-td">
                            {{ $attendance->date->format('Y年n月j日') }}
                        </td>
                    </tr>
                    <tr>
                        <th class="attendance-detail-th">
                            出勤・退勤
                        </th>
                        <td class="attendance-detail-td">
                            <input type="time" name="clock_in" class="attendance-detail-input"
                                value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}">
                            <span class="attendance-detail-tilde">〜</span>
                            <input type="time" name="clock_out" class="attendance-detail-input" value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}">
                        </td>
                    </tr>
                    <tr>
                        <th class="attendance-detail-th">休憩</th>
                        <td class="attendance-detail-td">
                            <input type="time" name="break_start_1" class="attendance-detail-input" value="{{ isset($attendance->breakTimes[0]) && $attendance->breakTimes[0]->break_start ? \Carbon\Carbon::parse($attendance->breakTimes[0]->break_start)->format('H:i') : '' }}">
                            <span class="attendance-detail-tilde">〜</span>
                            <input type="time" name="break_end_1" class="attendance-detail-input" value="{{ isset($attendance->breakTimes[0]) && $attendance->breakTimes[0]->break_end ? \Carbon\Carbon::parse($attendance->breakTimes[0]->break_end)->format('H:i') : '' }}">
                        </td>
                    </tr>
                    <tr>
                        <th class="attendance-detail-th">休憩2</th>
                        <td class="attendance-detail-td">
                            <input type="time" name="break_start_2" class="attendance-detail-input" value="{{ isset($attendance->breakTimes[1]) && $attendance->breakTimes[1]->break_start ? \Carbon\Carbon::parse($attendance->breakTimes[1]->break_start)->format('H:i') : '' }}">
                            <span class="attendance-detail-tilde">〜</span>
                            <input type="time" name="break_end_2" class="attendance-detail-input" value="{{ isset($attendance->breakTimes[1]) && $attendance->breakTimes[1]->break_end ? \Carbon\Carbon::parse($attendance->breakTimes[1]->break_end)->format('H:i') : '' }}">
                        </td>
                    </tr>
                    <tr>
                        <th class="attendance-detail-th">
                            備考
                        </th>
                        <td class="attendance-detail-td">
                            <textarea name="memo" class="attendance-detail-input">
                                {{ $attendance->memo ?? '' }}
                            </textarea>
                        </td>
                    </tr>
                </table>
                <div class="attendance-detail-btn-area">
                    <button type="submit" class="attendance-detail-edit-btn button">修正</button>
                </div>
            </form>
        </div>
    </div>
@endsection
