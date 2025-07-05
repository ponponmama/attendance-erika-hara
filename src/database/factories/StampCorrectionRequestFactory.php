<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Database\Factories\ReasonList;

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
        $status = $this->faker->randomElement(['pending', 'approved']);
        $approved_at = ($status === 'approved') ? $this->faker->dateTimeThisMonth() : null;
        $approved_by = ($status === 'approved') ? User::where('role', 'admin')->first()->id ?? 1 : null;

        return [
            'user_id' => null, // シーダーで指定される
            'attendance_id' => null, // シーダーで指定される
            'approved_by' => $approved_by,
            'request_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
            'correction_type' => $this->faker->randomElement(['clock_in', 'clock_out', 'break_start', 'break_end']),
            'current_time' => $this->faker->optional(0.7)->time('H:i:s'),
            'requested_time' => $this->faker->time('H:i:s'),
            'reason' => $this->faker->randomElement(ReasonList::REASONS),
            'status' => $status,
            'approved_at' => $approved_at,
        ];
    }
}
