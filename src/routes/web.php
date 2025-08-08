<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

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
//ユーザー登録
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);
//ログイン(userとadmin共通)
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
//ログアウト(userとadmin共通)
Route::post('/logout', function () {
    $user = auth()->user();
    $isAdmin = $user && $user->role === 'admin';

    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    if ($isAdmin) {
        return redirect('/admin/login');
    }
    return redirect('/login');
})->name('logout');

// メール認証関連(user)
Route::get('/email/verify', function () {
    return view('verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance')->with('success', 'メール認証が完了しました。');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function () {
    request()->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// 一般ユーザー用ルート
Route::middleware(['auth', 'role:user', 'verified'])->group(function () {
    //打刻ページ（打刻ボタン押下時）
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance_index');
    //勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance_list');
    //打刻（各打刻ボタン押下時）
    Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance_clock_in');
    Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance_clock_out');
    Route::post('/break-start', [AttendanceController::class, 'breakStart'])->name('attendance_break_start');
    Route::post('/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance_break_end');
});

// 管理者用ルート
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    //勤怠一覧
    Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('attendance.list');
    //スタッフ一覧
    Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('staff.list');
    //スタッフ別勤怠一覧
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('attendance.staff');
    //スタッフ別勤怠一覧のCSV出力
    Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportStaffCsv'])
        ->name('attendance.staff.csv')
        ->middleware(['auth', 'role:admin']);
});



// 勤怠詳細（ユーザー・管理者共通、ミドルウェアで区別）
Route::get('/attendance/{id}', [AttendanceController::class, 'show'])
    ->middleware(['auth'])
    ->name('attendance_detail');

// 修正申請関連（StampCorrectionRequestControllerでuserとadmin共通）
Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'list'])
    ->middleware(['auth'])
    ->name('stamp_correction_request.list');

// 【一般ユーザー・管理者】修正申請の新規作成（申請ボタン押下時）
Route::post('/stamp_correction_request', [StampCorrectionRequestController::class, 'store'])
    ->middleware(['auth'])
    ->name('stamp_correction_request.store');

// 【一般ユーザー・管理者】修正申請の詳細表示
Route::get('/stamp_correction_request/{id}', [StampCorrectionRequestController::class, 'show'])
    ->middleware(['auth'])
    ->name('stamp_correction_request.show');

// 【管理者】修正申請の承認（承認ボタン押下時）
Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [StampCorrectionRequestController::class, 'approve'])
    ->middleware(['auth', 'role:admin'])
    ->name('stamp_correction_request.approve');
