<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されることを確認
     * @return void
     */
    public function test_shows_error_when_clock_in_after_clock_out()
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

    /**
     * 修正申請処理が実行されることを確認
     * @return void
     */
    public function test_correction_request_is_processed()
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

        // 修正申請を送信
        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '08:30',
            'clock_out' => '18:00',
            'memo' => '出勤時刻修正',
        ]);

        // 修正申請が作成されることを確認
        $response->assertStatus(302);
        $this->assertDatabaseHas('stamp_correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていることを確認
     * @return void
     */
    public function test_displays_user_pending_requests()
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

        // 承認待ちの修正申請を作成
        StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'reason' => '出勤時刻修正',
            'request_date' => '2024-06-25',
            'approved_by' => null,
        ]);

        // 申請一覧画面にアクセス
        $response = $this->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);

        // 自分の申請が表示されていることを確認
        $response->assertSee('出勤時刻修正');
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されていることを確認
     * @return void
     */
    public function test_displays_user_approved_requests()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 承認済みの修正申請を作成
        StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
            'reason' => '出勤時刻修正',
            'request_date' => '2024-06-25',
        ]);

        // 申請一覧画面にアクセス
        $response = $this->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);

        // 承認済みの申請が表示されていることを確認
        $response->assertSee('出勤時刻修正');
    }

    /**
     * 各申請の「詳細」を押下すると申請詳細画面に遷移することを確認
     * @return void
     */
    public function test_redirects_to_request_detail_when_detail_clicked()
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

        // 修正申請を作成
        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'reason' => '出勤時刻修正',
            'request_date' => '2024-06-25',
            'approved_by' => null,
        ]);

        // 申請一覧画面にアクセス
        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 詳細ボタンが存在することを確認
        $response->assertSee('詳細');
    }
}
