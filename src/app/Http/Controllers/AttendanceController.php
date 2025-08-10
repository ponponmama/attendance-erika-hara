<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    //勤怠打刻ページ表示
    public function index()
    {
        $now = Carbon::now();
        $user = Auth::user();
        $today = $now->toDateString();

        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();
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

        return view('attendance.attendance', compact('now', 'status'));
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
        // 厳密比較だと型の違いで弾かれる可能性があるため、緩やかな比較に変更
        if ($user->role !== 'admin' && $attendance->user_id != $user->id) {
            abort(403);
        }

        // 最新の修正申請（pending/approved問わず）
        $latestRequest = $attendance->stampCorrectionRequests()->latest()->first();

        // 承認待ち・承認済みの修正申請をすべて取得
        $correctionRequests = $attendance->stampCorrectionRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->latest()
            ->get();

        Log::info($latestRequest);

        // 管理者用の最新の修正申請（なければnull）
        $stampCorrectionRequest = $attendance->stampCorrectionRequests()->latest()->first();

        return view('attendance.detail', compact('attendance', 'latestRequest', 'stampCorrectionRequest', 'correctionRequests'));
    }
}