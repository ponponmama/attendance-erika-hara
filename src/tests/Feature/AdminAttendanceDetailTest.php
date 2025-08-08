<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID: 13-1
     * 勤怠詳細情報取得・修正機能（管理者） - 勤怠詳細画面に表示されるデータが選択したものになっている
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 勤怠詳細ページを開く
     * 期待挙動: 詳細画面の内容が選択した情報と一致する
     */
    public function test_displays_selected_attendance_data()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create(['name' => 'テスト太郎']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * ID: 13-2
     * 勤怠詳細情報取得・修正機能（管理者） - 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 勤怠詳細ページを開く 3. 出勤時間を退勤時間より後に設定する 4. 保存処理をする
     * 期待挙動: 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_shows_error_when_clock_in_time_is_after_clock_out_time()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'user_id' => $admin->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'memo' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * ID: 13-3
     * 勤怠詳細情報取得・修正機能（管理者） - 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 勤怠詳細ページを開く 3. 休憩開始時間を退勤時間より後に設定する 4. 保存処理をする
     * 期待挙動: 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_shows_error_when_break_start_time_is_after_clock_out_time()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'user_id' => $admin->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start_0' => '19:00',
            'break_end_0' => '20:00',
            'memo' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'break_start_0' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * ID: 13-4
     * 勤怠詳細情報取得・修正機能（管理者） - 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 勤怠詳細ページを開く 3. 休憩終了時間を退勤時間より後に設定する 4. 保存処理をする
     * 期待挙動: 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_shows_error_when_break_end_time_is_after_clock_out_time()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'user_id' => $admin->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start_0' => '12:00',
            'break_end_0' => '19:00',
            'memo' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'break_end_0' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * ID: 13-5
     * 勤怠詳細情報取得・修正機能（管理者） - 備考欄が未入力の場合のエラーメッセージが表示される
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 勤怠詳細ページを開く 3. 備考欄を未入力のまま保存処理をする
     * 期待挙動: 「備考を記入してください」というバリデーションメッセージが表示される
     */
    public function test_shows_error_when_memo_is_empty()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'user_id' => $admin->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'memo' => '',
        ]);

        $response->assertSessionHasErrors([
            'memo' => '備考を記入してください'
        ]);
    }

    /**
     * 管理者は直接修正が実行される
     */
    public function test_admin_can_directly_correct_attendance()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'user_id' => $admin->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '08:30',
            'clock_out' => '19:00',
            'memo' => '管理者による直接修正',
        ]);

        $response->assertStatus(302);

        // 勤怠データが直接更新されていることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'memo' => '管理者による直接修正',
        ]);

        // 更新された勤怠データを再取得して時刻を確認
        $updatedAttendance = Attendance::find($attendance->id);
        $this->assertEquals('2024-07-15 08:30:00', $updatedAttendance->clock_in);
        $this->assertEquals('2024-07-15 19:00:00', $updatedAttendance->clock_out);
    }

    /**
     * 一般ユーザーは管理者の勤怠詳細にアクセスできない
     */
    public function test_prevents_user_access_to_admin_attendance_detail()
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $admin = User::factory()->create(['role' => 'admin']);
        $attendance = Attendance::factory()->create([
            'user_id' => $admin->id,
            'date' => '2024-07-15',
        ]);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(403);
    }
}
