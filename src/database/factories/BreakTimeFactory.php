<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $breakStart = $this->faker->dateTimeBetween('12:00:00', '13:00:00');
        $breakEnd = (clone $breakStart)->modify('+1 hour');

        return [
            'attendance_id' => Attendance::factory(),
            'break_start' => $breakStart->format('H:i:s'),
            'break_end' => $breakEnd->format('H:i:s'),
        ];
    }
}