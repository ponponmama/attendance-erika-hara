<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合、バリデーションエラーとなることを確認
     */
    public function test_email_is_required_for_login()
    {
        // 1. テスト用のデータを用意する（メールアドレスだけ空にする）
        $data = [
            'email' => '',
            'password' => 'password',
        ];

        // 2. POSTリクエストを /login に送信する
        $response = $this->post('/login', $data);

        // 3. レスポンスを検証する
        //    - 'email' フィールドでバリデーションエラーが発生していることを確認
        //    - エラーメッセージが「メールアドレスを入力してください」であることを確認
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションエラーとなることを確認
     */
    public function test_password_is_required_for_login()
    {
        // 1. テスト用のユーザーを作成
        $user = User::factory()->create();

        // 2. テスト用のデータを用意する（パスワードだけ空にする）
        $data = [
            'email' => $user->email,
            'password' => '',
        ];

        // 3. POSTリクエストを /login に送信する
        $response = $this->post('/login', $data);

        // 4. レスポンスを検証する
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 登録情報と一致しない場合、バリデーションエラーとなることを確認
     */
    public function test_user_cannot_login_with_incorrect_credentials()
    {
        // 1. テスト用のユーザーを作成
        $user = User::factory()->create([
            'password' => bcrypt('i-am-correct-password'),
        ]);

        // 2. わざと間違ったパスワードでログインを試みる
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'i-am-wrong-password',
        ]);

        // 3. レスポンスを検証する
        //    - 'email' フィールドでエラーが発生していることを確認 (Fortifyはemailキーでエラーを返す)
        //    - エラーメッセージが「ログイン情報が登録されていません」であることを確認
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
        //    - ログイン状態になっていないこと(ゲスト状態であること)を確認
        $this->assertGuest();
    }

    /**
     * ユーザーが正常にログインできることを確認
     */
    public function test_user_can_login_successfully()
    {
        // 1. テスト用のユーザーを作成
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // 2. 正しい情報でログインを試みる
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // 3. レスポンスを検証する
        //    - 指定したユーザーとして認証されていることを確認
        $this->assertAuthenticatedAs($user);
        //    - 打刻ページにリダイレクトされていることを確認
        $response->assertRedirect('/attendance');
    }
}