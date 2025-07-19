<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Requests\LoginRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Events\Login;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::loginView(function () {
            return view('login');
        });

        Fortify::registerView(function () {
            return view('register');
        });

        Fortify::createUsersUsing(CreateNewUser::class);

        // LoginRequestを使用してログイン処理を行う
        Fortify::authenticateUsing(function ($request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                // 管理者ログインの場合はadminのみ許可
                if ($request->has('admin_login') && $user->role !== 'admin') {
                    throw ValidationException::withMessages([
                        'failed' => 'ログイン情報が登録されていません'
                    ]);
                }
                return $user;
            }

            throw ValidationException::withMessages([
                'failed' => 'ログイン情報が登録されていません'
            ]);
        });

        // ログイン後のリダイレクト先を分岐
        app('events')->listen(Login::class, function (Login $event) {
            $user = $event->user;
            // セッションにリダイレクト先を保存
            if ($user->role === 'admin') {
                session(['url.intended' => '/admin/attendance/list']);
            } else {
                session(['url.intended' => '/attendance']);
            }
        });
    }
}
