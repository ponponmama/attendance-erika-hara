<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID: 2-1
     * ログイン認証機能（一般ユーザー） - メールアドレスが未入力の場合、バリデーションメッセージが表示される
     * テスト手順: 1. ユーザーを登録する 2. メールアドレス以外のユーザー情報を入力する 3. ログインの処理を行う
     * 期待挙動: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_email_is_required_for_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
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
     * ID: 2-2
     * ログイン認証機能（一般ユーザー） - パスワードが未入力の場合、バリデーションメッセージが表示される
     * テスト手順: 1. ユーザーを登録する 2. パスワード以外のユーザー情報を入力する 3. ログインの処理を行う
     * 期待挙動: 「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_password_is_required_for_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * ID: 2-3
     * ログイン認証機能（一般ユーザー） - 登録内容と一致しない場合、バリデーションメッセージが表示される
     * テスト手順: 1. ユーザーを登録する 2. 誤ったメールアドレスのユーザー情報を入力する 3. ログインの処理を行う
     * 期待挙動: 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
     */
    public function test_invalid_credentials_show_error_message()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors([
            'failed' => 'ログイン情報が登録されていません'
        ]);
    }

    /**
     * FN006: ログイン認証機能（一般ユーザー）
     * 使用技術: fortify
     */
    public function test_user_can_login_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticated();
    }

    /**
     * FN007: 入力フォーム
     * 必要な情報: メールアドレス、パスワード
     */
    public function test_login_form_has_required_fields()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('メールアドレス');
        $response->assertSee('パスワード');
    }

    /**
     * FN008: バリデーション機能
     * 使用技術: formrequest
     */
    public function test_validation_requires_email_and_password()
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * FN009: エラーメッセージ表示
     * 1. 未入力の場合
     */
    public function test_shows_error_messages_for_empty_login_fields()
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * FN009: エラーメッセージ表示
     * 2. 入力情報が誤っている場合
     */
    public function test_shows_error_for_invalid_login_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors([
            'failed' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * FN010: ユーザー認証動線
     * ログイン画面から会員登録画面に遷移できる
     */
    public function test_can_navigate_from_login_to_register()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('会員登録');
    }

    /**
     * FN011: メールを用いた認証機能
     * 1. 新規会員登録にメール認証を行う
     */
    public function test_email_verification_is_required_for_new_registration()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
    }

    /**
     * FN011: メールを用いた認証機能
     * 2. 新規会員登録後にメール認証をしないでログインを試みた場合はメール認証誘導画面へ遷移
     */
    public function test_unverified_user_is_redirected_to_verification_notice()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertRedirect('/email/verify');
    }

    /**
     * FN012: 認証メール再送機能
     * メール認証誘導画面で「認証メール再送」ボタンをクリックすると認証メールを再送信される
     */
    public function test_can_resend_verification_email()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertStatus(302);
        $response->assertSessionHas('status', 'verification-link-sent');
    }
}