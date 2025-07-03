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

        // 一般ユーザーを作成
        $users = User::factory()->count(25)->create([
            'password' => Hash::make('user_pass'),
        ]);

        // 各ユーザーに対してFactoryを使って関連データを作成
        foreach ($users as $userIndex => $user) {
            // 月3回だけ修正申請を作成する日をランダムに決定
            $correctionDays = collect(range(1, 30))->shuffle()->take(3);
            for ($day = 1; $day <= 30; $day++) {
                $date = now()->subDays($day)->toDateString();

                // 勤怠データを作成
                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date,
                ]);

                // 出勤・退勤が両方nullなら休憩・修正依頼は作らない
                if (!is_null($attendance->clock_in) && !is_null($attendance->clock_out) &&
                    $attendance->clock_in !== '' && $attendance->clock_out !== '') {
                    // 休憩データを作成（2-3回）
                    $breakCount = rand(2, 3);
                    for ($i = 0; $i < $breakCount; $i++) {
                        BreakTime::factory()->create([
                            'attendance_id' => $attendance->id,
                        ]);
                    }

                    // 修正申請は月3回だけ
                    if ($correctionDays->contains($day)) {
                        $requestCount = rand(1, 2);
                        for ($i = 0; $i < $requestCount; $i++) {
                            StampCorrectionRequest::factory()->create([
                                'user_id' => $user->id,
                                'attendance_id' => $attendance->id,
                                'approved_by' => $admin->id,
                                'request_date' => $date,
                            ]);
                        }
                    }
                }
            }
        }
    }
}