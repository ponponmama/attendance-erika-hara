<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID: 9-1
     * 勤怠一覧情報取得機能（一般ユーザー） - 自分が行った勤怠情報が全て表示されている
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインする 2. 勤怠一覧ページを開く 3. 自分の勤怠情報がすべて表示されていることを確認する
     * 期待挙動: 自分の勤怠情報が全て表示されている
     */
    public function test_displays_all_user_attendance_records()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 複数の勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-06-24',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        // 他のユーザーの勤怠記録を作成（表示されないことを確認）
        $otherUser = User::factory()->create(['role' => 'user']);
        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2024-06-25',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 自分の勤怠記録が表示されていることを確認
        $response->assertSee('06/25');
        $response->assertSee('06/24');
        $response->assertSee('09:00');
        $response->assertSee('08:30');

        // 他のユーザーの勤怠記録が表示されていないことを確認
        $response->assertDontSee('10:00');
    }

    /**
     * ID: 9-2
     * 勤怠一覧情報取得機能（一般ユーザー） - 勤怠一覧画面に遷移した際に現在の月が表示される
     * テスト手順: 1. ユーザーにログインをする 2. 勤怠一覧ページを開く
     * 期待挙動: 現在の月が表示されている
     */
    public function test_displays_current_month()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 現在の月が表示されていることを確認
        $response->assertSee('2024/06');
    }

    /**
     * ID: 9-3
     * 勤怠一覧情報取得機能（一般ユーザー） - 「前月」を押下した時に表示月の前月の情報が表示される
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠一覧ページを開く 3. 「前月」ボタンを押す
     * 期待挙動: 前月の情報が表示されている
     */
    public function test_displays_previous_month_when_clicked()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 前月の勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-05-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 前月の勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list?month=2024-05');
        $response->assertStatus(200);

        // 前月の情報が表示されていることを確認
        $response->assertSee('2024/05');
        $response->assertSee('05/25');
    }

    /**
     * ID: 9-4
     * 勤怠一覧情報取得機能（一般ユーザー） - 「翌月」を押下した時に表示月の翌月の情報が表示される
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠一覧ページを開く 3. 「翌月」ボタンを押す
     * 期待挙動: 翌月の情報が表示されている
     */
    public function test_displays_next_month_when_clicked()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 翌月の勤怠記録を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 翌月の勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list?month=2024-07');
        $response->assertStatus(200);

        // 翌月の情報が表示されていることを確認
        $response->assertSee('2024/07');
        $response->assertSee('07/25');
    }

    /**
     * ID: 9-5
     * 勤怠一覧情報取得機能（一般ユーザー） - 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠一覧ページを開く 3. 「詳細」ボタンを押下する
     * 期待挙動: その日の勤怠詳細画面に遷移する
     */
            public function test_redirects_to_attendance_detail_when_detail_clicked()
    {
        // 一般ユーザーを作成
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        // テスト時間を固定（現在の月に合わせる）
        $testTime = Carbon::create(2025, 8, 5, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 勤怠記録を作成（現在の月のデータ）
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-08-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // デバッグ情報を確認
        $this->assertEquals('user', $user->role);
        $this->assertEquals($user->id, $attendance->user_id);

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 詳細リンクが表示されていることを確認
        $response->assertSee('詳細');

        // 詳細画面にアクセス
        $response = $this->get("/attendance/{$attendance->id}");

        // デバッグ情報を出力
        if ($response->status() !== 200) {
            dump([
                'user_id' => $user->id,
                'user_role' => $user->role,
                'attendance_user_id' => $attendance->user_id,
                'attendance_id' => $attendance->id,
                'response_status' => $response->status(),
                'response_content' => $response->content()
            ]);
        }

        $response->assertStatus(200);
    }

    /**
     * 他のユーザーの勤怠情報は表示されないことを確認
     */
    public function test_does_not_display_other_users_attendance()
    {
        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);

        $this->actingAs($user1);

        // 他のユーザーの勤怠データを作成
        $otherAttendance = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
        ]);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 他のユーザーの勤怠データが表示されないことを確認
        $response->assertDontSee('07/15');
        $response->assertDontSee('09:00');
    }

    /**
     * ID: 10-1
     * 勤怠詳細情報取得機能（一般ユーザー） - 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細ページを開く 3. 名前欄を確認する
     * 期待挙動: 名前がログインユーザーの名前になっている
     */
    public function test_displays_user_name_on_attendance_detail_page()
    {
        // 一般ユーザーを作成
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee($user->name);
        // 一般ユーザーなので修正フォームが表示されることを確認
        $response->assertSee('修正');
    }

    /**
     * ID: 10-2
     * 勤怠詳細情報取得機能（一般ユーザー） - 勤怠詳細画面の「日付」が選択した日付になっている
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細ページを開く 3. 日付欄を確認する
     * 期待挙動: 日付が選択した日付になっている
     */
    public function test_displays_selected_date_on_attendance_detail_page()
    {
        // 一般ユーザーを作成
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('2024年');
        $response->assertSee('7月15日');
        // 一般ユーザーなので修正フォームが表示されることを確認
        $response->assertSee('修正');
    }

    /**
     * ID: 10-3
     * 勤怠詳細情報取得機能（一般ユーザー） - 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細ページを開く 3. 出勤・退勤欄を確認する
     * 期待挙動: 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_displays_clock_in_out_times_on_attendance_detail_page()
    {
        // 一般ユーザーを作成
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        // 一般ユーザーなので修正フォームが表示されることを確認
        $response->assertSee('修正');
    }

    /**
     * ID: 10-4
     * 勤怠詳細情報取得機能（一般ユーザー） - 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     * テスト手順: 1. 勤怠情報が登録されたユーザーにログインをする 2. 勤怠詳細ページを開く 3. 休憩欄を確認する
     * 期待挙動: 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_displays_break_times_on_attendance_detail_page()
    {
        // 一般ユーザーを作成
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-07-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 休憩データを作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        // 一般ユーザーなので修正フォームが表示されることを確認
        $response->assertSee('修正');
    }
}