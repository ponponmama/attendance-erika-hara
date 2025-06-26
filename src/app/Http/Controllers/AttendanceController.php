<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;

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

        return view('attendance', compact('now', 'status'));
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

        return view('attendance.list', compact('attendances', 'monthlyStats', 'currentMonth'));
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

        return view('attendance.detail', compact('attendance'));
    }
}