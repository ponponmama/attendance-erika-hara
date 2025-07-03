<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;

class AttendanceController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $user = Auth::user();
        $today = $now->toDateString();

        $attendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();
        $break = $attendance ? BreakTime::where('attendance_id', $attendance->id)->whereNull('break_end')->first() : null;

        $status = 'not_clocked_in';
        if ($attendance) {
            if ($break) {
                $status = 'on_break';
            } elseif ($attendance->clock_out) {
                $status = 'clocked_out';
            } else {
                $status = 'clocked_in';
            }
        }

        return view('users.attendance.attendance', compact('now', 'status'));
    }

    public function list(Request $request)
    {
        $user = Auth::user();

        // クエリパラメータから月を取得、なければ現在の月を使用
        $monthParam = $request->get('month');
        if ($monthParam) {
            $currentMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        } else {
            $currentMonth = Carbon::now()->startOfMonth();
        }

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->with(['breakTimes'])
            ->orderBy('date', 'desc')
            ->get();

        // 月別の勤怠統計を計算
        $monthlyStats = $this->calculateMonthlyStats($attendances);

        return view('users.attendance.list', compact('attendances', 'monthlyStats', 'currentMonth'));
    }

    private function calculateMonthlyStats($attendances)
    {
        $totalWorkDays = 0;
        $totalWorkHours = 0;
        $totalBreakHours = 0;

        foreach ($attendances as $attendance) {
            if ($attendance->clock_in && $attendance->clock_out) {
                $totalWorkDays++;

                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);
                $workHours = $clockOut->diffInSeconds($clockIn) / 3600;
                $totalWorkHours += $workHours;

                // 休憩時間を計算
                $breakHours = 0;
                foreach ($attendance->breakTimes as $break) {
                    if ($break->break_start && $break->break_end) {
                        $breakStart = Carbon::parse($break->break_start);
                        $breakEnd = Carbon::parse($break->break_end);
                        $breakHours += $breakEnd->diffInSeconds($breakStart) / 3600;
                    }
                }
                $totalBreakHours += $breakHours;
            }
        }

        return [
            'totalWorkDays' => $totalWorkDays,
            'totalWorkHours' => round($totalWorkHours, 2),
            'totalBreakHours' => round($totalBreakHours, 2),
            'netWorkHours' => round($totalWorkHours - $totalBreakHours, 2),
        ];
    }

    public function clockIn()
    {
        $userId = Auth::id();
        $today = Carbon::today();

        // すでに出勤済みか確認
        $existingAttendance = Attendance::where('user_id', $userId)->where('date', $today)->first();

        if (!$existingAttendance) {
            Attendance::create([
                'user_id' => $userId,
                'date' => $today,
                'clock_in' => Carbon::now(),
            ]);
        }

        return redirect('/attendance');
    }

    public function breakStart()
    {
        $userId = Auth::id();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $userId)->where('date', $today)->first();

        // 出勤中かつ休憩中でない場合のみ休憩開始
        if ($attendance && !$attendance->clock_out) {
            $isNotOnBreak = BreakTime::where('attendance_id', $attendance->id)->whereNull('break_end')->doesntExist();
            if ($isNotOnBreak) {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => Carbon::now(),
                ]);
            }
        }

        return redirect('/attendance');
    }

    public function breakEnd()
    {
        $userId = Auth::id();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $userId)->where('date', $today)->first();

        if ($attendance) {
            $break = BreakTime::where('attendance_id', $attendance->id)->whereNull('break_end')->first();
            if ($break) {
                $break->update([
                    'break_end' => Carbon::now(),
                ]);
            }
        }

        return redirect('/attendance');
    }

    public function clockOut()
    {
        $userId = Auth::id();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $userId)->where('date', $today)->first();

        // 出勤中であり、かつ退勤していない場合のみ退勤処理
        if ($attendance && !$attendance->clock_out) {
            $attendance->update([
                'clock_out' => Carbon::now(),
            ]);
        }

        return redirect()->route('attendance_index')->with('message', 'お疲れ様でした。');
    }

    public function show($id)
    {
        $user = Auth::user();
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->with('breakTimes')
            ->firstOrFail();

        return view('users.attendance.detail', compact('attendance'));
    }

    public function stampCorrectionList()
    {
        $user = Auth::user();
        $requests = StampCorrectionRequest::where('user_id', $user->id)
            ->whereHas('attendance', function ($query) {
                $query->whereNotNull('memo')->where('memo', '!=', '');
            })
            ->orderBy('request_date', 'desc')
            ->get();

        return view('users.attendance.stamp_correction_list', compact('requests'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // バリデーション
        $request->validate([
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'break_start_1' => 'nullable|date_format:H:i',
            'break_end_1' => 'nullable|date_format:H:i',
            'break_start_2' => 'nullable|date_format:H:i',
            'break_end_2' => 'nullable|date_format:H:i',
            'memo' => 'required|string|max:1000',
        ], [
            'memo.required' => '備考を記入してください',
        ]);

        // 出勤時間と退勤時間の妥当性チェック
        if ($request->clock_in && $request->clock_out) {
            $clockIn = Carbon::parse($request->clock_in);
            $clockOut = Carbon::parse($request->clock_out);

            if ($clockIn->greaterThanOrEqualTo($clockOut)) {
                return back()->withErrors(['clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);
            }
        }

        // 休憩時間の妥当性チェック
        if ($request->clock_in && $request->clock_out) {
            $clockIn = Carbon::parse($request->clock_in);
            $clockOut = Carbon::parse($request->clock_out);

            // 休憩1のバリデーション
            if ($request->break_start_1 && $request->break_end_1) {
                $breakStart1 = Carbon::parse($request->break_start_1);
                $breakEnd1 = Carbon::parse($request->break_end_1);

                // 休憩開始時間が休憩終了時間より後でないこと
                if ($breakStart1->greaterThanOrEqualTo($breakEnd1)) {
                    return back()->withErrors(['break_start_1' => '休憩時間が不適切な値です']);
                }

                // 休憩時間が勤務時間内にあること
                if ($breakStart1->lessThan($clockIn) || $breakEnd1->greaterThan($clockOut)) {
                    return back()->withErrors(['break_start_1' => '休憩時間が勤務時間外です']);
                }
            }

            // 休憩2のバリデーション
            if ($request->break_start_2 && $request->break_end_2) {
                $breakStart2 = Carbon::parse($request->break_start_2);
                $breakEnd2 = Carbon::parse($request->break_end_2);

                // 休憩開始時間が休憩終了時間より後でないこと
                if ($breakStart2->greaterThanOrEqualTo($breakEnd2)) {
                    return back()->withErrors(['break_start_2' => '休憩時間が不適切な値です']);
                }

                // 休憩時間が勤務時間内にあること
                if ($breakStart2->lessThan($clockIn) || $breakEnd2->greaterThan($clockOut)) {
                    return back()->withErrors(['break_start_2' => '休憩時間が勤務時間外です']);
                }
            }
        }

        // 勤怠データを更新
        $attendance->update([
            'clock_in' => $request->clock_in ? Carbon::parse($request->clock_in)->format('H:i:s') : null,
            'clock_out' => $request->clock_out ? Carbon::parse($request->clock_out)->format('H:i:s') : null,
            'memo' => $request->memo,
        ]);

        // 休憩時間の更新
        $this->updateBreakTimes($attendance, $request);

        return redirect()->route('attendance_detail', $id)->with('success', '勤怠情報を更新しました');
    }

    private function updateBreakTimes($attendance, $request)
    {
        // 既存の休憩データを削除
        $attendance->breakTimes()->delete();

        // 新しい休憩データを作成
        if ($request->break_start_1 && $request->break_end_1) {
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => Carbon::parse($request->break_start_1)->format('H:i:s'),
                'break_end' => Carbon::parse($request->break_end_1)->format('H:i:s'),
            ]);
        }

        if ($request->break_start_2 && $request->break_end_2) {
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => Carbon::parse($request->break_start_2)->format('H:i:s'),
                'break_end' => Carbon::parse($request->break_end_2)->format('H:i:s'),
            ]);
        }
    }
}