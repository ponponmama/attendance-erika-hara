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
        $users = User::factory()->count(5)->create([
            'password' => Hash::make('user_pass'),
        ]);

        foreach ($users as $user) {
            $attendances = [];
            for ($day = 1; $day <= 30; $day++) {
                $date = now()->subDays($day)->toDateString();
                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'memo' => null,
                ]);
                $attendances[] = $attendance;

                // 出勤データだけを抽出（clock_inとclock_outが両方あるものだけ）
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                // 勤務時間内でランダムな休憩開始
                $breakCount = rand(0, 3);
                for ($i = 0; $i < $breakCount; $i++) {
                    $breakStart = $clockIn->copy()->addMinutes(rand(0, $clockOut->diffInMinutes($clockIn) - 10));
                    // 10〜60分の休憩
                    $breakEnd = $breakStart->copy()->addMinutes(rand(10, min(60, $clockOut->diffInMinutes($breakStart))));
                    // 退勤時間を超えないように調整
                    if ($breakEnd->gt($clockOut)) {
                        $breakEnd = $clockOut->copy();
                    }
                    \App\Models\BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart->format('H:i:s'),
                        'break_end' => $breakEnd->format('H:i:s'),
                    ]);
                }
            }

            // 出勤データだけを抽出（clock_inとclock_outが両方あるものだけ）
            $workAttendances = collect($attendances)->filter(function($a) {
                return $a->clock_in && $a->clock_out;
            });

            // ここで「ユーザーごとに1回だけ」ランダムで3件選ぶ
            $randomAttendances = $workAttendances->random(min(3, $workAttendances->count()));

            foreach ($randomAttendances as $attendance) {
                // correction_typeをランダムで決める
                $correction_type = $this->faker->randomElement(['clock_in', 'clock_out']);
                $current_time = $correction_type === 'clock_in' ? $attendance->clock_in : $attendance->clock_out;

                // 必ずcurrent_timeがNULLでないものだけ申請を作る
                if ($current_time) {
                    $attendance->memo = $this->faker->randomElement(\Database\Factories\ReasonList::REASONS);
                    $attendance->save();

                    $status = $this->faker->randomElement(['pending', 'approved']);
                    $approved_at = ($status === 'approved') ? $this->faker->dateTimeThisMonth() : null;
                    $approved_by = ($status === 'approved') ? $admin->id : null;

                    \App\Models\StampCorrectionRequest::factory()->create([
                        'user_id' => $user->id,
                        'attendance_id' => $attendance->id,
                        'approved_by' => $approved_by,
                        'request_date' => $attendance->date,
                        'correction_type' => $correction_type,
                        'current_time' => $current_time,
                        'requested_time' => $this->faker->time('H:i:s'),
                        'reason' => $this->faker->randomElement(\Database\Factories\ReasonList::REASONS),
                        'status' => $status,
                        'approved_at' => $approved_at,
                    ]);
                }
            }
        }
    }
}
