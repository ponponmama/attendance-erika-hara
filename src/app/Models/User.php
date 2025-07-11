<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Mail\VerifyEmailCustom;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    public function sendEmailVerificationNotification()
    {
        // 管理者の場合はメール認証をスキップ
        if ($this->role === 'admin') {
            return;
        }

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())]
        );

        Mail::to($this->email)->send(new VerifyEmailCustom($this, $verificationUrl));
    }
}
