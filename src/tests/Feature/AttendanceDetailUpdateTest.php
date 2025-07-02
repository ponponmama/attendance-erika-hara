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
        $user1 = User::factory()->create();
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
}
