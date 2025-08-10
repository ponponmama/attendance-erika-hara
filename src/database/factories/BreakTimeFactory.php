<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BreakTime>
 */
class BreakTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'attendance_id' => null, // Seederで指定
            'break_start' => '12:00:00', // デフォルト値（withinWorkHoursで上書きされる）
            'break_end' => '13:00:00', // デフォルト値（withinWorkHoursで上書きされる）
        ];
    }

    /**
     * 勤務時間内に収まる休憩時間を生成
     */
    public function withinWorkHours(Attendance $attendance, $breakType = 'lunch')
    {
        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::parse($attendance->clock_out);

        // 勤務時間の長さを計算（分単位）
        $workDuration = $clockIn->diffInMinutes($clockOut);

        // 休憩パターンを勤務時間に応じて設定
        $breakPatterns = [];

        // 全員に必ず昼休憩を追加
        $breakPatterns[] = [
            'type' => 'lunch',
            'start_min_offset' => 240, // 出勤から4時間後（12:00）
            'start_max_offset' => 300, // 出勤から5時間後（13:00）
            'duration_min' => 45,
            'duration_max' => 60,
        ];

        // 6時間以上: 午前休憩も追加
        if ($workDuration >= 360) {
            $breakPatterns[] = [
                'type' => 'morning',
                'start_min_offset' => 90, // 出勤から1.5時間後
                'start_max_offset' => 150, // 出勤から2.5時間後
                'duration_min' => 10,
                'duration_max' => 20,
            ];
        }

        // 8時間以上: 午後休憩も追加
        if ($workDuration >= 480) {
            $breakPatterns[] = [
                'type' => 'afternoon',
                'start_min_offset' => 300, // 出勤から5時間後
                'start_max_offset' => 360, // 出勤から6時間後
                'duration_min' => 10,
                'duration_max' => 20,
            ];
        }

                // 指定された休憩タイプに基づいて休憩時間を生成
        if ($breakType === 'lunch') {
            // 昼休憩: 12:00-13:00の間に固定
            $startHour = $this->faker->numberBetween(12, 12);
            $startMinute = $this->faker->numberBetween(0, 59);
            $breakStart = Carbon::createFromTime($startHour, $startMinute, 0);

            // 勤務時間が短い場合は昼休憩時間を短縮
            if ($workDuration < 240) {
                $duration = $this->faker->numberBetween(15, 30); // 15-30分に短縮
            } else {
                $duration = $this->faker->numberBetween(45, 60);
            }
        } elseif ($breakType === 'morning') {
            // 午前休憩: 出勤から1.5-2.5時間後
            $startOffset = $this->faker->numberBetween(90, 150);
            $breakStart = (clone $clockIn)->addMinutes($startOffset);
            $duration = $this->faker->numberBetween(10, 20);
        } elseif ($breakType === 'afternoon') {
            // 午後休憩: 出勤から5-6時間後
            $startOffset = $this->faker->numberBetween(300, 360);
            $breakStart = (clone $clockIn)->addMinutes($startOffset);
            $duration = $this->faker->numberBetween(10, 20);
        }

        $breakEnd = (clone $breakStart)->addMinutes($duration);

        // 退勤時間を超えないように調整
        if ($breakEnd > $clockOut) {
            $breakEnd = clone $clockOut;
            $breakStart = (clone $breakEnd)->subMinutes($duration);
        }

        return $this->state([
            'break_start' => $breakStart->format('H:i:s'),
            'break_end' => $breakEnd->format('H:i:s'),
        ]);
    }
}
