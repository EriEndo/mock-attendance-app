<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }

    public function test_current_datetime_is_displayed_in_ui_format(): void
    {
        $user = $this->createUser();

        $fixedNow = \Carbon\Carbon::create(2026, 4, 14, 9, 30, 0);
        \Carbon\Carbon::setTestNow($fixedNow);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('2026-04-14 09:30:00');
    }
}
