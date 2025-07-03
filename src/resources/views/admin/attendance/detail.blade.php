@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/correction_request_detail.css') }}">
@endsection

@section('content')
    <div class="admin-correction-request-detail-container">
        <h1 class="admin-correction-request-detail-title">
            修正申請詳細（管理者）
        </h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="request-info">
            <h2>申請情報</h2>
            <table class="request-info-table">
                <tr>
                    <th>申請者</th>
                    <td>{{ $request->user->name }}</td>
                </tr>
                <tr>
                    <th>対象日</th>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y年n月j日') }}</td>
                </tr>
                <tr>
                    <th>修正種別</th>
                    <td>
                        @if ($request->correction_type === 'clock_in')
                            出勤時間
                        @elseif($request->correction_type === 'clock_out')
                            退勤時間
                        @elseif($request->correction_type === 'break_start')
                            休憩開始時間
                        @elseif($request->correction_type === 'break_end')
                            休憩終了時間
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>現在の時間</th>
                    <td>{{ $request->current_time ? \Carbon\Carbon::parse($request->current_time)->format('H:i') : '未設定' }}
                    </td>
                </tr>
                <tr>
                    <th>申請時間</th>
                    <td>{{ \Carbon\Carbon::parse($request->requested_time)->format('H:i') }}</td>
                </tr>
                <tr>
                    <th>申請理由</th>
                    <td>{{ $request->reason }}</td>
                </tr>
                <tr>
                    <th>申請日</th>
                    <td>{{ \Carbon\Carbon::parse($request->request_date)->format('Y年n月j日') }}</td>
                </tr>
                <tr>
                    <th>状態</th>
                    <td>
                        @if ($request->status === 'pending')
                            <span class="status-pending">承認待ち</span>
                        @elseif($request->status === 'approved')
                            <span class="status-approved">承認済み</span>
                        @elseif($request->status === 'rejected')
                            <span class="status-rejected">却下</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @if ($request->status === 'pending')
            <div class="action-buttons">
                <form action="{{ route('admin.approve_request', $request->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="approve-button">承認</button>
                </form>
                <form action="{{ route('admin.reject_request', $request->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="reject-button">却下</button>
                </form>
            </div>
        @endif

        <div class="back-link">
            <a href="{{ route('admin.correction_request_list') }}">← 申請一覧に戻る</a>
        </div>
    </div>
@endsection
