<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
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

    public function detail($id) {
        // 勤怠データ取得
        $attendance = \App\Models\Attendance::with(['user', 'breakTimes', 'stampCorrectionRequests.user', 'stampCorrectionRequests.attendance'])
            ->findOrFail($id);

        // 修正申請一覧
        $requests = $attendance->stampCorrectionRequests;

        return view('admin.attendance.detail', compact('attendance', 'requests'));
    }

    public function staffAttendance($id) {
        // スタッフ別勤怠データ取得
        return view('admin.attendance.staff_attendance');
    }
}