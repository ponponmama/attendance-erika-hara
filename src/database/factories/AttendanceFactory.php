<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $clockIn = $this->faker->dateTimeBetween('8:00:00', '10:00:00');
        $clockOut = $this->faker->dateTimeBetween('18:00:00', '20:00:00');

        // 意味のある備考理由の配列
        $memoReasons = [
            '電車遅延',
            '体調不良',
            '家族の急用',
            '医者に行った',
            '打ち合わせが長引いた',
            'システム障害対応',
            '緊急対応',
            '研修参加',
            '出張',
            '残業',
            '早退',
            '通勤事故',
            '天候不良',
            '交通渋滞',
            '会議延長',
            'クライアント対応',
            '電車の遅延',
            'バスが遅れた',
            '車の故障',
            '道路工事'
        ];

        return [
            'user_id' => User::factory(),
            'date' => $this->faker->date(),
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
            'memo' => $this->faker->optional(0.3)->randomElement($memoReasons),
        ];
    }
}