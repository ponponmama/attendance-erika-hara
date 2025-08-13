<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID: 12-1
     * 勤怠一覧情報取得機能（管理者） - その日になされた全ユーザーの勤怠情報が正確に確認できる
     * テスト手順: 1. 管理者ユーザーにログインする 2. 勤怠一覧画面を開く
     * 期待挙動: その日の全ユーザーの勤怠情報が正確な値になっている
     */
    public function test_displays_all_users_attendance_for_current_date()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user1 = User::factory()->create(['name' => 'ユーザー1']);
        $user2 = User::factory()->create(['name' => 'ユーザー2']);

        // 今日の日付で勤怠データを作成
        $today = Carbon::today()->format('Y-m-d');

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in' => '2024-07-15 09:00:00',
            'clock_out' => '2024-07-15 18:00:00',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => $today,
            'clock_in' => '2024-07-15 08:30:00',
            'clock_out' => null,
        ]);

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        // 全ユーザーの勤怠情報が表示されていることを確認
        $response->assertSee('ユーザー1');
        $response->assertSee('ユーザー2');
        $response->assertSee('09:00');
        $response->assertSee('08:30');
    }

    /**
     * ID: 12-2
     * 勤怠一覧情報取得機能（管理者） - 遷移した際に現在の日付が表示される
     * テスト手順: 1. 管理者ユーザーにログインする 2. 勤怠一覧画面を開く
     * 期待挙動: 勤怠一覧画面にその日の日付が表示されている
     */
    public function test_displays_current_date()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);

        $currentDate = Carbon::today()->format('Y年m月d日');
        $response->assertSee($currentDate);
    }

    /**
     * ID: 12-3
     * 勤怠一覧情報取得機能（管理者） - 「前日」を押下した時に前の日の勤怠情報が表示される
     * テスト手順: 1. 管理者ユーザーにログインする 2. 勤怠一覧画面を開く 3. 「前日」ボタンを押す
     * 期待挙動: 前日の日付の勤怠情報が表示される
     */
    public function test_displays_previous_day_attendance()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $yesterday,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/admin/attendance/list?date={$yesterday}");
        $response->assertStatus(200);

        $yesterdayDisplay = Carbon::yesterday()->format('Y年m月d日');
        $response->assertSee($yesterdayDisplay);
        $response->assertSee('09:00');
    }

    /**
     * ID: 12-4
     * 勤怠一覧情報取得機能（管理者） - 「翌日」を押下した時に次の日の勤怠情報が表示される
     * テスト手順: 1. 管理者ユーザーにログインする 2. 勤怠一覧画面を開く 3. 「翌日」ボタンを押す
     * 期待挙動: 翌日の日付の勤怠情報が表示される
     */
    public function test_displays_next_day_attendance()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $tomorrow,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/admin/attendance/list?date={$tomorrow}");
        $response->assertStatus(200);

        $tomorrowDisplay = Carbon::tomorrow()->format('Y年m月d日');
        $response->assertSee($tomorrowDisplay);
        $response->assertSee('09:00');
    }

    /**
     * 一般ユーザーは管理者画面にアクセスできない
     */
    public function test_prevents_user_access_to_admin_page()
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(302);
    }

    /**
     * 未認証ユーザーは管理者画面にアクセスできない
     */
    public function test_prevents_guest_access_to_admin_page()
    {
        $response = $this->get('/admin/attendance/list');
        $response->assertRedirect('/login');
    }

    /**
     * 勤怠情報修正機能（管理者） - 承認待ちの修正申請が全て表示されている
     */
    public function test_displays_all_pending_correction_requests()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user1 = User::factory()->create(['name' => 'ユーザー1']);
        $user2 = User::factory()->create(['name' => 'ユーザー2']);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => '2024-07-15',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => '2024-07-16',
        ]);

        StampCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'status' => 'pending',
            'reason' => '申請1',
            'correction_type' => 'clock_in',
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => now(),
        ]);

        StampCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'status' => 'pending',
            'reason' => '申請2',
            'correction_type' => 'clock_out',
            'current_time' => '18:00',
            'requested_time' => '19:00',
            'request_date' => now(),
        ]);

        $response = $this->get('/stamp_correction_request/list?status=pending');
        $response->assertStatus(200);
        $response->assertSee('申請1');
        $response->assertSee('申請2');
        $response->assertSee('承認待ち');
    }

    /**
     * 勤怠情報修正機能（管理者） - 承認済みの修正申請が全て表示されている
     */
    public function test_displays_all_approved_correction_requests()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user1 = User::factory()->create(['name' => 'ユーザー1']);
        $user2 = User::factory()->create(['name' => 'ユーザー2']);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => '2024-07-15',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => '2024-07-16',
        ]);

        StampCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'status' => 'approved',
            'reason' => '承認済み申請1',
            'correction_type' => 'clock_in',
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => now(),
        ]);

        StampCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'status' => 'approved',
            'reason' => '承認済み申請2',
            'correction_type' => 'clock_out',
            'current_time' => '18:00',
            'requested_time' => '19:00',
            'request_date' => now(),
        ]);

        $response = $this->get('/stamp_correction_request/list?status=approved');
        $response->assertStatus(200);
        $response->assertSee('承認済み申請1');
        $response->assertSee('承認済み申請2');
        $response->assertSee('承認済み');
    }

    /**
     * 勤怠情報修正機能（管理者） - 修正申請の詳細内容が正しく表示されている
     */
    public function test_displays_correction_request_detail_correctly()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create(['name' => 'テストユーザー']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'reason' => 'テスト修正申請',
            'correction_type' => 'clock_in',
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => now(),
        ]);

        // attendance.detailビューを使用して詳細表示をテスト
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('テストユーザー');
        $response->assertSee('テスト修正申請');
        // 管理者画面では実際の勤怠データが表示されるため、09:00を確認
        $response->assertSee('09:00');
    }

    /**
     * 勤怠情報修正機能（管理者） - 修正申請の承認処理が正しく行われる
     */
    public function test_approves_correction_request_successfully()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'reason' => 'テスト修正申請',
            'correction_type' => 'clock_in',
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => now(),
        ]);

        $response = $this->post("/stamp_correction_request/approve/{$request->id}");
        $response->assertStatus(302);

        // 申請が承認済みに変更されていることを確認
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);

        // 勤怠情報が更新されていることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2024-07-15 08:30:00',
        ]);
    }

    /**
     * 勤怠詳細情報取得・修正機能（管理者） - 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_shows_error_when_admin_clock_in_time_is_after_clock_out_time()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '19:00:00',
            'clock_out' => '18:00:00',
            'memo' => 'テスト修正',
        ]);

        $response->assertSessionHasErrors(['clock_in']);
    }

    /**
     * 勤怠詳細情報取得・修正機能（管理者） - 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_shows_error_when_admin_break_start_time_is_after_clock_out_time()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'break_start_0' => '19:00:00',
            'break_end_0' => '20:00:00',
            'memo' => 'テスト修正',
        ]);

        $response->assertSessionHasErrors(['break_start_0']);
    }

    /**
     * 勤怠詳細情報取得・修正機能（管理者） - 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_shows_error_when_admin_break_end_time_is_after_clock_out_time()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'break_start_0' => '12:00:00',
            'break_end_0' => '19:00:00',
            'memo' => 'テスト修正',
        ]);

        $response->assertSessionHasErrors(['break_end_0']);
    }

    /**
     * 勤怠詳細情報取得・修正機能（管理者） - 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_shows_error_when_admin_reason_is_empty()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'memo' => '',
        ]);

        $response->assertSessionHasErrors(['memo']);
    }
}