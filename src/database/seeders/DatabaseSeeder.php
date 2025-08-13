<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Database\Factories\ReasonList;

class DatabaseSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create('ja_JP');
    }

    public function run()
    {
        // 管理者ユーザー
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('user_pass'),
        ]);

        // 1. ユーザーをランダムで20人作成
        $users = User::factory()->count(20)->create();

        // 2. id順でメールアドレスを上書き
        foreach ($users as $user) {
            $user->email = 'user' . $user->id . '@example.com';
            $user->save();
        }

        // 各ユーザーに対して勤怠データを作成
        foreach ($users as $user) {
            for ($day = 1; $day <= 30; $day++) {
                $date = now()->subDays($day)->startOfDay();
                $reason = $this->faker->randomElement(ReasonList::REASONS);

                // 土日（0=日曜日、6=土曜日）は休日として空の勤怠データを作成
                if ($date->dayOfWeek === 0) {
                    $attendance = Attendance::factory()->create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'clock_in' => null,
                        'clock_out' => null,
                        'memo' => null,
                    ]);
                    continue;
                }

                // 勤怠データは必ず作成
                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    // 5日ごとにだけ備考をつける
                    'memo' => ($day % 5 === 0) ? $reason : null,
                ]);

                // 勤務時間を計算
                $workDuration = \Carbon\Carbon::parse($attendance->clock_in)->diffInMinutes(\Carbon\Carbon::parse($attendance->clock_out));

                // 休憩を時間順に作成
                if ($workDuration >= 360) {
                    // 6時間以上: 午前休憩を最初に作成
                    BreakTime::factory()->withinWorkHours($attendance, 'morning')->create([
                        'attendance_id' => $attendance->id,
                    ]);
                }

                // 昼休憩を作成（全員）
                BreakTime::factory()->withinWorkHours($attendance, 'lunch')->create([
                    'attendance_id' => $attendance->id,
                ]);

                if ($workDuration >= 480) {
                    // 8時間以上: 午後休憩を最後に作成
                    BreakTime::factory()->withinWorkHours($attendance, 'afternoon')->create([
                        'attendance_id' => $attendance->id,
                    ]);
                }

                // 5日ごとにだけ修正申請を作成
                if ($day % 5 === 0) {
                    $status = $this->faker->randomElement(['pending', 'approved']);
                    $approved_at = ($status === 'approved') ? $this->faker->dateTimeThisMonth() : null;
                    $approved_by = ($status === 'approved') ? $admin->id : null;

                    StampCorrectionRequest::factory()->create([
                        'user_id' => $user->id,
                        'attendance_id' => $attendance->id,
                        'request_date' => $date->toDateString(),
                        'reason' => $reason,
                        'status' => $status,
                        'approved_at' => $approved_at,
                        'approved_by' => $approved_by,
                    ]);
                }
            }
        }
    }
}