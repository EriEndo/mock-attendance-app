<?php

namespace Database\Factories;

use App\Models\RequestBreak;
use App\Models\CorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestBreakFactory extends Factory
{
    protected $model = RequestBreak::class;

    public function definition(): array
    {
        return [
            'correction_request_id' => CorrectionRequest::factory(),
            'break_no' => 1,

            'requested_break_start_at' => '12:15:00',
            'requested_break_end_at' => '13:15:00',
        ];
    }
}
