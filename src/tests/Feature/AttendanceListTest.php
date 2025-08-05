<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されていることを確認
     * @return void
     */
    public function test_displays_all_user_attendance_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 複数の勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-24',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        // 他のユーザーの勤怠記録を作成（表示されないことを確認）
        $otherUser = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2024-06-25',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 自分の勤怠記録が表示されていることを確認
        $response->assertSee('06/25');
        $response->assertSee('06/24');
        $response->assertSee('09:00');
        $response->assertSee('08:30');

        // 他のユーザーの勤怠記録が表示されていないことを確認
        $response->assertDontSee('10:00');
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示されることを確認
     * @return void
     */
    public function test_displays_current_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 現在の月が表示されていることを確認
        $response->assertSee('2024/06');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示されることを確認
     * @return void
     */
    public function test_displays_previous_month_when_clicked()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

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
        $response = $this->get('/attendance/list?month=2024-05');
        $response->assertStatus(200);

        // 前月の情報が表示されていることを確認
        $response->assertSee('2024/05');
        $response->assertSee('05/25');
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示されることを確認
     * @return void
     */
    public function test_displays_next_month_when_clicked()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

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
        $response = $this->get('/attendance/list?month=2024-07');
        $response->assertStatus(200);

        // 翌月の情報が表示されていることを確認
        $response->assertSee('2024/07');
        $response->assertSee('07/25');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移することを確認
     * @return void
     */
    public function test_redirects_to_attendance_detail_when_detail_clicked()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // テスト時間を固定（現在の月に合わせる）
        $testTime = Carbon::create(2025, 8, 5, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 勤怠記録を作成（現在の月のデータ）
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-08-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 詳細リンクが表示されていることを確認
        $response->assertSee('詳細');

        // 詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
    }
}