<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'approved_by',
        'request_date',
        'correction_type',
        'current_time',
        'requested_time',
        'reason',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'correction_type' => 'string', // Enums can often be cast to strings
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}