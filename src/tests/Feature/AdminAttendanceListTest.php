<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できることを確認
     * @return void
     */
    public function test_displays_all_users_attendance_records()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
        ]);
        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
        ]);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 複数のユーザーの勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => '2024-06-25',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        // 管理者の勤怠一覧画面にアクセス
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        // 全ユーザーの勤怠記録が表示されていることを確認
        $response->assertSee('ユーザー1');
        $response->assertSee('ユーザー2');
        $response->assertSee('09:00');
        $response->assertSee('08:30');
    }

    /**
     * 遷移した際に現在の日付が表示されることを確認
     * @return void
     */
    public function test_displays_current_date()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 管理者の勤怠一覧画面にアクセス
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        // 現在の日付が表示されていることを確認（Y/m/d形式）
        $response->assertSee('2024/06/25');
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示されることを確認
     * @return void
     */
    public function test_displays_previous_day_when_clicked()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 一般ユーザーを作成
        $user = User::factory()->create();

        // 前日の勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 前日の勤怠一覧画面にアクセス
        $response = $this->get('/admin/attendance/list?date=2024-06-24');
        $response->assertStatus(200);

        // 前日の情報が表示されていることを確認
        $response->assertSee('2024/06/24');
        $response->assertSee('09:00');
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示されることを確認
     * @return void
     */
    public function test_displays_next_day_when_clicked()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 一般ユーザーを作成
        $user = User::factory()->create();

        // 翌日の勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-26',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 翌日の勤怠一覧画面にアクセス
        $response = $this->get('/admin/attendance/list?date=2024-06-26');
        $response->assertStatus(200);

        // 翌日の情報が表示されていることを確認
        $response->assertSee('2024/06/26');
        $response->assertSee('09:00');
    }
}
