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

    public function approve($id)
    {
        $request = \App\Models\StampCorrectionRequest::findOrFail($id);
        $request->status = 'approved';
        $request->approved_at = now();
        $request->approved_by = Auth::id();
        $request->save();

        // attendanceの該当項目を更新
        $attendance = $request->attendance;
        if ($request->correction_type === 'clock_in') {
            $attendance->clock_in = $request->requested_time;
        } elseif ($request->correction_type === 'clock_out') {
            $attendance->clock_out = $request->requested_time;
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

        // どの項目を修正したいかをまとめて記録
        $correctionTypes = [];
        if ($request->clock_in && $request->clock_in !== ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '')) {
            $correctionTypes[] = 'clock_in';
        }
        if ($request->clock_out && $request->clock_out !== ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '')) {
            $correctionTypes[] = 'clock_out';
        }
        for ($i = 1; $i <= 10; $i++) {
            if ($request->input("break_start_{$i}") || $request->input("break_end_{$i}")) {
                $correctionTypes[] = 'break';
                break; // 1つでもあればbreakでOK
            }
        }
        if ($request->memo && $request->memo !== $attendance->memo) {
            $correctionTypes[] = 'memo';
        }

        if (count($correctionTypes) > 0) {
            \App\Models\StampCorrectionRequest::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'request_date' => now(),
                'status' => 'pending',
                'correction_type' => implode(',', $correctionTypes), // 例: "clock_in,clock_out,break,memo"
                'requested_time' => null, // 必要に応じて代表値を入れる
                'reason' => $request->memo,
            ]);
        }

        return redirect()->back()->with('success', '修正申請を送信しました');
    }
}