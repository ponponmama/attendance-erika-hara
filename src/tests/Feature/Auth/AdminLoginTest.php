<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required_for_admin_login()
    {
        // 1. 管理者ユーザーを登録する
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. メールアドレス以外のユーザー情報を入力する
        $data = [
            'email' => '', // メールアドレスを空にする
            'password' => 'password123',
            'admin_login' => '1', // 管理者ログインフラグ
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/login', $data);

        // 4. レスポンスを検証する
        //    - 'email' フィールドでバリデーションエラーが発生していることを確認
        //    - エラーメッセージが「メールアドレスを入力してください」であることを確認
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required_for_admin_login()
    {
        // 1. 管理者ユーザーを登録する
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. パスワード以外のユーザー情報を入力する
        $data = [
            'email' => 'admin@example.com',
            'password' => '', // パスワードを空にする
            'admin_login' => '1', // 管理者ログインフラグ
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/login', $data);

        // 4. レスポンスを検証する
        //    - 'password' フィールドでバリデーションエラーが発生していることを確認
        //    - エラーメッセージが「パスワードを入力してください」であることを確認
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_admin_cannot_login_with_incorrect_credentials()
    {
        // 1. 管理者ユーザーを登録する
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        $data = [
            'email' => 'wrong@example.com', // 存在しないメールアドレス
            'password' => 'password123',
            'admin_login' => '1', // 管理者ログインフラグ
        ];

        // 3. ログインの処理を行う
        $response = $this->post('/login', $data);

        // 4. レスポンスを検証する
        //    - 'failed' フィールドでエラーが発生していることを確認
        //    - エラーメッセージが「ログイン情報が登録されていません」であることを確認
        $response->assertSessionHasErrors([
            'failed' => 'ログイン情報が登録されていません'
        ]);
        //    - ログイン状態になっていないこと(ゲスト状態であること)を確認
        $this->assertGuest();
    }

    /**
     * 管理者が正常にログインできることを確認
     */
    public function test_admin_can_login_successfully()
    {
        // 1. 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. 正しい情報でログインを試みる
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'admin_login' => '1', // 管理者ログインフラグ
        ]);

        // 3. レスポンスを検証する
        //    - 指定した管理者として認証されていることを確認
        $this->assertAuthenticatedAs($admin);
        //    - 管理者用の勤怠一覧ページにリダイレクトされていることを確認
        $response->assertRedirect('/admin/attendance/list');
    }
}
