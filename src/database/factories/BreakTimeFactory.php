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

        // 指定された休憩タイプに基づいて休憩時間を生成
        if ($breakType === 'lunch') {
            // 昼休憩: 12:00-13:00の間に収まるように調整
            $startHour = 12;
            $startMinute = $this->faker->numberBetween(0, 15); // 12:00-12:15の間に開始
            $breakStart = Carbon::createFromTime($startHour, $startMinute, 0);

            // 昼休憩時間: 45-60分（13:00を超えないように）
            $duration = $this->faker->numberBetween(45, 60);

            // 13:00を超える場合は調整
            $breakEnd = (clone $breakStart)->addMinutes($duration);
            if ($breakEnd->format('H') > 13 || ($breakEnd->format('H') == 13 && $breakEnd->format('i') > 0)) {
                $duration = 60 - $startMinute; // 13:00ちょうどまで
            }
        } elseif ($breakType === 'morning') {
            // 午前休憩: 出勤から1.5-2.5時間後（昼休憩より前）
            $startOffset = $this->faker->numberBetween(90, 150);
            $breakStart = (clone $clockIn)->addMinutes($startOffset);
            $duration = $this->faker->numberBetween(10, 15);
        } elseif ($breakType === 'afternoon') {
            // 午後休憩: 出勤から5-6時間後（昼休憩より後）
            $startOffset = $this->faker->numberBetween(300, 360);
            $breakStart = (clone $clockIn)->addMinutes($startOffset);
            $duration = $this->faker->numberBetween(10, 15);
        } else {
            // デフォルトは昼休憩
            $startHour = $this->faker->numberBetween(12, 12);
            $startMinute = $this->faker->numberBetween(0, 59);
            $breakStart = Carbon::createFromTime($startHour, $startMinute, 0);
            $duration = $this->faker->numberBetween(45, 60);
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
