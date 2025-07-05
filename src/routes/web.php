<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 認証関連
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// 一般ユーザー用ルート
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('attendance_index');
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance_list');

    Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance_clock_in');
    Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance_clock_out');
    Route::post('/break-start', [AttendanceController::class, 'breakStart'])->name('attendance_break_start');
    Route::post('/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance_break_end');
    Route::post('/attendance/update/{id}', [AttendanceController::class, 'update'])->name('attendance_update');
});

// 管理者用ルート
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('staff.list');
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('attendance.staff');
    Route::post('/attendance/approve/{id}', [AdminAttendanceController::class, 'approve'])->name('attendance.approve');
});

// 勤怠詳細（ユーザー・管理者共通、ミドルウェアで区別）
Route::get('/attendance/{id}', [AttendanceController::class, 'show'])
    ->middleware(['auth'])
    ->name('attendance_detail');

// 修正申請一覧（ユーザー・管理者共通、ミドルウェアで区別）
Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'list'])
    ->middleware(['auth'])
    ->name('stamp_correction_request.list');

// 承認処理（管理者のみ）
Route::post('/stamp_correction_request/approve/{id}', [StampCorrectionRequestController::class, 'approve'])
    ->middleware(['auth', 'role:admin'])
    ->name('stamp_correction_request.approve');
