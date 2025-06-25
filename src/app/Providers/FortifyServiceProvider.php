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
        Fortify::authenticateUsing(function (Request $request) {
            $loginRequest = new LoginRequest();
            $loginRequest->setContainer($this->app);
            $loginRequest->setRedirector($this->app['redirect']);
            $loginRequest->merge($request->all());

            // バリデーション
            $validator = Validator::make($request->all(), $loginRequest->rules(), $loginRequest->messages());
            if ($validator->fails()) {
                // バリデーションエラーはValidationExceptionを投げる
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            // LoginRequestのauthenticate()メソッドを使用して認証処理
            $loginRequest->authenticate();

            // 認証成功時はユーザーを返す
            return Auth::user();
        });
    }
}