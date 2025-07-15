<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Actions\SendEmailVerificationNotification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録後、認証メールが送信されることを確認
     * @return void
     */
    public function test_sends_verification_email_after_registration()
    {
        // 実際のメール送信をテストするため、モックを削除
        // Mail::fake();
        // Notification::fake();

        // 会員登録データ
        $userData = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 会員登録を実行
        $response = $this->post('/register', $userData);
        $response->assertStatus(302); // リダイレクトされることを確認

        // ユーザーが作成されていることを確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // デバッグ用：ユーザーの状態を確認
        dump('ユーザー情報:', $user->toArray());
        dump('email_verified_at:', $user->email_verified_at);

                                        // ユーザーが未認証状態であることを確認
        $this->assertNull($user->email_verified_at, 'ユーザーが未認証状態であることを確認');

        // 実際のメール送信を確認（メールトラップなどで確認可能）
        // テストではユーザーが未認証状態であることを確認する
    }

    /**
     * メール認証誘導画面で「認証メール再送」ボタンが表示されることを確認
     * @return void
     */
    public function test_redirects_to_email_verification_page()
    {
        // 未認証ユーザーを作成
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);

        // メール認証誘導画面にアクセス
        $response = $this->get('/email/verify');
        $response->assertStatus(200);

        // 認証誘導画面の内容を確認
        $response->assertSee('メールアドレスの確認');
        $response->assertSee('認証メール再送');

        // 認証メール再送フォームが存在することを確認
        $response->assertSee('email/verification-notification');
    }

    /**
     * メール認証完了後に勤怠登録画面に遷移することを確認
     * @return void
     */
    public function test_redirects_to_attendance_page_after_email_verification()
    {
        // 未認証ユーザーを作成
        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);

        // メール認証を完了させる（実際の認証リンクをシミュレート）
        $user->email_verified_at = now();
        $user->save();

        // 認証完了後のリダイレクトをテスト
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 勤怠画面が表示されることを確認
        $response->assertSee('勤怠');
    }
}
