<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前が未入力の場合に、バリデーションエラーとなることを確認
     *
     * @return void
     */
    public function test_name_is_required()
    {
        // 1. テスト用のデータを用意する（名前だけ空にする）
        $data = [
            'name' => '', // 名前を空にする
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 2. POSTリクエストを /register に送信する
        $response = $this->post('/register', $data);

        // 3. レスポンスを検証する
        //    - 'name' フィールドでバリデーションエラーが発生していることを確認
        //    - エラーメッセージが「お名前を入力してください」であることを確認
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);
    }

    /**
     * メールアドレスが未入力の場合に、バリデーションエラーとなることを確認
     */
    public function test_email_is_required()
    {
        $data = [
            'name' => 'テスト太郎',
            'email' => '', // メールアドレスを空にする
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合に、バリデーションエラーとなることを確認
     */
    public function test_password_is_required()
    {
        $data = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '', // パスワードを空にする
            'password_confirmation' => '',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * パスワードが8文字未満の場合に、バリデーションエラーとなることを確認
     */
    public function test_password_must_be_at_least_8_characters()
    {
        $data = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'pass', // 4文字のパスワード
            'password_confirmation' => 'pass',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
    }

    /**
     * 確認用パスワードが一致しない場合に、バリデーションエラーとなることを確認
     */
    public function test_password_confirmation_does_not_match()
    {
        $data = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456', // 違うパスワード
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません'
        ]);
    }

    /**
     * フォームに正しい内容が入力された場合に、データが正常に保存されることを確認
     */
    public function test_user_can_register_successfully()
    {
        // 1. テスト用のデータを用意する
        $data = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 2. POSTリクエストを /register に送信する
        $response = $this->post('/register', $data);

        // 3. レスポンスを検証する
        //    - 認証されていることを確認
        $this->assertAuthenticated();
        //    - 指定のページにリダイレクトされていることを確認
        $response->assertRedirect('/attendance');

        // 4. データベースを検証する
        //    - 'users' テーブルに、送信したメールアドレスのデータが存在することを確認
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }
}