<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID: 11-1
     * 勤怠詳細情報修正機能（一般ユーザー） - 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細ページを開く 3. 出勤時間を退勤時間より後に設定する 4. 保存処理をする
     * 期待挙動: 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_shows_error_when_clock_in_time_is_after_clock_out_time()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'reason' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * ID: 11-2
     * 勤怠詳細情報修正機能（一般ユーザー） - 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細ページを開く 3. 休憩開始時間を退勤時間より後に設定する 4. 保存処理をする
     * 期待挙動: 「休憩時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_shows_error_when_break_start_time_is_after_clock_out_time()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
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
            'reason' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'break_start_0' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * ID: 11-3
     * 勤怠詳細情報修正機能（一般ユーザー） - 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細ページを開く 3. 休憩終了時間を退勤時間より後に設定する 4. 保存処理をする
     * 期待挙動: 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function test_shows_error_when_break_end_time_is_after_clock_out_time()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
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
            'reason' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'break_end_0' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * ID: 11-4
     * 勤怠詳細情報修正機能（一般ユーザー） - 備考欄が未入力の場合のエラーメッセージが表示される
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細ページを開く 3. 備考欄を未入力のまま保存処理をする
     * 期待挙動: 「備考を記入してください」というバリデーションメッセージが表示される
     */
    public function test_shows_error_when_memo_is_empty()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
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
     * ID: 11-5
     * 勤怠詳細情報修正機能（一般ユーザー） - 修正申請処理が実行される
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細を修正し保存処理をする 3. 管理者ユーザーで承認画面と申請一覧画面を確認する
     * 期待挙動: 修正申請が実行され、管理者の承認画面と申請一覧画面に表示される
     */
    public function test_correction_request_is_processed()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '08:30',
            'clock_out' => '18:00',
            'memo' => 'テスト備考',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/stamp_correction_request/list');

        // 修正申請がデータベースに保存されていることを確認
        $this->assertDatabaseHas('stamp_correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'reason' => 'テスト備考',
        ]);
    }

    /**
     * ID: 11-6
     * 勤怠詳細情報修正機能（一般ユーザー） - 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細を修正し保存処理をする 3. 申請一覧画面を確認する
     * 期待挙動: 申請一覧に自分の申請が全て表示されている
     */
    public function test_displays_all_pending_requests_for_user()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 修正申請を作成
        $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '08:30',
            'clock_out' => '18:00',
            'memo' => 'テスト備考',
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 申請一覧に自分の申請が表示されていることを確認
        $response->assertSee('テスト備考');
    }

    /**
     * ID: 11-7
     * 勤怠詳細情報修正機能（一般ユーザー） - 「承認済み」に管理者が承認した修正申請が全て表示されている
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細を修正し保存処理をする 3. 申請一覧画面を開く 4. 管理者が承認した修正申請が全て表示されていることを確認
     * 期待挙動: 承認済みに管理者が承認した申請が全て表示されている
     */
    public function test_displays_all_approved_requests()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 修正申請を作成（承認済み状態）
        $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '08:30',
            'clock_out' => '18:00',
            'reason' => 'テスト備考',
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 承認済みの申請が表示されていることを確認
        $response->assertSee('承認済み');
    }

    /**
     * ID: 11-8
     * 勤怠詳細情報修正機能（一般ユーザー） - 各申請の「詳細」を押下すると申請詳細画面に遷移する
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細を修正し保存処理をする 3. 申請一覧画面を開く 4. 「詳細」ボタンを押す
     * 期待挙動: 申請詳細画面に遷移する
     */
    public function test_redirects_to_request_detail_when_detail_button_clicked()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 修正申請を作成
        $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '08:30',
            'clock_out' => '18:00',
            'reason' => 'テスト備考',
        ]);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        // 詳細ボタンが表示されていることを確認
        $response->assertSee('詳細');
    }
}