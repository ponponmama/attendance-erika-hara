<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠打刻画面に、現在の日時が正しい形式で表示されることを確認
     *
     * @return void
     */
    public function test_displays_current_date_and_time()
    {
        $this->withoutExceptionHandling();

        // 1. テスト用のユーザーを作成し、ログイン状態にする
        $user = User::factory()->create();
        $this->actingAs($user);

        // 2. テストの実行時間を「2024年6月25日 10時30分00秒」に固定する
        $testNow = Carbon::create(2024, 6, 25, 10, 30, 0);
        Carbon::setTestNow($testNow);

        // 3. 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // 4. レスポンスを検証する
        //    - 画面が正常に表示されることを確認
        $response->assertStatus(200);
        //    - 画面に、固定した日付「2024年6月25日(火)」が表示されていることを確認
        $response->assertSee('2024年6月25日(火)');
        //    - 画面に、固定した時間「10:30」が表示されていることを確認
        $response->assertSee('10:30');
        //    - 画面に、秒「:00」が表示されていないことを確認
        $response->assertDontSee(':00');
    }

    /**
     * 出勤ボタンが正しく機能することを確認
     * @return void
     */
    public function test_can_clock_in()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤処理をPOST
        $response = $this->post(route('attendance_clock_in'));
        $response->assertRedirect('/attendance');

        // データベースに出勤記録が保存されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
        ]);

        // ステータスが出勤中になっていることを確認
        $this->get('/attendance')->assertSee('出勤中');
    }

    /**
     * 出勤は一日一回のみできることを確認
     * @return void
     */
    public function test_clock_in_button_is_disabled_when_already_worked()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // すでに退勤済みの日勤データを作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => Carbon::now(),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 退勤後はステータスが「終了済」となり、出勤ボタンが押せない状態であることを確認
        $response->assertSee('終了済');
        $response->assertDontSee('勤務外');
    }

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示されることを確認
     * @return void
     */
    public function test_displays_correct_status_when_not_working()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 勤務外の場合、ステータスが「勤務外」となっていることを確認
        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示されることを確認
     * @return void
     */
    public function test_displays_correct_status_when_working()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中のデータを作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 出勤中の場合、ステータスが「出勤中」となっていることを確認
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示されることを確認
     * @return void
     */
    public function test_displays_correct_status_when_on_break()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中のデータを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        // 休憩中のデータを作成
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subMinutes(30),
            'break_end' => null,
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 休憩中の場合、ステータスが「休憩中」となっていることを確認
        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示されることを確認
     * @return void
     */
    public function test_displays_correct_status_when_clocked_out()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 退勤済のデータを作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 退勤済の場合、ステータスが「終了済」となっていることを確認
        $response->assertSee('終了済');
    }

    /**
     * 出勤ボタンが表示されることを確認
     * @return void
     */
    public function test_clock_in_button_is_displayed_when_not_working()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 勤務外の場合、出勤ボタンが表示されていることを確認
        $response->assertSee('出勤');
    }

    /**
     * 出勤ボタンが表示されないことを確認
     * @return void
     */
    public function test_clock_in_button_is_not_displayed_when_already_worked()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // すでに退勤済みの日勤データを作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => Carbon::now(),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 退勤済の場合、出勤ボタンが表示されないことを確認
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できることを確認
     * @return void
     */
    public function test_clock_in_time_is_recorded_in_attendance_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 出勤処理を実行
        $response = $this->post(route('attendance_clock_in'));
        $response->assertRedirect('/attendance');

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 出勤時刻が記録されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_in' => '09:00:00',
        ]);

        // 勤怠一覧画面に出勤時刻が表示されていることを確認
        $response->assertSee('09:00');
    }

    /**
     * 休憩ボタンが正しく機能することを確認
     * @return void
     */
    public function test_can_start_break()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中のデータを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        // 休憩開始処理をPOST
        $response = $this->post(route('attendance_break_start'));
        $response->assertRedirect('/attendance');

        // データベースに休憩記録が保存されていることを確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_end' => null,
        ]);

        // ステータスが休憩中になっていることを確認
        $this->get('/attendance')->assertSee('休憩中');
    }

    /**
     * 休憩は一日に何回でもできることを確認
     * @return void
     */
    public function test_can_start_break_multiple_times()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中のデータを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        // 1回目の休憩開始
        $response = $this->post(route('attendance_break_start'));
        $response->assertRedirect('/attendance');

        // 1回目の休憩終了
        $response = $this->post(route('attendance_break_end'));
        $response->assertRedirect('/attendance');

        // 2回目の休憩開始
        $response = $this->post(route('attendance_break_start'));
        $response->assertRedirect('/attendance');

        // 休憩入ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩戻ボタンが正しく機能することを確認
     * @return void
     */
    public function test_can_end_break()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中のデータを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        // 休憩開始
        $response = $this->post(route('attendance_break_start'));
        $response->assertRedirect('/attendance');

        // 休憩終了処理をPOST
        $response = $this->post(route('attendance_break_end'));
        $response->assertRedirect('/attendance');

        // データベースに休憩終了時刻が保存されていることを確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_end' => Carbon::now()->format('H:i:s'),
        ]);

        // ステータスが出勤中に戻っていることを確認
        $this->get('/attendance')->assertSee('出勤中');
    }

    /**
     * 休憩戻は一日に何回でもできることを確認
     * @return void
     */
    public function test_can_end_break_multiple_times()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中のデータを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        // 1回目の休憩開始と終了
        $this->post(route('attendance_break_start'));
        $this->post(route('attendance_break_end'));

        // 2回目の休憩開始
        $this->post(route('attendance_break_start'));

        // 休憩戻ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できることを確認
     * @return void
     */
    public function test_break_time_is_recorded_in_attendance_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 出勤処理を実行
        $this->post(route('attendance_clock_in'));

        // 作成されたattendanceを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', '2024-06-25')
            ->first();

        // 休憩開始処理を実行
        $this->post(route('attendance_break_start'));

        // 休憩終了処理を実行
        $this->post(route('attendance_break_end'));

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 休憩時刻が記録されていることを確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => '09:00:00',
            'break_end' => '09:00:00',
        ]);

        // 勤怠一覧画面に休憩時刻が表示されていることを確認
        $response->assertSee('09:00');
    }

    /**
     * 退勤ボタンが正しく機能することを確認
     * @return void
     */
    public function test_can_clock_out()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中のデータを作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        // 退勤処理をPOST
        $response = $this->post(route('attendance_clock_out'));
        $response->assertRedirect('/attendance');

        // データベースに退勤記録が保存されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_out' => Carbon::now()->format('H:i:s'),
        ]);

        // ステータスが終了済になっていることを確認
        $this->get('/attendance')->assertSee('終了済');
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できることを確認
     * @return void
     */
    public function test_clock_out_time_is_recorded_in_attendance_list()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // テスト時間を固定
        $testTime = Carbon::create(2024, 6, 25, 9, 0, 0);
        Carbon::setTestNow($testTime);

        // 出勤処理を実行
        $this->post(route('attendance_clock_in'));

        // 退勤処理を実行
        $this->post(route('attendance_clock_out'));

        // 勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 退勤時刻が記録されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2024-06-25',
            'clock_out' => '09:00:00',
        ]);

        // 勤怠一覧画面に退勤時刻が表示されていることを確認
        $response->assertSee('09:00');
    }
}
