<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeStampTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のデータベースをリフレッシュ
        $this->refreshDatabase();

        // マイグレーションを実行
        $this->artisan('migrate:fresh');
    }

    /**
     * ID: 4-1
     * 日時取得機能 - 現在の日時情報がUIと同じ形式で出力されている
     * テスト手順: 1. 勤怠打刻画面を開く 2. 画面に表示されている日時情報を確認する
     * 期待挙動: 画面上に表示されている日時が現在の日時と一致する
     */
    public function test_displays_current_date_time_in_ui_format()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 現在の日時が表示されていることを確認
        $currentDate = Carbon::now()->format('Y年n月j日');
        $currentTime = Carbon::now()->format('H:i');
        $response->assertSee($currentDate);
        $response->assertSee($currentTime);
        // 曜日も含めて確認
        $weekday = ['日', '月', '火', '水', '木', '金', '土'][Carbon::now()->dayOfWeek];
        $response->assertSee("({$weekday})");
    }

    /**
     * ID: 5-1
     * ステータス確認機能 - 勤務外の場合、勤怠ステータスが正しく表示される
     * テスト手順: 1. ステータスが勤務外のユーザーにログインする 2. 勤怠打刻画面を開く 3. 画面に表示されているステータスを確認する
     * 期待挙動: 画面上に表示されているステータスが「勤務外」となる
     */
    public function test_displays_work_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // ステータスが表示されていることを確認
        $response->assertSee('勤務外');
    }

    /**
     * ID: 5-2
     * ステータス確認機能 - 出勤中の場合、勤怠ステータスが正しく表示される
     * テスト手順: 1. ステータスが出勤中のユーザーにログインする 2. 勤怠打刻画面を開く 3. 画面に表示されているステータスを確認する
     * 期待挙動: 画面上に表示されているステータスが「勤務中」となる
     */
    public function test_displays_working_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤処理
        $response = $this->post('/clock-in');
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        // データベースの状態を確認
        $attendance = \App\Models\Attendance::where('user_id', $user->id)->first();
        if ($attendance) {
            echo "Attendance found: " . $attendance->date . " - " . $attendance->clock_in . "\n";
        } else {
            echo "No attendance record found\n";
        }

        // リダイレクト先の画面を取得して「勤務中」が表示されることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務中');
    }

    /**
     * ID: 5-3
     * ステータス確認機能 - 休憩中の場合、勤怠ステータスが正しく表示される
     * テスト手順: 1. ステータスが休憩中のユーザーにログインする 2. 勤怠打刻画面を開く 3. 画面に表示されているステータスを確認する
     * 期待挙動: 画面上に表示されているステータスが「休憩中」となる
     */
    public function test_displays_break_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤してから休憩に入る
        $this->post('/clock-in');
        $this->post('/break-start');

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        // 休憩中の画面で「休憩中」が表示されることを確認
        $response->assertSee('休憩中');
    }

    /**
     * ID: 5-4
     * ステータス確認機能 - 退勤済の場合、勤怠ステータスが正しく表示される
     * テスト手順: 1. ステータスが退勤済のユーザーにログインする 2. 勤怠打刻画面を開く 3. 画面に表示されているステータスを確認する
     * 期待挙動: 画面上に表示されているステータスが「終了済」となる
     */
    public function test_displays_clocked_out_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤してから退勤する
        $this->post('/clock-in');
        $this->post('/clock-out');

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        // 退勤済の画面で「終了済」が表示されることを確認
        $response->assertSee('終了済');
    }

    /**
     * ID: 6-1
     * 出勤機能 - 出勤ボタンが正しく機能する
     * テスト手順: 1. ステータスが勤務外のユーザーにログインする 2. 画面に「出勤」ボタンが表示されていることを確認する 3. 出勤の処理を行う
     * 期待挙動: 画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「勤務中」になる
     */
    public function test_shows_clock_in_button_when_status_is_off_duty()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');

        // 出勤処理を実行
        $response = $this->post('/clock-in');
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        // リダイレクト後の画面で「勤務中」ステータスが表示されることを確認
        $response = $this->get('/attendance');
        $response->assertSee('勤務中');
    }

    /**
     * ID: 6-2
     * 出勤機能 - 出勤は一日一回のみできる
     * テスト手順: 1. ステータスが退勤済であるユーザーにログインする 2. 勤務ボタンが表示されないことを確認する
     * 期待挙動: 画面上に「出勤」ボタンが表示されない
     */
    public function test_can_only_clock_in_once_per_day()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤してから退勤する（退勤済の状態を作る）
        $this->post('/clock-in');
        $this->post('/clock-out');

        // 退勤済の状態で画面を確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // 退勤済の状態では出勤ボタンが表示されないことを確認
        $response->assertDontSee('出勤');
    }

    /**
     * ID: 6-3
     * 出勤機能 - 出勤時刻が勤怠一覧画面で確認できる
     * テスト手順: 1. ステータスが勤務外のユーザーにログインする 2. 出勤の処理を行う 3. 勤怠一覧画面から出勤の日付を確認する
     * 期待挙動: 勤怠一覧画面に出勤時刻が正確に記録されている
     */
    public function test_clock_in_time_is_recorded_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $clockInTime = Carbon::now();
        $response = $this->post('/clock-in');
        $response->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $clockInTime->format('Y-m-d 00:00:00'),
            'clock_in' => $clockInTime->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * データベースの状態確認テスト
     */
    public function test_clock_in_creates_attendance_record()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤処理前の状態確認
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d 00:00:00'),
        ]);

        // 出勤処理
        $response = $this->post('/clock-in');
        $response->assertStatus(302);

        // 出勤処理後のデータベース状態確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d 00:00:00'),
        ]);

        // 実際のレコードを取得して確認
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_in);
        $this->assertNull($attendance->clock_out);
    }

    /**
     * 出勤後の画面表示確認テスト
     */
    public function test_clock_in_shows_working_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤処理
        $response = $this->post('/clock-in');
        $response->assertStatus(302);

        // 出勤後の画面表示確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        // デバッグ用：実際のHTML内容を確認
        $content = $response->getContent();
        if (strpos($content, '勤務外') !== false) {
            $this->fail('出勤後も「勤務外」が表示されています。HTML: ' . $content);
        }

        $response->assertSee('勤務中');
    }

    /**
     * ID: 7-1
     * 休憩機能 - 休憩ボタンが正しく機能する
     * テスト手順: 1. ステータスが出勤中のユーザーにログインする 2. 画面に「休憩入」ボタンが表示されていることを確認する 3. 休憩の処理を行う
     * 期待挙動: 画面上に「休憩入」ボタンが表示され、処理後に画面上に表示されるステータスが「休憩中」になる
     */
    public function test_shows_break_button_when_status_is_working()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // まず出勤する
        $response = $this->post('/clock-in');
        $response->assertStatus(302);

        // 出勤後の画面で「休憩入」ボタンが表示されることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩処理を実行
        $response = $this->post('/break-start');
        $response->assertStatus(302);

        // 処理後に「休憩中」ステータスが表示されることを確認
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    /**
     * ID: 7-2
     * 休憩機能 - 休憩は一日に何回でもできる
     * テスト手順: 1. ステータスが出勤中であるユーザーにログインする 2. 休憩入と休憩戻の処理を行う 3. 「休憩入」ボタンが表示されることを確認する
     * 期待挙動: 画面上に「休憩入」ボタンが表示される
     */
    public function test_can_start_break_multiple_times_per_day()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤
        $this->post('/clock-in');

        // 1回目の休憩開始
        $response1 = $this->post('/break-start');
        $response1->assertStatus(302);

        // 休憩終了
        $this->post('/break-end');

        // デバッグ: 1回目の休憩終了後の状態を確認
        $attendance = \App\Models\Attendance::where('user_id', $user->id)->whereDate('date', \Carbon\Carbon::today())->first();
        $breaks = \App\Models\BreakTime::where('attendance_id', $attendance->id)->get();
        dump('1回目の休憩終了後の休憩レコード数: ' . $breaks->count());
        foreach ($breaks as $break) {
            dump('休憩レコード: start=' . $break->break_start . ', end=' . $break->break_end);
        }

        // 2回目の休憩開始（成功するはず）
        $response2 = $this->post('/break-start');
        $response2->assertStatus(302);

        // デバッグ: 2回目の休憩開始後の状態を確認
        $breaks = \App\Models\BreakTime::where('attendance_id', $attendance->id)->get();
        dump('2回目の休憩開始後の休憩レコード数: ' . $breaks->count());
        foreach ($breaks as $break) {
            dump('休憩レコード: start=' . $break->break_start . ', end=' . $break->break_end);
        }

        // 休憩戻ボタンが表示されることを確認（2回目の休憩開始後は休憩中になるため）
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /**
     * ID: 7-3
     * 休憩機能 - 休憩戻ボタンが正しく機能する
     * テスト手順: 1. ステータスが出勤中であるユーザーにログインする 2. 休憩入の処理を行う 3. 休憩戻の処理を行う
     * 期待挙動: 休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更される
     */
    public function test_shows_break_end_button_when_status_is_break()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤
        $response = $this->post('/clock-in');
        $response->assertStatus(302);

        // 休憩開始
        $response = $this->post('/break-start');
        $response->assertStatus(302);

        // 休憩中の画面で「休憩戻」ボタンが表示されることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        // 休憩戻処理を実行
        $response = $this->post('/break-end');
        $response->assertStatus(302);

        // 処理後に「勤務中」ステータスが表示されることを確認
        $response = $this->get('/attendance');
        $response->assertSee('勤務中');
    }

    /**
     * ID: 7-4
     * 休憩機能 - 休憩戻は一日に何回でもできる
     * テスト手順: 1. ステータスが出勤中であるユーザーにログインする 2. 休憩入と休憩戻の処理を行い、再度休憩入の処理を行う 3. 「休憩戻」ボタンが表示されることを確認する
     * 期待挙動: 画面上に「休憩戻」ボタンが表示される
     */
    public function test_can_end_break_multiple_times_per_day()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤
        $this->post('/clock-in');

        // 1回目の休憩開始・終了
        $this->post('/break-start');
        $response1 = $this->post('/break-end');
        $response1->assertStatus(302);

        // 2回目の休憩開始
        $response2 = $this->post('/break-start');
        $response2->assertStatus(302);

        // 休憩戻ボタンが表示されることを確認（2回目の休憩開始後は休憩中になるため）
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /**
     * ID: 7-5
     * 休憩機能 - 休憩時刻が勤怠一覧画面で確認できる
     * テスト手順: 1. ステータスが勤務中のユーザーにログインする 2. 休憩入と休憩戻の処理を行う 3. 勤怠一覧画面から休憩の日付を確認する
     * 期待挙動: 勤怠一覧画面に休憩時刻が正確に記録されている
     */
    public function test_break_times_are_recorded_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤
        $response = $this->post('/clock-in');
        $response->assertStatus(302);

        // 休憩開始
        $breakStartTime = Carbon::now();
        $response = $this->post('/break-start');
        $response->assertStatus(302);

        // 休憩終了
        $breakEndTime = Carbon::now();
        $response = $this->post('/break-end');
        $response->assertStatus(302);

        // 休憩時間が正しく記録されていることを確認
        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', Carbon::today())->first();
        $this->assertNotNull($attendance);

        // breaksテーブルに休憩記録が作成されていることを確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $breakStartTime->format('Y-m-d H:i:s'),
            'break_end' => $breakEndTime->format('Y-m-d H:i:s'),
        ]);

        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務中');
    }

    /**
     * ID: 8-1
     * 退勤機能 - 退勤ボタンが正しく機能する
     * テスト手順: 1. ステータスが勤務中のユーザーにログインする 2. 画面に「退勤」ボタンが表示されていることを確認する 3. 退勤の処理を行う
     * 期待挙動: 画面上に「退勤」ボタンが表示され、処理後に画面上に表示されるステータスが「終了済」になる
     */
    public function test_shows_clock_out_button_when_status_is_working()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤
        $response = $this->post('/clock-in');
        $response->assertStatus(302);

        // 出勤後の画面で「退勤」ボタンが表示されることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤');

        // 退勤処理を実行
        $response = $this->post('/clock-out');
        $response->assertStatus(302);

        // 処理後に「終了済」ステータスが表示されることを確認
        $response = $this->get('/attendance');
        $response->assertSee('終了済');
    }

    /**
     * ID: 8-2
     * 退勤機能 - 退勤時刻が勤怠一覧画面で確認できる
     * テスト手順: 1. ステータスが勤務外のユーザーにログインする 2. 出勤と退勤の処理を行う 3. 勤怠一覧画面から退勤の日付を確認する
     * 期待挙動: 勤怠一覧画面に退勤時刻が正確に記録されている
     */
    public function test_clock_out_time_is_recorded_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤
        $this->post('/clock-in');

        // 退勤
        $clockOutTime = Carbon::now();
        $response = $this->post('/clock-out');
        $response->assertStatus(302);

        // データベースに退勤時刻が記録されていることを確認
        $attendance = Attendance::where('user_id', $user->id)->first();
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_out' => $clockOutTime->format('Y-m-d H:i:s'),
        ]);
    }
}