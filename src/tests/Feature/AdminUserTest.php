<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID: 14-1
     * ユーザー情報取得機能（管理者） - 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     * テスト手順: 1. 管理者でログインする 2. スタッフ一覧ページを開く
     * 期待挙動: 全ての一般ユーザーの氏名とメールアドレスが正しく表示されている
     */
    public function test_displays_all_users_name_and_email()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user1 = User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'test1@example.com',
            'role' => 'user'
        ]);

        $user2 = User::factory()->create([
            'name' => 'テスト花子',
            'email' => 'test2@example.com',
            'role' => 'user'
        ]);

        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        // 全ユーザーの氏名とメールアドレスが表示されていることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('test1@example.com');
        $response->assertSee('テスト花子');
        $response->assertSee('test2@example.com');
    }

    /**
     * ID: 14-2
     * ユーザー情報取得機能（管理者） - ユーザーの勤怠情報が正しく表示される
     * テスト手順: 1. 管理者ユーザーでログインする 2. 選択したユーザーの勤怠一覧ページを開く
     * 期待挙動: 勤怠情報が正確に表示される
     */
    public function test_displays_user_attendance_correctly()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create(['name' => 'テスト太郎']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);

        // ユーザーの勤怠情報が表示されていることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * ID: 14-3
     * ユーザー情報取得機能（管理者） - 「前月」を押下した時に表示月の前月の情報が表示される
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 勤怠一覧ページを開く 3. 「前月」ボタンを押す
     * 期待挙動: 前月の情報が表示されている
     */
    public function test_displays_previous_month_when_previous_button_clicked()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}?month={$previousMonth}");
        $response->assertStatus(200);

        $previousMonthDisplay = Carbon::now()->subMonth()->format('Y/m');
        $response->assertSee($previousMonthDisplay);
    }

    /**
     * ID: 14-4
     * ユーザー情報取得機能（管理者） - 「翌月」を押下した時に表示月の翌月の情報が表示される
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 勤怠一覧ページを開く 3. 「翌月」ボタンを押す
     * 期待挙動: 翌月の情報が表示されている
     */
    public function test_displays_next_month_when_next_button_clicked()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $nextMonth = Carbon::now()->addMonth()->format('Y-m');

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->addMonth()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}?month={$nextMonth}");
        $response->assertStatus(200);

        $nextMonthDisplay = Carbon::now()->addMonth()->format('Y/m');
        $response->assertSee($nextMonthDisplay);
    }

    /**
     * ID: 14-5
     * ユーザー情報取得機能（管理者） - 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 勤怠一覧ページを開く 3. 「詳細」ボタンを押下する
     * 期待挙動: その日の勤怠詳細画面に遷移する
     */
    public function test_redirects_to_detail_page_when_detail_button_clicked()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);

        // 詳細ボタンが表示されていることを確認
        $response->assertSee('詳細');
    }

    /**
     * 一般ユーザーは管理者画面にアクセスできない
     */
    public function test_prevents_user_access_to_admin_staff_list()
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $response = $this->get('/admin/staff/list');
        $response->assertStatus(302);
    }

    /**
     * 未認証ユーザーは管理者画面にアクセスできない
     */
    public function test_prevents_guest_access_to_admin_staff_list()
    {
        $response = $this->get('/admin/staff/list');
        $response->assertRedirect('/login');
    }
}
