<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * FN013: ログアウト機能（一般ユーザー）
     * 使用技術: fortify
     * ヘッダーのボタンから正常にログアウトを行える
     */
    public function test_user_can_logout_successfully()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertStatus(302);
        $this->assertGuest();
    }

    /**
     * FN017: ログアウト機能（管理者）
     * 使用技術: fortify
     * ヘッダーのボタンから正常にログアウトを行える
     */
    public function test_admin_can_logout_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->post('/logout');

        $response->assertStatus(302);
        $this->assertGuest();
    }

    /**
     * ログアウト後にログインページにリダイレクトされることを確認
     */
    public function test_redirects_to_login_page_after_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
    }
}
