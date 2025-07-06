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

        return view('attendance.list', compact('user', 'attendances', 'currentMonth'));
    }

    //スタッフ一覧
    public function staffList() {
        // 一般ユーザーのみを取得（管理者以外）
        $users = \App\Models\User::where('role', 'user')->get();
        return view('admin.staff.list', compact('users'));
    }

    // 勤怠承認処理
    public function approve($id)
    {
        $attendance = Attendance::findOrFail($id);

        // 承認処理のロジックをここに実装
        // 現在はStampCorrectionRequestControllerで処理されているため、
        // このメソッドは必要に応じて実装

        return redirect()->back()->with('success', '勤怠を承認しました');
    }
}
