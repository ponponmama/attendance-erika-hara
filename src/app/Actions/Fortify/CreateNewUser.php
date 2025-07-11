<?php

namespace App\Actions\Fortify;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $registerRequest = new RegisterRequest();
        $registerRequest->setContainer(app());
        $registerRequest->setRedirector(app('redirect'));

        if (!$registerRequest->authorize()) {
            abort(403);
        }

        Validator::make($input, $registerRequest->rules(), $registerRequest->messages())->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        // 管理者の場合はメール認証を自動的に完了
        if ($user->role === 'admin') {
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }
}
