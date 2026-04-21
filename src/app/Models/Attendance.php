<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',

    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function getFormattedClockInAttribute()
    {
        return $this->clock_in_at?->format('H:i') ?? '';
    }

    public function getFormattedClockOutAttribute()
    {
        return $this->clock_out_at?->format('H:i') ?? '';
    }

    public function getBreakTotalSecondsAttribute()
    {
        return $this->breakTimes->sum(function ($break) {
            if ($break->break_start_at && $break->break_end_at) {
                return $break->break_start_at->diffInSeconds($break->break_end_at);
            }
            return 0;
        });
    }

    public function getWorkTotalSecondsAttribute()
    {
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return 0;
        }

        return $this->clock_in_at->diffInSeconds($this->clock_out_at)
            - $this->break_total_seconds;
    }
}
