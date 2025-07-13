<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    //管理者の勤怠一覧
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

    //スタッフ別勤怠一覧
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

    //管理者による勤怠データの更新
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

    // スタッフ別勤怠CSV出力
    public function exportStaffCsv(Request $request, $id)
    {
        // 1. 月情報を取得（例: '2023-06'）
        $month = $request->input('month');
        $currentMonth = $month
            ? \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : \Carbon\Carbon::now()->startOfMonth();

        // 2. ユーザー情報取得
        $user = \App\Models\User::findOrFail($id);

        // 3. 勤怠データ取得（指定月のデータを昇順で取得）
        $attendances = \App\Models\Attendance::where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'asc')
            ->get();

        // 4. CSVデータ作成
        // ヘッダー行
        $csv = "日付,出勤,退勤,休憩,合計\n";
        $wday = ['日', '月', '火', '水', '木', '金', '土']; // 曜日リスト
        foreach ($attendances as $attendance) {
            // 日付（例: 07/01（火））
            $date = $attendance->date ? $attendance->date->format('m/d') . '（' . $wday[$attendance->date->dayOfWeek] . '）' : '';
            // 出勤
            $clockIn = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '';
            // 退勤
            $clockOut = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '';
            // 休憩（分単位の合計をhh:mmに変換）
            $totalBreakMinutes = 0;
            foreach ($attendance->breakTimes as $break) {
                if ($break->break_start && $break->break_end) {
                    $start = \Carbon\Carbon::parse($break->break_start);
                    $end = \Carbon\Carbon::parse($break->break_end);
                    $totalBreakMinutes += $end->diffInMinutes($start);
                }
            }
            $break_h = floor($totalBreakMinutes / 60);
            $break_m = $totalBreakMinutes % 60;
            $breakStr = $totalBreakMinutes > 0 ? sprintf('%d:%02d', $break_h, $break_m) : '';
            // 合計（実働時間）
            $netWorkHours = '';
            if ($attendance->clock_in && $attendance->clock_out) {
                $clockInTime = \Carbon\Carbon::parse($attendance->clock_in);
                $clockOutTime = \Carbon\Carbon::parse($attendance->clock_out);
                $workMinutes = $clockOutTime->diffInMinutes($clockInTime) - $totalBreakMinutes;
                $work_h = floor($workMinutes / 60);
                $work_m = $workMinutes % 60;
                $netWorkHours = sprintf('%d:%02d', $work_h, $work_m);
            }
            // 1行分をCSVに追加
            $csv .= "$date,$clockIn,$clockOut,$breakStr,$netWorkHours\n";
        }

        // 5. ファイル名を作成（例: 西玲奈_2023年6月_勤怠.csv）
        $fileName = $user->name . '_' . $currentMonth->format('Y年n月') . '_勤怠.csv';

        // ★ここでBOMを付与
        $csv = "\xEF\xBB\xBF" . $csv;

        // 6. ダウンロードレスポンスを返す
        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
