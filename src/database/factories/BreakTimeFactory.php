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
        // 休憩パターンを現実的に
        $breakPatterns = [
            // 午前休憩: 10:00〜10:59開始、15分
            [
                'start_min' => '10:00',
                'start_max' => '10:59',
                'duration_min' => 15,
                'duration_max' => 15,
            ],
            // 昼休憩: 12:00〜12:30開始、45〜60分
            [
                'start_min' => '12:00',
                'start_max' => '12:30',
                'duration_min' => 45,
                'duration_max' => 60,
            ],
            // 午後休憩: 15:00〜15:59開始、15〜20分
            [
                'start_min' => '15:00',
                'start_max' => '15:59',
                'duration_min' => 15,
                'duration_max' => 20,
            ],
        ];
        $pattern = $this->faker->randomElement($breakPatterns);
        $start = $this->faker->dateTimeBetween($pattern['start_min'], $pattern['start_max']);
        $duration = $this->faker->numberBetween($pattern['duration_min'], $pattern['duration_max']);
        $breakStart = Carbon::instance($start);
        $breakEnd = (clone $breakStart)->addMinutes($duration);
        return [
            'attendance_id' => null, // シーダーで指定
            'break_start' => $breakStart->format('H:i:s'),
            'break_end' => $breakEnd->format('H:i:s'),
        ];
    }
}