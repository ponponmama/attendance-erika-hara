<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
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

        // 各ユーザーに対して過去3日分の勤怠・休憩・修正依頼データを作成
        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $date = now()->subDays($i)->toDateString();

                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date,
                ]);

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

                // 1日だけ修正依頼データ
                if ($i === 0) {
                    StampCorrectionRequest::factory()->create([
                        'user_id' => $user->id,
                        'attendance_id' => $attendance->id,
                    ]);
                }
            }
        }
    }
}