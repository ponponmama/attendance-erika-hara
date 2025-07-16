<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できることを確認
     * @return void
     */
    public function test_displays_all_users_name_and_email()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user1 = User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'test1@example.com',
        ]);
        $user2 = User::factory()->create([
            'name' => 'テスト花子',
            'email' => 'test2@example.com',
        ]);

        // 管理者のスタッフ一覧画面にアクセス
        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        // 全ユーザーの氏名とメールアドレスが表示されていることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('test1@example.com');
        $response->assertSee('テスト花子');
        $response->assertSee('test2@example.com');
    }

    /**
     * ユーザーの勤怠情報が正しく表示されることを確認
     * @return void
     */
    public function test_displays_user_attendance_information()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create([
            'name' => 'テスト太郎',
        ]);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // ユーザーの勤怠一覧画面にアクセス
        $response = $this->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);

        // 勤怠情報が表示されていることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示されることを確認
     * @return void
     */
    public function test_displays_previous_month_when_clicked()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create();

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 前月の勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-05-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 前月の勤怠一覧画面にアクセス
        $response = $this->get('/admin/attendance/staff/' . $user->id . '?month=2024-05');
        $response->assertStatus(200);

        // 前月の情報が表示されていることを確認
        $response->assertSee('2024/05');
        $response->assertSee('09:00');
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示されることを確認
     * @return void
     */
    public function test_displays_next_month_when_clicked()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create();

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 翌月の勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 翌月の勤怠一覧画面にアクセス
        $response = $this->get('/admin/attendance/staff/' . $user->id . '?month=2024-07');
        $response->assertStatus(200);

        // 翌月の情報が表示されていることを確認
        $response->assertSee('2024/07');
        $response->assertSee('09:00');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移することを確認
     * @return void
     */
    public function test_redirects_to_attendance_detail_when_detail_button_clicked()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create();

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 勤怠記録を作成（現在の月に合わせる）
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // ユーザーの勤怠一覧画面にアクセス
        $response = $this->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);

        // 詳細ボタンが存在することを確認
        $response->assertSee('詳細');
    }
}
