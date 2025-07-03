<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示されることを確認
     *
     * @return void
     */
    public function test_displays_error_when_clock_in_is_after_clock_out()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 3. 出勤時間を退勤時間より後に設定する
        $updateData = [
            'clock_in' => '19:00',  // 退勤時間より後
            'clock_out' => '18:00',
            'memo' => 'テストメモ',
        ];

        // 4. 保存処理をする
        $response = $this->post("/attendance/update/{$attendance->id}", $updateData);

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['clock_in']);
        $response->assertSessionHasErrors(['clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);

        // データベースが更新されていないことを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    /**
     * 正常な時間設定で勤怠情報が更新されることを確認
     *
     * @return void
     */
    public function test_updates_attendance_with_valid_times()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $updateData = [
            'clock_in' => '08:30',
            'clock_out' => '17:30',
            'memo' => '更新されたメモ',
        ];

        $response = $this->post("/attendance/update/{$attendance->id}", $updateData);

        $response->assertRedirect("/attendance/{$attendance->id}");
        $response->assertSessionHas('success', '勤怠情報を更新しました');

        // データベースが正しく更新されていることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'memo' => '更新されたメモ',
        ]);
    }

    /**
     * 出勤時間と退勤時間が同じ場合、エラーメッセージが表示されることを確認
     *
     * @return void
     */
    public function test_displays_error_when_clock_in_equals_clock_out()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $updateData = [
            'clock_in' => '18:00',  // 退勤時間と同じ
            'clock_out' => '18:00',
            'memo' => 'テストメモ',
        ];

        $response = $this->post("/attendance/update/{$attendance->id}", $updateData);

        $response->assertSessionHasErrors(['clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 他のユーザーの勤怠情報を更新しようとした場合、404エラーになることを確認
     *
     * @return void
     */
    public function test_cannot_update_other_users_attendance()
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();

        $this->actingAs($user1);

        $attendance = Attendance::factory()->create([
            'user_id' => $user2->id,  // 別のユーザーの勤怠
            'date' => Carbon::today(),
        ]);

        $updateData = [
            'clock_in' => '08:30',
            'clock_out' => '17:30',
        ];

        $response = $this->post("/attendance/update/{$attendance->id}", $updateData);
        $response->assertStatus(404);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示されることを確認
     *
     * @return void
     */
    public function test_displays_error_when_break_end_is_after_clock_out()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 3. 休憩終了時間を退勤時間より後に設定する
        $updateData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start_1' => '12:00',
            'break_end_1' => '19:00',  // 退勤時間より後
            'memo' => 'テストメモ',
        ];

        // 4. 保存処理をする
        $response = $this->post("/attendance/update/{$attendance->id}", $updateData);

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['break_start_1']);
        $response->assertSessionHasErrors(['break_start_1' => '休憩時間が勤務時間外です']);
    }

    /**
     * 休憩開始時間が休憩終了時間より後になっている場合、エラーメッセージが表示されることを確認
     *
     * @return void
     */
    public function test_displays_error_when_break_start_is_after_break_end()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $updateData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start_1' => '13:00',  // 休憩終了時間より後
            'break_end_1' => '12:00',
            'memo' => 'テストメモ',
        ];

        $response = $this->post("/attendance/update/{$attendance->id}", $updateData);

        $response->assertSessionHasErrors(['break_start_1' => '休憩時間が不適切な値です']);
    }

    /**
     * 休憩時間が勤務時間外（出勤時間より前）の場合、エラーメッセージが表示されることを確認
     *
     * @return void
     */
    public function test_displays_error_when_break_time_is_before_clock_in()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $updateData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start_1' => '08:00',  // 出勤時間より前
            'break_end_1' => '08:30',
            'memo' => 'テストメモ',
        ];

        $response = $this->post("/attendance/update/{$attendance->id}", $updateData);

        $response->assertSessionHasErrors(['break_start_1' => '休憩時間が勤務時間外です']);
    }

    /**
     * 備考欄が未入力の場合、エラーメッセージが表示されることを確認
     *
     * @return void
     */
    public function test_displays_error_when_memo_is_empty()
    {
        // 1. 勤怠情報が登録されたユーザーにログインをする
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 2. 勤怠詳細ページを開く
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 3. 備考欄を未入力のまま保存処理をする
        $updateData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'memo' => '',  // 未入力
        ];

        // 4. 保存処理をする
        $response = $this->post("/attendance/update/{$attendance->id}", $updateData);

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['memo']);
        $response->assertSessionHasErrors(['memo' => '備考を記入してください']);
    }
}