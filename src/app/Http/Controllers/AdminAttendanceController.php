<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
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

        return view('attendance.list', compact('attendances', 'currentMonth'));
    }



    //スタッフ別勤怠
    public function staffAttendance($id) {
        // スタッフ情報を取得
        $staff = \App\Models\User::findOrFail($id);

        // そのスタッフの勤怠データを取得
        $attendances = \App\Models\Attendance::with(['breakTimes'])
            ->where('user_id', $id)
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.attendance.staff_attendance', compact('staff', 'attendances'));
    }

    //スタッフ一覧
    public function staffList() {
        // 一般ユーザーのみを取得（管理者以外）
        $users = \App\Models\User::where('role', 'user')->get();
        return view('admin.staff.list', compact('users'));
    }


}
