<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっていることを確認
     * @return void
     */
    public function test_displays_correct_attendance_data()
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

        // 勤怠記録を作成（修正申請なし）
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'memo' => 'テスト備考',
        ]);

        // 休憩記録を作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // 管理者の勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 詳細画面の内容が選択した情報と一致することを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('2024年');
        $response->assertSee('6月25日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('テスト備考');
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されることを確認
     * @return void
     */
    public function test_shows_error_when_clock_in_after_clock_out()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create();

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 管理者の勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 出勤時間を退勤時間より後に設定して修正申請を送信
        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'memo' => 'テスト備考',
        ]);

        // バリデーションエラーが発生することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['clock_in']);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示されることを確認
     * @return void
     */
    public function test_shows_error_when_break_start_after_clock_out()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create();

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

        // 管理者の勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 休憩開始時間を退勤時間より後に設定して修正申請を送信
        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start_0' => '19:00', // 退勤時間より後の時間
            'break_end_0' => '20:00',
            'memo' => 'テスト備考',
        ]);

        // バリデーションエラーが発生することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['break_start_0']);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示されることを確認
     * @return void
     */
    public function test_shows_error_when_break_end_after_clock_out()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create();

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

        // 管理者の勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 休憩終了時間を退勤時間より後に設定して修正申請を送信
        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start_0' => '12:00',
            'break_end_0' => '19:00', // 退勤時間より後の時間
            'memo' => 'テスト備考',
        ]);

        // バリデーションエラーが発生することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['break_end_0']);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示されることを確認
     * @return void
     */
    public function test_shows_error_when_memo_is_empty()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create();

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 管理者の勤怠詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 備考欄を未入力のまま修正申請を送信
        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'memo' => '',
        ]);

        // バリデーションエラーが発生することを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['memo']);
    }
}
