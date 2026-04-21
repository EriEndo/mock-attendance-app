<?php

namespace Database\Factories;

use App\Models\CorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrectionRequestFactory extends Factory
{
    protected $model = CorrectionRequest::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'requested_by' => User::factory(),
            'request_type' => 'user_request',

            'status' => 'pending',

            'requested_clock_in_at' => '09:30:00',
            'requested_clock_out_at' => '18:30:00',

            'note' => '修正申請テスト',

            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    // 👇 状態別
    public function pending()
    {
        return $this->state([
            'status' => 'pending',
        ]);
    }

    public function approved()
    {
        return $this->state([
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function adminDirect()
    {
        return $this->state([
            'request_type' => 'admin_direct',
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}
