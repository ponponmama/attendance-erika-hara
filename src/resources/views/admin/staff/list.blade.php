{{-- スタッフ一覧画面（管理者） /admin/staff/list --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/staff_list.css') }}">
@endsection

@section('content')
    <div class="staff-list-container">
        <h1 class="attendance-title">
            スタッフ一覧
        </h1>
        <div class="staff-list-table-container">
            <table class="staff-list-table">
                <thead>
                    <tr class="table-header-tr">
                        <th class="table-th">名前</th>
                        <th class="table-th">メールアドレス</th>
                        <th class="table-th">勤怠一覧</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="table-tr">
                            <td class="table-td">{{ $user->name }}</td>
                            <td class="table-td">{{ $user->email }}</td>
                            <td class="table-td">
                                <a href="{{ route('admin.attendance.staff', $user->id) }}" class="detail-link">
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
