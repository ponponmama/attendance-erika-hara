{{-- 勤怠詳細画面（一般ユーザー・管理者共通） /attendance/{id} --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/attendance_detail.css') }}">
@endsection

@section('content')
    <div class="attendance-detail-container">
        <div class="title-container">
            <span class="title-border"></span>
            <h1 class="attendance-title">
            勤怠詳細
            </h1>
        </div>
        @if (session('success'))
            <p class="alert-success">
                {{ session('success') }}
            </p>
        @endif

        @if (Auth::user()->role === 'admin')
            @php
                $latestRequest = $attendance->stampCorrectionRequests()->latest()->first();
            @endphp
            @if ($latestRequest && in_array($latestRequest->status, ['pending', 'approved']))
                {{-- 承認待ち・承認済みの場合は表示のみ --}}

                <div class="table-wrapper">
                    <table class="attendance-detail-table">
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">名前</th>
                            <td class="attendance-detail-td td-text">{{ $attendance->user->name ?? '-' }}</td>
                        </tr>
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">日付</th>
                            <td class="attendance-detail-td td-date">
                                <span class="attendance-detail-date-year">{{ $attendance->date->format('Y年') }}</span>
                                <span class="attendance-detail-date-day">{{ $attendance->date->format('n月j日') }}</span>
                            </td>
                        </tr>
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">出勤・退勤</th>
                            <td class="attendance-detail-td">
                                @php
                                    $correctionData = $latestRequest->correction_data
                                        ? json_decode($latestRequest->correction_data, true)
                                        : null;
                                @endphp
                                @if ($correctionData && isset($correctionData['clock_in']))
                                    <span class="attendance-detail-time">{{ $correctionData['clock_in']['requested'] }}</span>
                                @else
                                    <span class="attendance-detail-time">{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</span>
                                @endif
                                <span class="attendance-detail-tilde">〜</span>
                                @if ($correctionData && isset($correctionData['clock_out']))
                                    <span class="attendance-detail-time">{{ $correctionData['clock_out']['requested'] }}</span>
                                @else
                                    <span class="attendance-detail-time">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</span>
                                @endif
                            </td>
                        </tr>
                        @foreach ($attendance->breakTimes as $i => $break)
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">休憩{{ $i + 1 }}</th>
                                <td class="attendance-detail-td">
                                    @if ($correctionData && isset($correctionData['breaks'][$i]))
                                        <span class="attendance-detail-time">{{ $correctionData['breaks'][$i]['break_start']['requested'] }}</span>
                                        <span class="attendance-detail-tilde">〜</span>
                                        <span class="attendance-detail-time">{{ $correctionData['breaks'][$i]['break_end']['requested'] }}</span>
                                    @else
                                        <span class="attendance-detail-time">{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}</span>
                                        <span class="attendance-detail-tilde">〜</span>
                                        <span class="attendance-detail-time">{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">備考</th>
                            <td class="attendance-detail-td">
                                <span class="attendance-detail-memo">{{ $latestRequest->reason ?? $attendance->memo }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="attendance-detail-btn-area">
                    @if ($latestRequest->status === 'pending')
                        <form method="POST" action="{{ route('stamp_correction_request.approve', $latestRequest->id) }}">
                            @csrf
                            <button type="submit" class="attendance-detail-edit-btn button">承認</button>
                        </form>
                    @elseif ($latestRequest->status === 'approved')
                        <span class="status-approved">承認済み</span>
                    @endif
                </div>
            @else
                {{-- 管理者は常に編集可能 --}}
                <div class="table-wrapper">
                    <form class="attendance-detail-form" action="{{ route('stamp_correction_request.store') }}"
                        method="POST">
                        @csrf
                        <table class="attendance-detail-table">
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">名前</th>
                                <td class="attendance-detail-td td-text">{{ $attendance->user->name ?? '-' }}</td>
                            </tr>
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th td-date">日付</th>
                                <td class="attendance-detail-td">
                                    <span class="attendance-detail-date-year">{{ $attendance->date->format('Y年') }}</span>
                                    <span class="attendance-detail-date-day">{{ $attendance->date->format('n月j日') }}</span>
                                </td>
                            </tr>
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">出勤・退勤</th>
                                <td class="attendance-detail-td">
                                    <input type="time" name="clock_in" class="attendance-detail-input"
                                        value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}">
                                    <span class="attendance-detail-tilde">〜</span>
                                    <input type="time" name="clock_out" class="attendance-detail-input"
                                        value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}">
                                    @if (!($latestRequest && in_array($latestRequest->status, ['pending', 'approved'])))
                                        <p class="form__error">
                                            @error('clock_in')
                                                {{ $message }}
                                            @enderror
                                            @error('clock_out')
                                                {{ $message }}
                                            @enderror
                                        </p>
                                    @endif
                                </td>
                            </tr>
                            @foreach ($attendance->breakTimes as $i => $break)
                                <tr class="attendance-detail-tr">
                                    <th class="attendance-detail-th">休憩{{ $i + 1 }}</th>
                                    <td class="attendance-detail-td">
                                        <input type="time" name="break_start_{{ $i }}"
                                            class="attendance-detail-input"
                                            value="{{ old('break_start_' . $i, $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '') }}">
                                        <span class="attendance-detail-tilde">〜</span>
                                        <input type="time" name="break_end_{{ $i }}"
                                            class="attendance-detail-input"
                                            value="{{ old('break_end_' . $i, $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '') }}">
                                        @if (!($latestRequest && in_array($latestRequest->status, ['pending', 'approved'])))
                                            <p class="form__error">
                                                @error('break_start_' . $i)
                                                    {{ $message }}
                                                @enderror
                                                @error('break_end_' . $i)
                                                    {{ $message }}
                                                @enderror
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @php
                                $breakCount = count($attendance->breakTimes);
                            @endphp
                            @for ($i = $breakCount; $i < $breakCount + 1; $i++)
                                <tr class="attendance-detail-tr">
                                    <th class="attendance-detail-th">休憩{{ $i + 1 }}</th>
                                    <td class="attendance-detail-td">
                                        <input type="time" name="break_start_{{ $i }}"
                                            class="attendance-detail-input"
                                            value="{{ old('break_start_' . $i, isset($attendance->breakTimes[$i]) && $attendance->breakTimes[$i]->break_start ? \Carbon\Carbon::parse($attendance->breakTimes[$i]->break_start)->format('H:i') : '') }}">
                                        <span class="attendance-detail-tilde">〜</span>
                                        <input type="time" name="break_end_{{ $i }}"
                                            class="attendance-detail-input"
                                            value="{{ old('break_end_' . $i, isset($attendance->breakTimes[$i]) && $attendance->breakTimes[$i]->break_end ? \Carbon\Carbon::parse($attendance->breakTimes[$i]->break_end)->format('H:i') : '') }}">
                                        @if (!($latestRequest && in_array($latestRequest->status, ['pending', 'approved'])))
                                            <p class="form__error">
                                                @error('break_start_' . $i)
                                                    {{ $message }}
                                                @enderror
                                                @error('break_end_' . $i)
                                                    {{ $message }}
                                                @enderror
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">備考</th>
                                <td class="attendance-detail-td">
                                    <textarea name="memo" class="attendance-detail-input attendance-detail-memo">{{ old('memo', $attendance->memo ?? '') }}</textarea>
                                    @if (!($latestRequest && in_array($latestRequest->status, ['pending', 'approved'])))
                                        <p class="form__error">
                                            @error('memo')
                                                {{ $message }}
                                            @enderror
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        <div class="attendance-detail-btn-area">
                            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                            <button type="submit" class="attendance-detail-edit-btn button">修正</button>
                        </div>
                    </form>
                </div>
            @endif
        @else
            <form class="attendance-detail-form" action="{{ route('stamp_correction_request.store') }}" method="POST">
                @csrf
                <div class="table-wrapper">
                    <table class="attendance-detail-table">
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">名前</th>
                            <td class="attendance-detail-td td-text">{{ $attendance->user->name ?? '-' }}</td>
                        </tr>
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">日付</th>
                            <td class="attendance-detail-td td-date">
                                <span class="attendance-detail-date-year">{{ $attendance->date->format('Y年') }}</span>
                                <span class="attendance-detail-date-day">{{ $attendance->date->format('n月j日') }}</span>
                            </td>
                        </tr>
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">出勤・退勤</th>
                            <td class="attendance-detail-td">
                                @php
                                    $correctionData =
                                        $latestRequest && in_array($latestRequest->status, ['pending', 'approved'])
                                            ? ($latestRequest->correction_data
                                                ? json_decode($latestRequest->correction_data, true)
                                                : null)
                                            : null;
                                @endphp
                                <input type="time" name="clock_in" class="attendance-detail-input"
                                    value="{{ $correctionData && isset($correctionData['clock_in']) ? $correctionData['clock_in']['requested'] : (old('clock_in') ?: ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '')) }}"
                                    {{ $latestRequest && in_array($latestRequest->status, ['pending', 'approved']) ? 'disabled' : '' }}>
                                <span class="attendance-detail-tilde">〜</span>
                                <input type="time" name="clock_out" class="attendance-detail-input"
                                    value="{{ $correctionData && isset($correctionData['clock_out']) ? $correctionData['clock_out']['requested'] : (old('clock_out') ?: ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '')) }}"
                                    {{ $latestRequest && in_array($latestRequest->status, ['pending', 'approved']) ? 'disabled' : '' }}>
                                @if (!($latestRequest && in_array($latestRequest->status, ['pending', 'approved'])))
                                    <p class="form__error">
                                        @error('clock_in')
                                            {{ $message }}
                                        @enderror
                                        @error('clock_out')
                                            {{ $message }}
                                        @enderror
                                    </p>
                                @endif
                            </td>
                        </tr>
                        @php
                            $breakCount = count($attendance->breakTimes);
                        @endphp

                        @for ($i = 0; $i < $breakCount + 1; $i++)
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">休憩{{ $i + 1 }}</th>
                                <td class="attendance-detail-td">
                                    <input type="time" name="break_start_{{ $i }}"
                                        class="attendance-detail-input"
                                        value="{{ $correctionData && isset($correctionData['breaks'][$i]) ? $correctionData['breaks'][$i]['break_start']['requested'] : (old('break_start_' . $i) ?: (isset($attendance->breakTimes[$i]) && $attendance->breakTimes[$i]->break_start ? \Carbon\Carbon::parse($attendance->breakTimes[$i]->break_start)->format('H:i') : '')) }}"
                                        {{ $latestRequest && in_array($latestRequest->status, ['pending', 'approved']) ? 'disabled' : '' }}>
                                    <span class="attendance-detail-tilde">〜</span>
                                    <input type="time" name="break_end_{{ $i }}"
                                        class="attendance-detail-input"
                                        value="{{ $correctionData && isset($correctionData['breaks'][$i]) ? $correctionData['breaks'][$i]['break_end']['requested'] : (old('break_end_' . $i) ?: (isset($attendance->breakTimes[$i]) && $attendance->breakTimes[$i]->break_end ? \Carbon\Carbon::parse($attendance->breakTimes[$i]->break_end)->format('H:i') : '')) }}"
                                        {{ $latestRequest && in_array($latestRequest->status, ['pending', 'approved']) ? 'disabled' : '' }}>
                                    @if (!($latestRequest && in_array($latestRequest->status, ['pending', 'approved'])))
                                        <p class="form__error">
                                            @error('break_start_' . $i)
                                                {{ $message }}
                                            @enderror
                                            @error('break_end_' . $i)
                                                {{ $message }}
                                            @enderror
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @endfor
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">備考</th>
                            <td class="attendance-detail-td">
                                @if ($latestRequest && in_array($latestRequest->status, ['pending', 'approved']))
                                    <textarea name="memo" class="attendance-detail-input attendance-detail-memo" disabled>{{ $latestRequest->reason ?? $attendance->memo }}</textarea>
                                @else
                                    <textarea name="memo" class="attendance-detail-input attendance-detail-memo">{{ old('memo', $attendance->memo ?? '') }}</textarea>
                                @endif
                                @if (!($latestRequest && in_array($latestRequest->status, ['pending', 'approved'])))
                                    <p class="form__error">
                                        @error('memo')
                                            {{ $message }}
                                        @enderror
                                    </p>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
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
