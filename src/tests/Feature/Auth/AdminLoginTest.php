<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID: 3-1
     * ログイン認証機能（管理者） - メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * テスト手順: 1. ユーザーを登録する 2. メールアドレス以外のユーザー情報を入力する 3. ログインの処理を行う
     * 期待挙動: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_email_is_required_for_admin_login()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * ID: 3-2
     * ログイン認証機能（管理者） - パスワードが未入力の場合、バリデーションメッセージが表示される
     * テスト手順: 1. ユーザーを登録する 2. パスワード以外のユーザー情報を入力する 3. ログインの処理を行う
     * 期待挙動: 「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_password_is_required_for_admin_login()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * ID: 3-3
     * ログイン認証機能（管理者） - 登録内容と一致しない場合、バリデーションメッセージが表示される
     * テスト手順: 1. ユーザーを登録する 2. 誤ったメールアドレスのユーザー情報を入力する 3. ログインの処理を行う
     * 期待挙動: 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
     */
    public function test_invalid_admin_credentials_show_error_message()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'user_pass',
        ]);

        $response->assertSessionHasErrors([
            'failed' => 'ログイン情報が登録されていません'
        ]);
    }

    /**
     * FN014: ログイン認証機能（管理者）
     * 使用技術: fortify
     */
    public function test_admin_can_login_successfully()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticated();
    }

    /**
     * FN015: バリデーション機能
     * 使用技術: formrequest
     */
    public function test_admin_login_validation_requires_fields()
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * FN016: エラーメッセージ表示機能
     * 1. 未入力の場合
     */
    public function test_shows_error_messages_for_empty_admin_login_fields()
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * FN016: エラーメッセージ表示機能
     * 2. 入力情報が誤っている場合
     */
    public function test_shows_error_for_invalid_admin_login_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'user_pass',
        ]);

        $response->assertSessionHasErrors([
            'failed' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * 管理者ログイン後に管理画面にリダイレクトされることを確認
     */
    public function test_admin_redirects_to_admin_dashboard_after_login()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/attendance/list');
    }
}