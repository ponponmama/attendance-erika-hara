@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/stamp_correction_list.css') }}">
@endsection

@section('content')
    <div class="stamp-correction-list-container">
        <h1 class="stamp-correction-list-title">
            申請一覧
        </h1>
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <div class="tab-menu">
            <a href="{{ route('admin.attendance.stamp_correction_list', ['status' => 'pending']) }}"
                class="tab-item {{ $status === 'pending' ? 'tab-item-active' : '' }}">
                承認待ち
            </a>
            <a href="{{ route('admin.attendance.stamp_correction_list', ['status' => 'approved']) }}"
                class="tab-item {{ $status === 'approved' ? 'tab-item-active' : '' }}">
                承認済み
            </a>
        </div>
        <div class="list-table-container">
            <table class="list-table">
                <thead>
                    <tr class="table-header-tr">
                        <th class="table-th">状態</th>
                        <th class="table-th">名前</th>
                        <th class="table-th">対象日時</th>
                        <th class="table-th">申請理由</th>
                        <th class="table-th">申請日時</th>
                        <th class="table-th">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr class="table-tr">
                            <td class="table-td">
                                @if ($request->status === 'pending')
                                    承認待ち
                                @elseif($request->status === 'approved')
                                    承認済み
                                @endif
                            </td>
                            <td class="table-td">{{ $request->user->name ?? '-' }}</td>
                            <td class="table-td">
                                {{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') ?? '-' }}</td>
                            <td class="table-td">{{ $request->reason }}</td>
                            <td class="table-td">{{ \Carbon\Carbon::parse($request->request_date)->format('Y/m/d') }}</td>
                            <td class="table-td">
                                <a href="/admin/attendance/{{ $request->attendance->id }}" class="detail-link">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
