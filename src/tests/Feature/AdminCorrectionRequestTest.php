<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 承認待ちの修正申請が全て表示されていることを確認
     * @return void
     */
    public function test_displays_all_pending_correction_requests()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを2人作成
        $user1 = User::factory()->create(['name' => 'テスト太郎']);
        $user2 = User::factory()->create(['name' => 'テスト花子']);

        // 勤怠データを作成
        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => '2024-07-15',
        ]);
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => '2024-07-16',
        ]);

        // 承認待ちの修正申請を2件作成
        $request1 = StampCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'status' => 'pending',
            'reason' => '出勤時刻修正',
            'request_date' => '2024-07-15',
        ]);
        $request2 = StampCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'status' => 'pending',
            'reason' => '退勤時刻修正',
            'request_date' => '2024-07-16',
        ]);

        // 修正申請一覧ページ（承認待ちタブ）にアクセス
        $response = $this->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);

        // 2件の修正申請が表示されていることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('出勤時刻修正');
        $response->assertSee('テスト花子');
        $response->assertSee('退勤時刻修正');
    }

    /**
     * 承認済みの修正申請が全て表示されていることを確認
     * @return void
     */
    public function test_displays_all_approved_correction_requests()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを2人作成
        $user1 = User::factory()->create(['name' => 'テスト太郎']);
        $user2 = User::factory()->create(['name' => 'テスト花子']);

        // 勤怠データを作成
        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => '2024-07-15',
        ]);
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => '2024-07-16',
        ]);

        // 承認済みの修正申請を2件作成
        $request1 = StampCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'status' => 'approved',
            'reason' => '出勤時刻修正',
            'request_date' => '2024-07-15',
        ]);
        $request2 = StampCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'status' => 'approved',
            'reason' => '退勤時刻修正',
            'request_date' => '2024-07-16',
        ]);

        // 修正申請一覧ページ（承認済みタブ）にアクセス
        $response = $this->get('/stamp_correction_request/list?status=approved');
        $response->assertStatus(200);

        // 2件の修正申請が表示されていることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('出勤時刻修正');
        $response->assertSee('テスト花子');
        $response->assertSee('退勤時刻修正');
    }

    /**
     * 修正申請の詳細内容が正しく表示されていることを確認
     * @return void
     */
    public function test_displays_correction_request_in_list()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create(['name' => 'テスト太郎']);

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
        ]);

        // 修正申請を作成
        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'reason' => '出勤時刻修正',
            'request_date' => '2024-07-15',
        ]);

        // 修正申請一覧画面にアクセス
        $response = $this->get('/stamp_correction_request/list?status=pending');
        $response->assertStatus(200);

        // 申請内容が正しく表示されていることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('出勤時刻修正');
        $response->assertSee('2024/07/15');

        // 詳細ボタンが存在することを確認
        $response->assertSee('詳細');
    }

    /**
     * 修正申請の承認処理が正しく行われることを確認
     * @return void
     */
    public function test_approve_correction_request_updates_status_and_attendance()
    {
        // 管理者ユーザーを作成
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // 一般ユーザーを作成
        $user = User::factory()->create(['name' => 'テスト太郎']);

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 修正申請を作成
        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'reason' => '出勤時刻修正',
            'request_date' => '2024-07-15',
            'correction_data' => json_encode([
                'clock_in' => ['requested' => '08:30'],
                'clock_out' => ['requested' => '18:00']
            ]),
        ]);

        // 修正申請の承認処理を実行
        $response = $this->post("/stamp_correction_request/approve/{$request->id}");
        $response->assertStatus(302);

        // 修正申請のステータスが承認済みに更新されることを確認
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // 勤怠情報が更新されることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '08:30:00',
            'clock_out' => '18:00:00',
        ]);
    }
}
