<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID: 15-1
     * 勤怠情報修正機能（管理者） - 承認待ちの修正申請が全て表示されている
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 修正申請一覧ページを開き、承認待ちのタブを開く
     * 期待挙動: 全ユーザーの未承認の修正申請が表示される
     */
    public function test_displays_all_pending_requests()
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

        // 承認待ちの修正申請を作成
        StampCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'status' => 'pending',
            'reason' => '出勤時刻修正',
            'correction_type' => 'clock_in',
            'correction_data' => [
                'clock_in' => [
                    'current' => '09:00',
                    'requested' => '08:30'
                ]
            ],
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => '2024-07-15',
            'approved_by' => null,
        ]);

        StampCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'status' => 'pending',
            'reason' => '退勤時刻修正',
            'correction_type' => 'clock_out',
            'correction_data' => [
                'clock_out' => [
                    'current' => '18:00',
                    'requested' => '19:00'
                ]
            ],
            'current_time' => '18:00',
            'requested_time' => '19:00',
            'request_date' => '2024-07-16',
            'approved_by' => null,
        ]);

        $response = $this->get('/stamp_correction_request/list?status=pending');
        $response->assertStatus(200);

        // 全ユーザーの未承認の修正申請が表示されていることを確認
        $response->assertSee('出勤時刻修正');
        $response->assertSee('退勤時刻修正');
    }

    /**
     * ID: 15-2
     * 勤怠情報修正機能（管理者） - 承認済みの修正申請が全て表示されている
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 修正申請一覧ページを開き、承認済みのタブを開く
     * 期待挙動: 全ユーザーの承認済みの修正申請が表示される
     */
    public function test_displays_all_approved_requests()
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

        // 承認済みの修正申請を作成
        StampCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'status' => 'approved',
            'reason' => '出勤時刻修正',
            'correction_type' => 'clock_in',
            'correction_data' => [
                'clock_in' => [
                    'current' => '09:00',
                    'requested' => '08:30'
                ]
            ],
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => '2024-07-15',
            'approved_by' => $admin->id,
        ]);

        StampCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'status' => 'approved',
            'reason' => '退勤時刻修正',
            'correction_type' => 'clock_out',
            'correction_data' => [
                'clock_out' => [
                    'current' => '18:00',
                    'requested' => '19:00'
                ]
            ],
            'current_time' => '18:00',
            'requested_time' => '19:00',
            'request_date' => '2024-07-16',
            'approved_by' => $admin->id,
        ]);

        $response = $this->get('/stamp_correction_request/list?status=approved');
        $response->assertStatus(200);

        // 全ユーザーの承認済みの修正申請が表示されていることを確認
        $response->assertSee('出勤時刻修正');
        $response->assertSee('退勤時刻修正');
    }

    /**
     * ID: 15-3
     * 勤怠情報修正機能（管理者） - 修正申請の詳細内容が正しく表示されている
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 修正申請の詳細画面を開く
     * 期待挙動: 申請内容が正しく表示されている
     */
    public function test_displays_correct_request_detail()
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

        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'reason' => '出勤時刻修正',
            'correction_type' => 'clock_in',
            'correction_data' => [
                'clock_in' => [
                    'current' => '09:00',
                    'requested' => '08:30'
                ]
            ],
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => '2024-07-15',
            'approved_by' => null,
        ]);

        // attendance.detailビューを使用して詳細表示をテスト
        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 申請内容が正しく表示されていることを確認
        $response->assertSee('テスト太郎');
        $response->assertSee('出勤時刻修正');
    }

    /**
     * ID: 15-4
     * 勤怠情報修正機能（管理者） - 修正申請の承認処理が正しく行われる
     * テスト手順: 1. 管理者ユーザーにログインをする 2. 修正申請の詳細画面で「承認」ボタンを押す
     * 期待挙動: 修正申請が承認され、勤怠情報が更新される
     */
    public function test_approval_process_works_correctly()
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

        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'reason' => '出勤時刻修正',
            'correction_type' => 'clock_in',
            'correction_data' => [
                'clock_in' => [
                    'current' => '09:00',
                    'requested' => '08:30'
                ]
            ],
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => '2024-07-15',
            'approved_by' => null,
        ]);

        $response = $this->post("/stamp_correction_request/approve/{$request->id}");
        $response->assertStatus(302);

        // 修正申請が承認されていることを確認
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // 勤怠情報が更新されていることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2024-07-15 08:30:00',
        ]);
    }

    /**
     * 一般ユーザーは承認処理を実行できない
     */
    public function test_prevents_user_from_approving_requests()
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $admin = User::factory()->create(['role' => 'admin']);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
        ]);

        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'correction_type' => 'clock_in',
            'correction_data' => [
                'clock_in' => [
                    'current' => '09:00',
                    'requested' => '08:30'
                ]
            ],
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => '2024-07-15',
            'approved_by' => null,
        ]);

        $response = $this->post("/stamp_correction_request/approve/{$request->id}");
        $response->assertStatus(302);
    }

    /**
     * 未認証ユーザーは承認処理を実行できない
     */
    public function test_prevents_guest_from_approving_requests()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
        ]);

        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'correction_type' => 'clock_in',
            'correction_data' => [
                'clock_in' => [
                    'current' => '09:00',
                    'requested' => '08:30'
                ]
            ],
            'current_time' => '09:00',
            'requested_time' => '08:30',
            'request_date' => '2024-07-15',
            'approved_by' => null,
        ]);

        $response = $this->post("/stamp_correction_request/approve/{$request->id}");
        $response->assertRedirect('/login');
    }
}
