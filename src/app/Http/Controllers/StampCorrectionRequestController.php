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
        $correctionData = $request->correction_data ? json_decode($request->correction_data, true) : null;

        if ($correctionData) {
            // 出勤時間の更新
            if (isset($correctionData['clock_in'])) {
                $attendance->clock_in = $correctionData['clock_in']['requested'] ?
                    \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($correctionData['clock_in']['requested']) : null;
            }

            // 退勤時間の更新
            if (isset($correctionData['clock_out'])) {
                $attendance->clock_out = $correctionData['clock_out']['requested'] ?
                    \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($correctionData['clock_out']['requested']) : null;
            }

            // 休憩時間の更新
            if (isset($correctionData['breaks'])) {
                foreach ($correctionData['breaks'] as $i => $breakData) {
                    $break = $attendance->breakTimes->get($i);
                    if ($break) {
                        $break->break_start = $breakData['break_start']['requested'] ?
                            \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($breakData['break_start']['requested']) : null;
                        $break->break_end = $breakData['break_end']['requested'] ?
                            \Carbon\Carbon::parse($attendance->date)->setTimeFromTimeString($breakData['break_end']['requested']) : null;
                        $break->save();
                    }
                }
            }
        }

        $attendance->memo = $request->reason;
        $attendance->save();

        return redirect()->back()->with('success', '承認しました');
    }

    public function store(StampCorrectionRequest $request)
    {
        $user = Auth::user();
        $attendance = \App\Models\Attendance::findOrFail($request->attendance_id);

        if ($user->role !== 'admin' && $attendance->user_id !== $user->id) {
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
        // どの項目を修正したいかをまとめて記録
        $correctionTypes = [];
        if ($request->has('clock_in') && $request->clock_in !== ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '')) {
            $correctionTypes[] = 'clock_in';
        }
        if ($request->has('clock_out') && $request->clock_out !== ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '')) {
            $correctionTypes[] = 'clock_out';
        }
        for ($i = 0; $i <= 10; $i++) {
            if ($request->input("break_start_{$i}")                     || $request->input("break_end_{$i}")) {
                $correctionTypes[] = 'break';
                break; // 1つでもあればbreakでOK
            }
        }
        if ($request->has('memo') && $request->memo !== ($attendance->memo ?? '')) {
            $correctionTypes[] = 'memo';
        }

        if (count($correctionTypes) > 0) {
            // 修正内容をJSON形式で保存
            $correctionData = [];

            // 出勤時間の修正
            if (in_array('clock_in', $correctionTypes)) {
                $correctionData['clock_in'] = [
                    'current' => $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    'requested' => $request->clock_in
                ];
            }

            // 退勤時間の修正
            if (in_array('clock_out', $correctionTypes)) {
                $correctionData['clock_out'] = [
                    'current' => $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : ''            ,
                    'requested' => $request->clock_out
                ];
            }

            // 休憩時間の修正
            if (in_array('break', $correctionTypes)) {
                $correctionData['breaks'] = [];
                for ($i = 0; $i <= 10; $i++) {
                    $breakStartKey = "break_start_{$i}";
                    $breakEndKey = "break_end_{$i}";

                    if ($request->filled($breakStartKey) || $request->filled($breakEndKey)) {
                        $currentBreak = isset($attendance->breakTimes[$i]) ? $attendance->breakTimes[$i] : null;
                        $correctionData['breaks'][$i] = [
                            'break_start' => [
                                'current' => $currentBreak && $currentBreak->break_start ? \Carbon\Carbon::parse($currentBreak->break_start)->format('H:i') : '',
                                'requested' => $request->input($breakStartKey)
                            ],
                            'break_end' => [
                                'current' => $currentBreak && $currentBreak->break_end ? \Carbon\Carbon::parse($currentBreak->break_end)->format('H:i') : '',
                                'requested' => $request->input($breakEndKey)
                            ]
                        ];
                    }
                }
            }

            \App\Models\StampCorrectionRequest::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'request_date' => now(),
                'status' => 'pending',
                'correction_type' => implode(',', $correctionTypes),
                'current_time' => null,
                'requested_time' => null,
                'reason' => $request->memo,
                'correction_data' => json_encode($correctionData),
            ]);
        }

        return redirect()->back()
            ->with('success', '修正申請を送信しました')
            ->withInput();
    }
}