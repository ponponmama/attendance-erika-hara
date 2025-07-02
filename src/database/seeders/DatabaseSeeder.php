<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Database\Factories\ReasonList;

class DatabaseSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create('ja_JP');
    }

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザー
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('user_pass'),
        ]);

        // 一般ユーザー20人
        $users = User::factory(20)->create([
            'password' => Hash::make('user_pass'),
        ]);

        // 各ユーザーごとにランダムな3日間を決める
        $memoDays = [];
        for ($j = 0; $j < 3; $j++) {
            $memoDays[] = rand(1, 30);
        }
        $memoDays = array_unique($memoDays); // 重複を除去

        // 各ユーザーに対して過去7日分の勤怠・休憩・修正依頼データを作成
        foreach ($users as $user) {
            for ($i = 1; $i <= 30; $i++) {
                $date = now()->subDays($i)->toDateString();

                // 土日（土曜日=6、日曜日=0）は休み
                $dayOfWeek = Carbon::parse($date)->dayOfWeek;
                $isHoliday = ($dayOfWeek === 0 || $dayOfWeek === 6);

                // ランダムに決めた日はmemoを確実に設定
                if (in_array($i, $memoDays)) {
                    $attendance = Attendance::factory()->create([
                        'user_id' => $user->id,
                        'date' => $date,
                        'clock_in' => $isHoliday ? null : Attendance::factory()->make()->clock_in,
                        'clock_out' => $isHoliday ? null : Attendance::factory()->make()->clock_out,
                        'memo' => Attendance::factory()->make()->memo, // AttendanceFactoryのmemoを使う
                    ]);
                } else {
                    $attendance = Attendance::factory()->create([
                        'user_id' => $user->id,
                        'date' => $date,
                        'clock_in' => $isHoliday ? null : Attendance::factory()->make()->clock_in,
                        'clock_out' => $isHoliday ? null : Attendance::factory()->make()->clock_out,
                    ]);
                }

                // 休みの日は休憩データを作成しない
                if (!$isHoliday) {
                    // 朝休憩（10:10〜10:20〜10:25）
                    $start = Carbon::parse($date . ' 10:10');
                    $end = (clone $start)->addMinutes(rand(10, 15));
                    BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $start,
                        'break_end' => $end,
                    ]);

                    // 昼休憩（12:00〜13:00）
                    $start = Carbon::parse($date . ' 12:00');
                    $end = (clone $start)->addHour();
                    BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $start,
                        'break_end' => $end,
                    ]);

                    // 15時休憩（15:00〜15:10〜15:15）
                    $start = Carbon::parse($date . ' 15:00');
                    $end = (clone $start)->addMinutes(rand(10, 15));
                    BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $start,
                        'break_end' => $end,
                    ]);
                }

                // memoがある勤怠データに対して修正依頼を作成（3件分）
                if ($attendance->memo && StampCorrectionRequest::where('user_id', $user->id)->count() < 3) {
                    StampCorrectionRequest::factory()->create([
                        'user_id' => $user->id,
                        'attendance_id' => $attendance->id,
                        'request_date' => $date,
                        // 必要なら他の項目も上書き
                    ]);
                }
            }
        }

        User::factory()
            ->count(10)
            ->has(
                Attendance::factory()
                    ->count(30)
                    ->has(
                        BreakTime::factory()->count(2),
                        'breakTimes'
                    )
                    ->has(
                        StampCorrectionRequest::factory()->count(1),
                        'stampCorrectionRequests'
                    ),
                'attendances'
            )
            ->create();
    }
}