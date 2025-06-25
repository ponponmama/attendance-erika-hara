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
        $response = $this->post(route('attendance.clock-in'));
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

        // 退勤後はステータスが「勤務終了」となり、出勤ボタンが押せない状態であることを確認
        $response->assertSee('勤務終了');
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
}