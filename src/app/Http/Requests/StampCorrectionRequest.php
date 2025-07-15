<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class StampCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'memo' => ['required', 'string'],
        ];

        // 休憩時間のバリデーションルールを動的に追加
        for ($i = 0; $i <= 10; $i++) {
            $rules["break_start_{$i}"] = ['nullable', 'date_format:H:i'];
            $rules["break_end_{$i}"] = ['nullable', 'date_format:H:i'];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'clock_in.date_format' => '出勤時間の形式が正しくありません',
            'clock_out.date_format' => '退勤時間の形式が正しくありません',
            'memo.required' => '備考を記入してください',
        ];
    }

    /**
     * カスタムバリデーション
     */
    public function withValidator($validator)
    {
        Log::info('withValidator called');
        $validator->after(function ($validator) {
            Log::info('validator->after called');
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            // 1. 出勤時間が退勤時間より後、または退勤時間が出勤時間より前の場合
            if ($clockIn && $clockOut) {
                if ($clockIn >= $clockOut) {
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            // 2. 休憩時間が勤務時間外の場合（フォームで送信された値のみチェック）
            for ($i = 0; $i <= 10; $i++) {
                $breakStart = $this->input("break_start_{$i}");
                $breakEnd = $this->input("break_end_{$i}");

                // デバッグ情報
                Log::info("Break {$i}: start={$breakStart}, end={$breakEnd}");

                // 休憩開始・終了の両方が入力されている場合のみチェック
                if ($breakStart && $breakEnd) {
                    // 休憩開始が休憩終了より後
                    if ($breakStart >= $breakEnd) {
                        Log::info("Validation error: break_start_{$i} >= break_end_{$i}");
                        $validator->errors()->add("break_start_{$i}", '出勤時間もしくは退勤時間が不適切な値です');
                    }

                    // 休憩時間が勤務時間外（出勤時間より前）
                    if ($clockIn && $breakStart < $clockIn) {
                        Log::info("Validation error: break_start_{$i} < clock_in");
                        $validator->errors()->add("break_start_{$i}", '出勤時間もしくは退勤時間が不適切な値です');
                    }

                    // 休憩時間が勤務時間外（退勤時間より後）
                    if ($clockOut && $breakEnd > $clockOut) {
                        Log::info("Validation error: break_end_{$i} > clock_out");
                        $validator->errors()->add("break_end_{$i}", '出勤時間もしくは退勤時間が不適切な値です');
                    }

                    // 休憩開始時間が退勤時間より後の場合
                    if ($clockOut && $breakStart > $clockOut) {
                        Log::info("Validation error: break_start_{$i} > clock_out");
                        $validator->errors()->add("break_start_{$i}", '出勤時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}
