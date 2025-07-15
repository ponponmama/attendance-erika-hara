<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっていることを確認
     * @return void
     */
    public function test_displays_user_name_in_detail()
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
        ]);
        $this->actingAs($user);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 名前がログインユーザーの名前になっていることを確認
        $response->assertSee('テスト太郎');
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっていることを確認
     * @return void
     */
    public function test_displays_selected_date_in_detail()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 日付が選択した日付になっていることを確認
        $response->assertSee('2024年');
        $response->assertSee('6月25日');
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致していることを確認
     * @return void
     */
    public function test_displays_correct_clock_in_out_times()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 出勤・退勤時間が正しく表示されていることを確認
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致していることを確認
     * @return void
     */
    public function test_displays_correct_break_times()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 休憩記録を作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 休憩時間が正しく表示されていることを確認
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}