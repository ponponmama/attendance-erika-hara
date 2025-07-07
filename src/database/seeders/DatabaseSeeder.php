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

        // 一般ユーザー5人を固定で作成
        $users = collect([
            [
                'name' => '西 玲奈',
                'email' => 'user1@example.com',
            ],
            [
                'name' => '山田 太郎',
                'email' => 'user2@example.com',
            ],
            [
                'name' => '山田 花子',
                'email' => 'user3@example.com',
            ],
            [
                'name' => '佐藤 次郎',
                'email' => 'user4@example.com',
            ],
            [
                'name' => '鈴木 三郎',
                'email' => 'user5@example.com',
            ],
        ])->map(function($userData) {
            return User::factory()->user()->create(array_merge($userData, [
                'password' => Hash::make('user_pass'),
            ]));
        });

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
