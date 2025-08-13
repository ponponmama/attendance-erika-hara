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
                {{-- 管理者ルート申請一覧より詳細画面(承認待ち・承認済みの表示のみ) --}}
                <div class="table-wrapper">1
                    <table class="attendance-detail-table">
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">名前</th>
                            <td class="attendance-detail-td">
                                <span class="attendance-detail-name">{{ $attendance->user->name }}</span>
                            </td>
                        </tr>
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">日付</th>
                            <td class="attendance-detail-td">
                                <span class="attendance-detail-date left-date">{{ $attendance->date->format('Y年') }}</span>
                                <span
                                    class="attendance-detail-date right-date">{{ $attendance->date->format('n月j日') }}</span>
                            </td>
                        </tr>
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">出勤・退勤</th>
                            <td class="attendance-detail-td">
                                <span
                                    class="attendance-detail-time">{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</span>
                                <span class="attendance-detail-tilde">〜</span>
                                <span
                                    class="attendance-detail-time">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</span>
                            </td>
                        </tr>
                        @foreach ($attendance->breakTimes as $i => $break)
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">休憩{{ $i + 1 }}</th>
                                <td class="attendance-detail-td">
                                    <span
                                        class="attendance-detail-time">{{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '' }}</span>
                                    <span class="attendance-detail-tilde">〜</span>
                                    <span
                                        class="attendance-detail-time">{{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '' }}</span>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">備考</th>
                            <td class="attendance-detail-td">
                                <span
                                    class="attendance-detail-text-memo">{{ $latestRequest->reason ?? $attendance->memo }}</span>
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
                {{-- 管理者ルート修正画面 --}}
                <form class="attendance-detail-form" action="{{ route('stamp_correction_request.store') }}" method="POST">
                    @csrf
                    <div class="table-wrapper">2
                        <table class="attendance-detail-table">
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">名前</th>
                                <td class="attendance-detail-td detail-td">{{ $attendance->user->name }}</td>
                            </tr>
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">日付</th>
                                <td class="attendance-detail-td detail-td">
                                    <span
                                        class="attendance-detail-date left-date ">{{ $attendance->date->format('Y年') }}</span>
                                    <span
                                        class="attendance-detail-date right-date">{{ $attendance->date->format('n月j日') }}</span>
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
                                        <input type="time" name="break_start_{{ $i }}"
                                            class="attendance-detail-input"
                                            value="{{ old('break_start_' . $i, $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '') }}">
                                        <span class="attendance-detail-tilde">〜</span>
                                        <input type="time" name="break_end_{{ $i }}"
                                            class="attendance-detail-input"
                                            value="{{ old('break_end_' . $i, $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '') }}">
                                        <p class="form__error">
                                            @error('break_start_' . $i)
                                                {{ $message }}
                                            @enderror
                                            @error('break_end_' . $i)
                                                {{ $message }}
                                            @enderror
                                        </p>
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
                                        <p class="form__error">
                                            @error('break_start_' . $i)
                                                {{ $message }}
                                            @enderror
                                            @error('break_end_' . $i)
                                                {{ $message }}
                                            @enderror
                                        </p>
                                    </td>
                                </tr>
                            @endfor
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">備考</th>
                                <td class="attendance-detail-td">
                                    <textarea name="memo" class="attendance-detail-input attendance-detail-memo">{{ old('memo', $attendance->memo ?? '') }}</textarea>
                                    <p class="form__error">
                                        @error('memo')
                                            {{ $message }}
                                        @enderror
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="attendance-detail-btn-area">
                        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                        <button type="submit" class="attendance-detail-edit-btn button">修正</button>
                    </div>
                </form>
            @endif
        @else
            {{-- スタッフ承認申請画面 --}}
            <form class="attendance-detail-form" action="{{ route('stamp_correction_request.store') }}" method="POST">
                @csrf
                <div class="table-wrapper">3
                    <table class="attendance-detail-table">
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">名前</th>
                            <td class="attendance-detail-td detail-td">{{ $attendance->user->name }}</td>
                        </tr>
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">日付</th>
                            <td class="attendance-detail-td detail-td">
                                <span
                                    class="attendance-detail-date left-date">{{ $attendance->date->format('Y年') }}</span>
                                <span
                                    class="attendance-detail-date right-date">{{ $attendance->date->format('n月j日') }}</span>
                            </td>
                        </tr>
                        <tr class="attendance-detail-tr">
                            <th class="attendance-detail-th">出勤・退勤</th>
                            <td class="attendance-detail-td">
                                @php
                                    $latestRequest = $attendance
                                        ->stampCorrectionRequests()
                                        ->whereIn('status', ['pending', 'approved'])
                                        ->latest()
                                        ->first();
                                    $correctionTypes = $latestRequest
                                        ? explode(',', $latestRequest->correction_type)
                                        : [];
                                    $hasClockInRequest = in_array('clock_in', $correctionTypes);
                                @endphp
                                <input type="time" name="clock_in" class="attendance-detail-input"
                                    value="{{ old('clock_in', $hasClockInRequest ? $latestRequest->correction_data['clock_in']['requested'] : ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '')) }}"{{ $latestRequest ? 'disabled' : '' }}>
                                <span class="attendance-detail-tilde">〜</span>
                                @php
                                    $hasClockOutRequest = in_array('clock_out', $correctionTypes);
                                @endphp
                                <input type="time" name="clock_out" class="attendance-detail-input"
                                    value="{{ old('clock_out', $hasClockOutRequest ? $latestRequest->correction_data['clock_out']['requested'] : ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '')) }}"
                                    {{ $latestRequest ? 'disabled' : '' }}>
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
                        @php
                            $breakCount = count($attendance->breakTimes);
                            $isPendingOrApproved =
                                $latestRequest && in_array($latestRequest->status, ['pending', 'approved']);
                        @endphp

                        @for ($i = 0; $i < $breakCount + 1; $i++)
                            @php
                                $hasBreakData =
                                    isset($attendance->breakTimes[$i]) &&
                                    ($attendance->breakTimes[$i]->break_start ||
                                        $attendance->breakTimes[$i]->break_end);
                                // 承認待ち・承認済みの場合は、データがある休憩のみ表示
                                if ($isPendingOrApproved && !$hasBreakData) {
                                    continue;
                                }
                            @endphp
                            <tr class="attendance-detail-tr">
                                <th class="attendance-detail-th">休憩{{ $i + 1 }}</th>
                                <td class="attendance-detail-td">
                                    @php
                                        $hasBreakStartRequest = in_array('break_' . $i . '_start', $correctionTypes);
                                    @endphp
                                    <input type="time" name="break_start_{{ $i }}"
                                        class="attendance-detail-input"
                                        value="{{ old('break_start_' . $i, $hasBreakStartRequest ? $latestRequest->correction_data['break_' . $i . '_start']['requested'] : (isset($attendance->breakTimes[$i]) && $attendance->breakTimes[$i]->break_start ? \Carbon\Carbon::parse($attendance->breakTimes[$i]->break_start)->format('H:i') : '')) }}"{{ $latestRequest ? 'disabled' : '' }}>
                                    <span class="attendance-detail-tilde input-tilde">〜</span>
                                    @php
                                        $hasBreakEndRequest = in_array('break_' . $i . '_end', $correctionTypes);
                                    @endphp
                                    <input type="time" name="break_end_{{ $i }}"
                                        class="attendance-detail-input"
                                        value="{{ old('break_end_' . $i, $hasBreakEndRequest ? $latestRequest->correction_data['break_' . $i . '_end']['requested'] : (isset($attendance->breakTimes[$i]) && $attendance->breakTimes[$i]->break_end ? \Carbon\Carbon::parse($attendance->breakTimes[$i]->break_end)->format('H:i') : '')) }}"{{ $latestRequest ? 'disabled' : '' }}>
                                    <p class="form__error">
                                        @error('break_start_' . $i)
                                            {{ $message }}
                                        @enderror
                                        @error('break_end_' . $i)
                                            {{ $message }}
                                        @enderror
                                    </p>
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
                                <p class="form__error">
                                    @error('memo')
                                        {{ $message }}
                                    @enderror
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="attendance-detail-btn-area">
                    @php
                        $hasPendingRequest = $attendance
                            ->stampCorrectionRequests()
                            ->whereIn('status', ['pending', 'approved'])
                            ->exists();
                    @endphp
                    @if ($hasPendingRequest)
                        <p class="pending-message">*承認待ちのため修正はできません。</p>
                    @else
                        <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                        <button type="submit" class="attendance-detail-edit-btn button">修正</button>
                    @endif
                </div>
            </form>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 空の時間入力フィールドの「--:--」を非表示にする
            const timeInputs = document.querySelectorAll('input[type="time"]');
            timeInputs.forEach(function(input) {
                if (!input.value) {
                    input.style.color = 'transparent';
                }

                // フォーカス時に文字色を元に戻す
                input.addEventListener('focus', function() {
                    this.style.color = 'black';
                });

                // フォーカスが外れた時に、値が空なら透明に戻す
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.style.color = 'transparent';
                    }
                });
            });
        });
    </script>
@endsection
