<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Http\Requests\UpdateAttendanceRequest;

class AttendanceController extends Controller
{
    //勤怠打刻ページ表示
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

    //勤怠一覧ページ表示
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

        return view('attendance.list', compact('attendances', 'monthlyStats', 'currentMonth'));
    }

    //月別の勤怠統計を計算
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

    //出勤処理
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

    //休憩開始処理
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

    //休憩終了処理
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

    //退勤処理
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

    //勤怠詳細ページ表示（管理者・一般ユーザー共通）
    public function show($id)
    {
        $user = Auth::user();
        $attendance = Attendance::with(['user', 'breakTimes', 'stampCorrectionRequests' => function($q) {
            $q->latest();
        }])->findOrFail($id);

        // 一般ユーザーの場合は自分の勤怠のみアクセス可能
        if ($user->role !== 'admin' && $attendance->user_id !== $user->id) {
            abort(403);
        }

        // 申請中の最新の修正申請（なければnull）
        $latestRequest = $attendance->stampCorrectionRequests()
            ->where('status', 'pending')
            ->latest()
            ->first();

        // 管理者用の最新の修正申請（なければnull）
        $stampCorrectionRequest = $attendance->stampCorrectionRequests()->latest()->first();

        return view('attendance.detail', compact('attendance', 'latestRequest', 'stampCorrectionRequest'));
    }



    public function update(UpdateAttendanceRequest $request, $id)
    {
        $user = Auth::user();
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$attendance) {
            abort(404);
        }

        // 申請中の修正申請があるかチェック
        $pendingRequest = $attendance->stampCorrectionRequests()
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return redirect()->route('attendance_detail', $id)
                ->with('error', '申請中のため修正できません。');
        }

        // 修正申請の場合
        if ($request->input('action') === 'request') {
            return $this->createCorrectionRequest($request, $attendance, $user);
        }

        // 通常の修正の場合
        $attendance->update([
            'clock_in' => $request->clock_in ? Carbon::parse($request->clock_in)->format('H:i:s') : null,
            'clock_out' => $request->clock_out ? Carbon::parse($request->clock_out)->format('H:i:s') : null,
            'memo' => $request->memo,
        ]);

        $this->updateBreakTimes($attendance, $request);

        return redirect()->route('attendance_detail', $id)->with('success', '勤怠情報を更新しました');
    }

    private function createCorrectionRequest($request, $attendance, $user)
    {
        // 修正申請データを作成
        $correctionData = [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'request_date' => $attendance->date,
            'status' => 'pending',
        ];

        // 出勤時間の変更がある場合
        if ($request->clock_in && ($attendance->clock_in ? $attendance->clock_in->format('H:i') : null) !== $request->clock_in) {
            $correctionData['correction_type'] = 'clock_in';
            $correctionData['current_time'] = $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : null;
            $correctionData['requested_time'] = Carbon::parse($request->clock_in)->format('H:i:s');
            $correctionData['reason'] = $request->memo;

            StampCorrectionRequest::create($correctionData);
        }

        // 退勤時間の変更がある場合
        if ($request->clock_out && ($attendance->clock_out ? $attendance->clock_out->format('H:i') : null) !== $request->clock_out) {
            $correctionData['correction_type'] = 'clock_out';
            $correctionData['current_time'] = $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : null;
            $correctionData['requested_time'] = Carbon::parse($request->clock_out)->format('H:i:s');
            $correctionData['reason'] = $request->memo;

            StampCorrectionRequest::create($correctionData);
        }

        return redirect()->route('attendance_detail', $attendance->id)
            ->with('success', '修正申請を送信しました。承認をお待ちください。');
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
