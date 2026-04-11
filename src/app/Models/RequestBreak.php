<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'break_no',
        'requested_break_start_at',
        'requested_break_end_at',
    ];

    protected $casts = [
        'requested_break_start_at' => 'datetime',
        'requested_break_end_at' => 'datetime',
    ];

    public function correctionRequest()
    {
        return $this->belongsTo(CorrectionRequest::class);
    }
}
