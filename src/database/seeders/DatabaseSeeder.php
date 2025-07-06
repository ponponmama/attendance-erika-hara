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
                // より現実的な日時を設定
                $date = now()->subDays($day)->startOfDay();

                // 同じ理由を勤怠と修正申請で使用するため、事前に決定
                $reason = $this->faker->randomElement(ReasonList::REASONS);

                // 勤怠データを作成（日付とmemoに理由を設定）
                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(), // 日付のみ
                    'memo' => $reason, // 備考欄に理由を設定
                ]);

                // 休憩データを作成（必ず1回は作成）
                BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                ]);

                // 修正申請データを作成（承認待ちと承認済みをランダムに）
                $status = $this->faker->randomElement(['pending', 'approved']);
                $approved_at = ($status === 'approved') ? $this->faker->dateTimeThisMonth() : null;
                $approved_by = ($status === 'approved') ? $admin->id : null;

                StampCorrectionRequest::factory()->create([
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'request_date' => $date->toDateString(), // 勤怠と同じ日付を設定
                    'reason' => $reason, // 勤怠の備考と同じ理由を設定
                    'status' => $status,
                    'approved_at' => $approved_at,
                    'approved_by' => $approved_by,
                ]);
            }
        }
    }
}
