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

                // 勤怠データは必ず作成
                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    // 5日ごとにだけ備考をつける
                    'memo' => ($day % 5 === 0) ? $reason : null,
                ]);

                BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                ]);

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
