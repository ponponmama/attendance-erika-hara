@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/attendance_detail.css') }}">
@endsection

@section('content')
    <div class="attendance-detail-container">
        <h1 class="attendance-detail-title">
            勤怠詳細
        </h1>
        @if ($errors->any())
            <div class="alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form class="attendance-detail-form" action="{{ url('/attendance/update/' . $attendance->id) }}" method="post">
            @csrf
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
                            value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}">
                        <span class="attendance-detail-tilde">〜</span>
                        <input type="time" name="clock_out" class="attendance-detail-input"
                            value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}">
                    </td>
                </tr>
                <tr class="attendance-detail-tr">
                    <th class="attendance-detail-th">休憩</th>
                    <td class="attendance-detail-td">
                        <input type="time" name="break_start_1" class="attendance-detail-input"
                            value="{{ isset($attendance->breakTimes[0]) && $attendance->breakTimes[0]->break_start ? \Carbon\Carbon::parse($attendance->breakTimes[0]->break_start)->format('H:i') : '' }}">
                        <span class="attendance-detail-tilde">〜</span>
                        <input type="time" name="break_end_1" class="attendance-detail-input"
                            value="{{ isset($attendance->breakTimes[0]) && $attendance->breakTimes[0]->break_end ? \Carbon\Carbon::parse($attendance->breakTimes[0]->break_end)->format('H:i') : '' }}">
                    </td>
                </tr>
                <tr class="attendance-detail-tr">
                    <th class="attendance-detail-th">休憩2</th>
                    <td class="attendance-detail-td">
                        <input type="time" name="break_start_2" class="attendance-detail-input"
                            value="{{ isset($attendance->breakTimes[1]) && $attendance->breakTimes[1]->break_start ? \Carbon\Carbon::parse($attendance->breakTimes[1]->break_start)->format('H:i') : '' }}">
                        <span class="attendance-detail-tilde">〜</span>
                        <input type="time" name="break_end_2" class="attendance-detail-input"
                            value="{{ isset($attendance->breakTimes[1]) && $attendance->breakTimes[1]->break_end ? \Carbon\Carbon::parse($attendance->breakTimes[1]->break_end)->format('H:i') : '' }}">
                    </td>
                </tr>
                <tr class="attendance-detail-tr">
                    <th class="attendance-detail-th">備考</th>
                    <td class="attendance-detail-td">
                        <textarea name="memo" class="attendance-detail-input attendance-detail-memo">{{ $attendance->memo ?? '' }}</textarea>
                    </td>
                </tr>
            </table>
            <div class="attendance-detail-btn-area">
                <button type="submit" class="attendance-detail-edit-btn button">修正</button>
            </div>
        </form>
    </div>
@endsection
