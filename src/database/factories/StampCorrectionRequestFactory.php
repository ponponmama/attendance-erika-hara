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
            'user_id' => null, // Seederで指定
            'attendance_id' => null, // Seederで指定
            'approved_by' => $approved_by,
            'request_date' => null, // Seederで指定（ランダム生成を無効化）
            'correction_type' => $this->faker->randomElement(['clock_in', 'clock_out', 'break_start', 'break_end']),
            'current_time' => $this->faker->optional(0.7)->time('H:i:s'),
            'requested_time' => $this->faker->time('H:i:s'),
            'reason' => null, // Seederで指定（ランダム生成を無効化）
            'status' => $status,
            'approved_at' => $approved_at,
        ];
    }
}
