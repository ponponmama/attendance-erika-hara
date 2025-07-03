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
use Laravel\Fortify\Events\Login;

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
            if (request()->has('admin') || request()->is('admin*')) {
                return view('admin.auth.login');
            }
            return view('users.auth.login');
        });

        Fortify::registerView(function () {
            return view('users.auth.register');
        });

        Fortify::createUsersUsing(CreateNewUser::class);

        // LoginRequestを使用してログイン処理を行う
        Fortify::authenticateUsing(function ($request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                // 管理者ログインの場合はadminのみ許可
                if ($request->has('admin_login') && $user->role !== 'admin') {
                    return null;
                }
                return $user;
            }
            return null;
        });

        // ログイン後のリダイレクト先を分岐
        app('events')->listen(Login::class, function (Login $event) {
            $user = $event->user;
            // セッションにリダイレクト先を保存
            if ($user->role === 'admin') {
                session(['url.intended' => '/admin/attendance/list']);
            } else {
                session(['url.intended' => '/']);
            }
        });
    }
}