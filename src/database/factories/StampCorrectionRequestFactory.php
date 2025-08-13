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
        $approved_by = ($status === 'approved') ? User::where('role', 'admin')->first()->id ?? null : null;

        $correctionType = $this->faker->randomElement(['clock_in', 'clock_out', 'break_start', 'break_end']);
        $currentTime = $this->faker->time('H:i');
        $requestedTime = $this->faker->time('H:i');

        return [
            'user_id' => null, // Seederで指定
            'attendance_id' => null, // Seederで指定
            'approved_by' => $approved_by,
            'request_date' => now(), // デフォルトで現在時刻を設定
            'correction_type' => $correctionType,
            'correction_data' => [
                $correctionType => [
                    'current' => $currentTime,
                    'requested' => $requestedTime
                ]
            ],
            'current_time' => $currentTime,
            'requested_time' => $requestedTime,
            'reason' => null, // Seederで指定（ランダム生成を無効化）
            'status' => $status,
            'approved_at' => $approved_at,
        ];
    }
}
