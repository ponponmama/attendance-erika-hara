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

        return [
            'user_id' => User::factory(),
            'date' => $this->faker->date(),
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
            'memo' => $this->faker->optional()->realText(50),
        ];
    }
}