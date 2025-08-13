<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StampCorrectionRequest;
use App\Models\StampCorrectionRequest as StampCorrectionRequestModel;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{
    //修正申告一覧表示
    public function list(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'pending');
        $tab = $request->get('tab', 'pending');

        if ($user->role === 'admin') {
            // 管理者用：全ユーザーの申請を取得
            $requests = StampCorrectionRequestModel::with(['user', 'attendance'])
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
            $requests = StampCorrectionRequestModel::where('user_id', $user->id)
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

    //修正申告承認処理
    public function approve($attendance_correct_request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request = StampCorrectionRequestModel::findOrFail($attendance_correct_request);
        $request->status = 'approved';
        $request->approved_at = now();
        $request->approved_by = Auth::id();
        $request->save();

        // attendanceの該当項目を更新
        $attendance = $request->attendance;
        $correctionData = $request->correction_data;

        // 出勤時間の更新
        if (isset($correctionData['clock_in'])) {
            $attendance->clock_in = Carbon::parse($attendance->date)->setTimeFromTimeString($correctionData['clock_in']['requested']);
        }

        // 退勤時間の更新
        if (isset($correctionData['clock_out'])) {
            $attendance->clock_out = Carbon::parse($attendance->date)->setTimeFromTimeString($correctionData['clock_out']['requested']);
        }

        // 休憩時間の更新
        foreach ($correctionData as $key => $data) {
            if (strpos($key, 'break_') === 0) {
                // break_0_start, break_0_end などの形式を解析
                $parts = explode('_', $key);
                if (count($parts) === 3) {
                    $breakIndex = $parts[1];
                    $breakField = $parts[2]; // start または end

                    // 該当する休憩時間を取得
                    $break = $attendance->breakTimes->get($breakIndex);
                    if ($break) {
                        $updateData = [];
                        if ($breakField === 'start') {
                            $updateData['break_start'] = Carbon::parse($attendance->date)->setTimeFromTimeString($data['requested']);
                        } elseif ($breakField === 'end') {
                            $updateData['break_end'] = Carbon::parse($attendance->date)->setTimeFromTimeString($data['requested']);
                        }

                        if (!empty($updateData)) {
                            $break->update($updateData);
                        }
                    }
                }
            }
        }

                $attendance->memo = $request->reason;
        $attendance->save();

        return redirect()->back()->with('success', '承認しました');
    }

    //修正申告作成処理
    public function store(StampCorrectionRequest $request)
    {
        $user = Auth::user();
        $attendance = Attendance::findOrFail($request->attendance_id);

        if ($user->role !== 'admin' && $attendance->user_id != $user->id) {
            abort(403);
        }

        // 管理者の場合は直接更新
        if ($user->role === 'admin') {
            // 勤怠データを更新
            $updateData = [];

            if ($request->filled('clock_in')) {
                $updateData['clock_in'] = Carbon::parse($attendance->date)->setTimeFromTimeString($request->clock_in);
            } else {
                $updateData['clock_in'] = null;
            }

            if ($request->filled('clock_out')) {
                $updateData['clock_out'] = Carbon::parse($attendance->date)->setTimeFromTimeString($request->clock_out);
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
                    $breakData['break_start'] = Carbon::parse($attendance->date)->setTimeFromTimeString($request->input($breakStartKey));
                }

                if ($request->filled($breakEndKey)) {
                    $breakData['break_end'] = Carbon::parse($attendance->date)->setTimeFromTimeString($request->input($breakEndKey));
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
                    'break_start' => Carbon::parse($attendance->date)->setTimeFromTimeString($request->input($newBreakStartKey)),
                    'break_end' => $request->filled($newBreakEndKey) ? Carbon::parse($attendance->date)->setTimeFromTimeString($request->input($newBreakEndKey)) : null,
                ];

                BreakTime::create($newBreakData);
            }

            return redirect()->back()->with('success', '勤怠を更新しました');
        }

        // 一般ユーザーの場合は修正申請を作成
        // 出勤時間の修正を優先的に処理
        $currentClockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
        $requestedClockIn = $request->input('clock_in', '');
        $currentClockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';
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
        $breakCorrections = [];
        for ($i = 0; $i <= 10; $i++) {
            $breakStartKey = "break_start_{$i}";
            $breakEndKey = "break_end_{$i}";

            if ($request->has($breakStartKey) || $request->has($breakEndKey)) {
                $breakCorrections[] = $i;
                $hasCorrection = true;
            }
        }

        // 備考の修正チェック
        $currentMemo = $attendance->memo ?? '';
        if ($request->has('memo') && $requestedMemo !== $currentMemo) {
            $correctionTypes[] = 'memo';
            $hasCorrection = true;
        }

        if ($hasCorrection) {
            // 既存の承認待ち申請を確認
            $existingRequest = StampCorrectionRequestModel::where('user_id', $user->id)
                ->where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->first();

            // 修正データを収集
            $correctionData = [];

            if ($request->has('clock_in') && $requestedClockIn !== $currentClockIn) {
                $correctionData['clock_in'] = [
                    'current' => $currentClockIn,
                    'requested' => $requestedClockIn
                ];
            }

            if ($request->has('clock_out') && $requestedClockOut !== $currentClockOut) {
                $correctionData['clock_out'] = [
                    'current' => $currentClockOut,
                    'requested' => $requestedClockOut
                ];
            }

            foreach ($breakCorrections as $breakIndex) {
                $breakStartKey = "break_start_{$breakIndex}";
                $breakEndKey = "break_end_{$breakIndex}";

                $currentBreak = $attendance->breakTimes->get($breakIndex);
                $currentBreakStart = $currentBreak && $currentBreak->break_start ? Carbon::parse($currentBreak->break_start)->format('H:i') : '';
                $currentBreakEnd = $currentBreak && $currentBreak->break_end ? Carbon::parse($currentBreak->break_end)->format('H:i') : '';

                $requestedBreakStart = $request->input($breakStartKey, '');
                $requestedBreakEnd = $request->input($breakEndKey, '');

                if ($request->has($breakStartKey) && $requestedBreakStart !== $currentBreakStart) {
                    $correctionData["break_{$breakIndex}_start"] = [
                        'current' => $currentBreakStart,
                        'requested' => $requestedBreakStart
                    ];
                }

                if ($request->has($breakEndKey) && $requestedBreakEnd !== $currentBreakEnd) {
                    $correctionData["break_{$breakIndex}_end"] = [
                        'current' => $currentBreakEnd,
                        'requested' => $requestedBreakEnd
                    ];
                }
            }

            // 1つのレコードに保存
            StampCorrectionRequestModel::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'request_date' => now(),
                'status' => 'pending',
                'correction_type' => implode(',', array_keys($correctionData)),
                'correction_data' => $correctionData,
                'current_time' => $correctionData[array_key_first($correctionData)]['current'],
                'requested_time' => $correctionData[array_key_first($correctionData)]['requested'],
                'reason' => $requestedMemo,
            ]);
        }

        return redirect()->back()
            ->with('success', '修正申請を送信しました');
    }
}
