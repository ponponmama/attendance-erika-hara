<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'memo',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * 出勤時間をフォーマットして取得
     */
    public function getClockInFormattedAttribute()
    {
        return $this->clock_in ? Carbon::parse($this->clock_in)->format('H:i') : '';
    }

    /**
     * 退勤時間をフォーマットして取得
     */
    public function getClockOutFormattedAttribute()
    {
        return $this->clock_out ? Carbon::parse($this->clock_out)->format('H:i') : '';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }
}
