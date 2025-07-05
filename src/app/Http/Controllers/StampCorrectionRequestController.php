<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                ->whereHas('attendance', function ($query) {
                    $query->whereNotNull('memo')->where('memo', '!=', '');
                })
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
}
