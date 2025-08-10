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
        return [
            'attendance_id' => null, // Seederで指定
            'break_start' => '12:00:00', // デフォルト値（withinWorkHoursで上書きされる）
            'break_end' => '13:00:00', // デフォルト値（withinWorkHoursで上書きされる）
        ];
    }

    /**
     * 勤務時間内に収まる休憩時間を生成
     */
    public function withinWorkHours(Attendance $attendance)
    {
        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::parse($attendance->clock_out);

        // 勤務時間の長さを計算（分単位）
        $workDuration = $clockIn->diffInMinutes($clockOut);

        // 勤務時間が短すぎる場合は休憩なし
        if ($workDuration < 240) { // 4時間未満
            return $this->state([
                'break_start' => null,
                'break_end' => null,
            ]);
        }

        // 休憩パターンを勤務時間に応じて設定
        $breakPatterns = [];

        // 4時間以上: 昼休憩のみ
        if ($workDuration >= 240) {
            $breakPatterns[] = [
                'type' => 'lunch',
                'start_min_offset' => 180, // 出勤から3時間後
                'start_max_offset' => 240, // 出勤から4時間後
                'duration_min' => 30,
                'duration_max' => 60,
            ];
        }

        // 6時間以上: 午前休憩も追加
        if ($workDuration >= 360) {
            $breakPatterns[] = [
                'type' => 'morning',
                'start_min_offset' => 90, // 出勤から1.5時間後
                'start_max_offset' => 150, // 出勤から2.5時間後
                'duration_min' => 10,
                'duration_max' => 20,
            ];
        }

        // 8時間以上: 午後休憩も追加
        if ($workDuration >= 480) {
            $breakPatterns[] = [
                'type' => 'afternoon',
                'start_min_offset' => 300, // 出勤から5時間後
                'start_max_offset' => 360, // 出勤から6時間後
                'duration_min' => 10,
                'duration_max' => 20,
            ];
        }

        $pattern = $this->faker->randomElement($breakPatterns);

        // 休憩開始時間を勤務時間内で設定
        $startOffset = $this->faker->numberBetween(
            $pattern['start_min_offset'],
            $pattern['start_max_offset']
        );

        $breakStart = (clone $clockIn)->addMinutes($startOffset);

        // 退勤時間を超えないように調整
        if ($breakStart >= $clockOut) {
            $breakStart = (clone $clockOut)->subMinutes(30);
        }

        // 休憩時間を設定
        $duration = $this->faker->numberBetween(
            $pattern['duration_min'],
            $pattern['duration_max']
        );

        $breakEnd = (clone $breakStart)->addMinutes($duration);

        // 退勤時間を超えないように調整
        if ($breakEnd > $clockOut) {
            $breakEnd = clone $clockOut;
            $breakStart = (clone $breakEnd)->subMinutes($duration);
        }

        return $this->state([
            'break_start' => $breakStart->format('H:i:s'),
            'break_end' => $breakEnd->format('H:i:s'),
        ]);
    }
}
