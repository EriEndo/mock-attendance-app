<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class UserLoginTest extends TestCase
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

    public function test_user_login_requires_email(): void
    {
        $this->createUser();

        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_user_login_requires_password(): void
    {
        $this->createUser();

        $response = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_user_login_fails_with_invalid_credentials(): void
    {
        $this->createUser();

        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors([
            'login_error' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest();
    }
}
