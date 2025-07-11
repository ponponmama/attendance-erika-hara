<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    //勤怠一覧
    public function list(Request $request)
    {
        // 月切り替え対応
        $monthParam = $request->get('month');
        $currentMonth = $monthParam
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.attendance.list', compact('attendances', 'currentMonth'));
    }

    //スタッフ別勤怠
    public function staffAttendance($id) {
        $user = \App\Models\User::findOrFail($id);
        $monthParam = request()->get('month');
        $currentMonth = $monthParam
            ? \Carbon\Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : \Carbon\Carbon::now()->startOfMonth();

        $attendances = \App\Models\Attendance::where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.attendance.staff', compact('user', 'attendances', 'currentMonth'));
    }

    //スタッフ一覧
    public function staffList() {
        // 一般ユーザーのみを取得（管理者以外）
        $users = \App\Models\User::where('role', 'user')->get();
        return view('admin.staff.list', compact('users'));
    }

    //管理者用勤怠更新
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // 管理者のみアクセス可能
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'memo' => 'nullable|string|max:1000',
        ]);

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

        return redirect()->route('admin.attendance.list')->with('success', '勤怠を更新しました。');
    }
}
