<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\StampCorrectionRequest;

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
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        // 一般ユーザーを作成
        $users = User::factory(10)->create();

        // 各ユーザーに対して、勤怠データと休憩データを作成
        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                // 80%の確率でその日の勤怠データを作成
                if (rand(1, 100) <= 80) {
                    $attendance = Attendance::factory()->create([
                        'user_id' => $user->id,
                        'date' => now()->subDays($i)->toDateString(),
                    ]);

                    // 50%の確率で休憩データを1〜2個作成
                    if (rand(1, 100) <= 50) {
                        BreakTime::factory(rand(1, 2))->create([
                            'attendance_id' => $attendance->id,
                        ]);
                    }

                    // 10%の確率で修正依頼データを作成
                    if (rand(1, 100) <= 10) {
                        StampCorrectionRequest::factory()->create([
                            'user_id' => $user->id,
                            'attendance_id' => $attendance->id,
                        ]);
                    }
                }
            }
        }
    }
}