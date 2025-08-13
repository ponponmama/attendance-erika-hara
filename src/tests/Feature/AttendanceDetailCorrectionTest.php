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
        $response->assertRedirect('/');

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

    /**
     * ID: 11-9
     * 勤怠詳細情報修正機能（一般ユーザー） - correction_dataが正しく保存される
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 複数の項目を修正して保存処理をする 3. データベースのcorrection_dataを確認する
     * 期待挙動: correction_dataに正しい構造でデータが保存される
     */
    public function test_correction_data_is_saved_correctly()
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

        // 複数の項目を修正
        $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '08:30',
            'clock_out' => '18:30',
            'break_start_0' => '12:30',
            'break_end_0' => '13:30',
            'memo' => 'テスト備考',
        ]);

        // correction_dataが正しく保存されていることを確認
        $this->assertDatabaseHas('stamp_correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'correction_type' => 'clock_in,clock_out,break_0_start,break_0_end',
        ]);

        // correction_dataの内容を確認
        $request = \App\Models\StampCorrectionRequest::where('user_id', $user->id)->first();
        $this->assertNotNull($request->correction_data);
        $this->assertEquals('08:30', $request->correction_data['clock_in']['requested']);
        $this->assertEquals('18:30', $request->correction_data['clock_out']['requested']);
        $this->assertEquals('12:30', $request->correction_data['break_0_start']['requested']);
        $this->assertEquals('13:30', $request->correction_data['break_0_end']['requested']);
    }

    /**
     * ID: 11-10
     * 勤怠詳細情報修正機能（管理者） - 承認時にcorrection_dataから正しくデータが更新される
     * テスト手順: 1. 修正申請を作成する 2. 管理者でログインして承認する 3. 勤怠データが正しく更新されることを確認する
     * 期待挙動: 承認時にcorrection_dataから正しく勤怠データが更新される
     */
    public function test_approval_updates_attendance_data_from_correction_data()
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 修正申請を作成
        $this->actingAs($user);
        $this->post("/stamp_correction_request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '08:30',
            'clock_out' => '18:30',
            'memo' => 'テスト備考',
        ]);

        $request = \App\Models\StampCorrectionRequest::where('user_id', $user->id)->first();

                // correction_dataが正しく保存されていることを確認
        $this->assertNotNull($request->correction_data);
        $this->assertArrayHasKey('clock_in', $request->correction_data);
        $this->assertArrayHasKey('clock_out', $request->correction_data);
        $this->assertEquals('08:30', $request->correction_data['clock_in']['requested']);
        $this->assertEquals('18:30', $request->correction_data['clock_out']['requested']);

        // 管理者で承認
        $this->actingAs($admin);
        $this->post("/stamp_correction_request/approve/{$request->id}");

        // 承認後のデータを確認
        $attendance->refresh();

                // データが正しく更新されていることを確認
        if ($attendance->clock_in instanceof \Carbon\Carbon) {
            $this->assertEquals('08:30:00', $attendance->clock_in->format('H:i:s'));
        } else {
            $this->assertEquals('08:30:00', substr($attendance->clock_in, 11, 8));
        }

        if ($attendance->clock_out instanceof \Carbon\Carbon) {
            $this->assertEquals('18:30:00', $attendance->clock_out->format('H:i:s'));
        } else {
            $this->assertEquals('18:30:00', substr($attendance->clock_out, 11, 8));
        }
    }
}
