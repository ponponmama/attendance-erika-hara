<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

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
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');


Route::middleware('auth')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('attendance_index');
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance_list');
    Route::get('/attendance/stamp_correction_list', [AttendanceController::class, 'stampCorrectionList'])->name('stamp_correction_list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance_detail');
    Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance_clock_in');
    Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance_clock_out');
    Route::post('/break-start', [AttendanceController::class, 'breakStart'])->name('attendance_break_start');
    Route::post('/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance_break_end');
    Route::post('/attendance/update/{id}', [AttendanceController::class, 'update'])->name('attendance_update');
});