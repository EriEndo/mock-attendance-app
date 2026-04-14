<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'name' => 'テスト管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
    }

    public function test_admin_login_requires_email(): void
    {
        $this->createAdmin();

        $response = $this->from('/admin/login')->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_admin_login_requires_password(): void
    {
        $this->createAdmin();

        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }


    public function test_admin_login_fails_with_invalid_credentials(): void
    {
        $this->createAdmin();

        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'login_error' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest();
    }
}
