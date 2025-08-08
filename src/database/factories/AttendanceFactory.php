<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Database\Factories\ReasonList;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // 出勤時間: 8:00〜9:30の間でランダム
        $clockInHour = $this->faker->randomElement([8, 9]);
        $clockInMinute = $this->faker->numberBetween(0, 59);
        $clockIn = sprintf('%02d:%02d:00', $clockInHour, $clockInMinute);

        // 退勤時間: 出勤時間から8〜10時間後
        $workHours = $this->faker->numberBetween(8, 10);
        $clockOutHour = $clockInHour + $workHours;
        $clockOutMinute = $this->faker->numberBetween(0, 59);
        $clockOut = sprintf('%02d:%02d:00', $clockOutHour, $clockOutMinute);

        return [
            'user_id' => null, // Seederで指定
            'date' => null, // Seederで指定（ランダム生成を無効化）
            'clock_in' => $clockIn, // 日付はAttendanceモデルで自動設定される
            'clock_out' => $clockOut, // 日付はAttendanceモデルで自動設定される
            'memo' => null, // Seederで指定（ランダム生成を無効化）
        ];
    }
}