<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StampCorrectionRequest>
 */
class StampCorrectionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $status = $this->faker->randomElement(['pending', 'approved', 'rejected']);
        $approved_at = ($status !== 'pending') ? $this->faker->dateTimeThisMonth() : null;
        $approved_by = ($status !== 'pending') ? User::factory() : null;

        return [
            'user_id' => User::factory(),
            'attendance_id' => Attendance::factory(),
            'approved_by' => $approved_by,
            'request_date' => $this->faker->date(),
            'correction_type' => $this->faker->randomElement(['clock_in', 'clock_out', 'break_start', 'break_end']),
            'current_time' => $this->faker->optional()->time('H:i:s'),
            'requested_time' => $this->faker->time('H:i:s'),
            'reason' => $this->faker->realText(100),
            'status' => $status,
            'approved_at' => $approved_at,
        ];
    }
}