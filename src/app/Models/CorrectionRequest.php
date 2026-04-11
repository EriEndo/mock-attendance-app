<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
    'attendance_id',
    'requested_by',
    'request_type',
    'status',
    'requested_clock_in_at',
    'requested_clock_out_at',
    'note',
    ];

    protected $casts = [
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestBreaks()
    {
        return $this->hasMany(RequestBreak::class);
    }

    public function getStatusLabelAttribute()
{
    return match ($this->status) {
        'pending' => '承認待ち',
        'approved' => '承認済み',
    };
}
}
