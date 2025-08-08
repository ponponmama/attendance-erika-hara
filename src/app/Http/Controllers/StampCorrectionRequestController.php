<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StampCorrectionRequest;

class StampCorrectionRequestController extends Controller
{
    public function list(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'pending');
        $tab = $request->get('tab', 'pending');

        if ($user->role === 'admin') {
            // 管理者用：全ユーザーの申請を取得
            $requests = \App\Models\StampCorrectionRequest::with(['user', 'attendance'])
                ->when($status === 'pending', function ($query) {
                    return $query->where('status', 'pending');
                })
                ->when($status === 'approved', function ($query) {
                    return $query->where('status', 'approved');
                })
                ->orderBy('request_date', 'desc')
                ->get();
        } else {
            // 一般ユーザー用：自分の申請のみ取得
            $requests = \App\Models\StampCorrectionRequest::where('user_id', $user->id)
                ->when($tab === 'approved', function ($query) {
                    return $query->where('status', 'approved');
                })
                ->when($tab === 'pending', function ($query) {
                    return $query->where('status', 'pending');
                })
                ->orderBy('request_date', 'desc')
                ->get();
        }

        return view('stamp_correction_request.list', compact('requests', 'status', 'tab'));
    }

    public function approve($attendance_correct_request)
    {
        $request = \App\Models\StampCorrectionRequest::findOrFail($attendance_correct_request);
        $request->status = 'approved';
        $request->approved_at = now();
        $request->approved_by = Auth::id();
        $request->save();

        // attendanceの該当項目を更新
        $attendance = $request->attendance;
        $correctionTypes = explode(',', $request->correction_type);

        // 出勤時間の更新
        if (in_array('clock_in', $correctionTypes) && $request->requested_time) {
            $attendance->clock_in = \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($request->requested_time);
        }

        // 退勤時間の更新
        if (in_array('clock_out', $correctionTypes) && $request->requested_time) {
            $attendance->clock_out = \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($request->requested_time);
        }

        // 休憩時間の更新（簡易版）
        if (in_array('break', $correctionTypes)) {
            // 休憩時間の更新は簡易的に処理
            // 実際の運用では、より詳細な処理が必要
        }

        $attendance->memo = $request->reason;
        $attendance->save();

        return redirect()->back()->with('success', '承認しました');
    }

    public function store(StampCorrectionRequest $request)
    {
        $user = Auth::user();
        $attendance = \App\Models\Attendance::findOrFail($request->attendance_id);

        if ($user->role !== 'admin' && $attendance->user_id != $user->id) {
            abort(403);
        }

        // 管理者の場合は直接更新
        if ($user->role === 'admin') {
            // 勤怠データを更新
            $updateData = [];

            if ($request->filled('clock_in')) {
                $updateData['clock_in'] = \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($request->clock_in);
            } else {
                $updateData['clock_in'] = null;
            }

            if ($request->filled('clock_out')) {
                $updateData['clock_out'] = \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($request->clock_out);
            } else {
                $updateData['clock_out'] = null;
            }

            if ($request->has('memo')) {
                $updateData['memo'] = $request->memo;
            } elseif ($request->has('reason')) {
                $updateData['memo'] = $request->reason;
            }

            $attendance->update($updateData);

            // 休憩時間を更新
            $breakTimes = $attendance->breakTimes;
            foreach ($breakTimes as $i => $break) {
                $breakStartKey = "break_start_" . $i;
                $breakEndKey = "break_end_" . $i;

                $breakData = [];

                if ($request->filled($breakStartKey)) {
                    $breakData['break_start'] = \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($request->input($breakStartKey));
                }

                if ($request->filled($breakEndKey)) {
                    $breakData['break_end'] = \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($request->input($breakEndKey));
                } else {
                    $breakData['break_end'] = null;
                }

                // 開始時間が入力されている場合のみ更新
                if (isset($breakData['break_start'])) {
                    $break->update($breakData);
                }
            }

            // 新しい休憩時間の処理
            $newBreakIndex = count($breakTimes);
            $newBreakStartKey = "break_start_" . $newBreakIndex;
            $newBreakEndKey = "break_end_" . $newBreakIndex;

            if ($request->filled($newBreakStartKey)) {
                $newBreakData = [
                    'attendance_id' => $attendance->id,
                    'break_start' => \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($request->input($newBreakStartKey)),
                    'break_end' => $request->filled($newBreakEndKey) ? \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($request->input($newBreakEndKey)) : null,
                ];

                \App\Models\BreakTime::create($newBreakData);
            }

            return redirect()->back()->with('success', '勤怠を更新しました');
        }

        // 一般ユーザーの場合は修正申請を作成
        // 出勤時間の修正を優先的に処理
        $currentClockIn = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '';
        $requestedClockIn = $request->input('clock_in', '');
        $currentClockOut = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '';
        $requestedClockOut = $request->input('clock_out', '');
        $requestedMemo = $request->input('memo', '');

        // 修正が必要かチェック
        $hasCorrection = false;
        $correctionTypes = [];

        // 出勤時間の修正チェック
        if ($request->has('clock_in') && $requestedClockIn !== $currentClockIn) {
            $correctionTypes[] = 'clock_in';
            $hasCorrection = true;
        }

        // 退勤時間の修正チェック
        if ($request->has('clock_out') && $requestedClockOut !== $currentClockOut) {
            $correctionTypes[] = 'clock_out';
            $hasCorrection = true;
        }

        // 休憩時間の修正チェック
        for ($i = 0; $i <= 10; $i++) {
            $breakStartKey = "break_start_{$i}";
            $breakEndKey = "break_end_{$i}";

            if ($request->has($breakStartKey) || $request->has($breakEndKey)) {
                $correctionTypes[] = 'break';
                $hasCorrection = true;
                break;
            }
        }

        // 備考の修正チェック
        $currentMemo = $attendance->memo ?? '';
        if ($request->has('memo') && $requestedMemo !== $currentMemo) {
            $correctionTypes[] = 'memo';
            $hasCorrection = true;
        }

        if ($hasCorrection) {
            // 出勤時間の修正を優先的に保存
            $currentTime = $currentClockIn;
            $requestedTime = $requestedClockIn;

            // 出勤時間に修正がない場合は退勤時間を保存
            if (!in_array('clock_in', $correctionTypes) && in_array('clock_out', $correctionTypes)) {
                $currentTime = $currentClockOut;
                $requestedTime = $requestedClockOut;
            }

            \App\Models\StampCorrectionRequest::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'request_date' => now(),
                'status' => 'pending',
                'correction_type' => implode(',', $correctionTypes),
                'current_time' => $currentTime,
                'requested_time' => $requestedTime,
                'reason' => $requestedMemo,
            ]);
        }

        return redirect()->route('stamp_correction_request.list')
            ->with('success', '修正申請を送信しました');
    }
}
