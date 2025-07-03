<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Database\Factories\ReasonList;

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
        // 30%の確率で休暇データを作成
        $isHoliday = $this->faker->boolean(30);

        if ($isHoliday) {
            return [
                'user_id' => null, // シーダーで指定される
                'date' => $this->faker->date(),
                'clock_in' => null,
                'clock_out' => null,
                'memo' => $this->faker->optional(0.5)->randomElement(['休日', '有給休暇', '病欠']),
            ];
        }

        // 通常の勤務日
        $clockInHour = $this->faker->randomElement([8, 9]); // 8時または9時
        $clockInMinute = $this->faker->numberBetween(0, 59);
        $clockIn = sprintf('%02d:%02d:00', $clockInHour, $clockInMinute);

        // 退勤時間は出勤時間から8-10時間後
        $workHours = $this->faker->numberBetween(8, 10);
        $clockOutHour = $clockInHour + $workHours;
        $clockOutMinute = $this->faker->numberBetween(0, 59);
        $clockOut = sprintf('%02d:%02d:00', $clockOutHour, $clockOutMinute);

        return [
            'user_id' => null, // シーダーで指定される
            'date' => $this->faker->date(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'memo' => $this->faker->optional(0.2)->randomElement(ReasonList::REASONS),
        ];
    }
}