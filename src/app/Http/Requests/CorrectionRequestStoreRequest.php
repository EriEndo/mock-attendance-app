<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Models\Attendance;

class CorrectionRequestStoreRequest extends FormRequest
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

    public function rules()
    {
        return [
            'clock_in_at' => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i'],
            'note' => ['required', 'string'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages()
    {
        return [
            'clock_in_at.required' => '出勤時間を入力してください',
            'clock_in_at.date_format' => '出勤時間を正しく入力してください',

            'clock_out_at.required' => '退勤時間を入力してください',
            'clock_out_at.date_format' => '退勤時間を正しく入力してください',

            'note.required' => '備考を記入してください',

            'breaks.*.start.date_format' => '休憩開始時間を正しく入力してください',
            'breaks.*.end.date_format' => '休憩終了時間を正しく入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in_at');
            $clockOut = $this->input('clock_out_at');
            $breaks = $this->input('breaks', []);
            $note = $this->input('note');
            $attendance = $this->getAttendance();
            if (!$attendance) {
                return;
            }

            if ($clockIn && $clockOut && $clockIn >= $clockOut) {
                $validator->errors()->add(
                    'clock_in_at',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            $validBreaks = [];

            foreach ($breaks as $index => $break) {
                $breakStart = $break['start'] ?? null;
                $breakEnd = $break['end'] ?? null;

                if (($breakStart && !$breakEnd) || (!$breakStart && $breakEnd)) {
                    $validator->errors()->add(
                        "breaks.$index.start",
                        '休憩時間が不適切な値です'
                    );
                    continue;
                }

                if (!$breakStart && !$breakEnd) {
                    continue;
                }
                if ($breakStart >= $breakEnd) {
                    $validator->errors()->add(
                        "breaks.$index.start",
                        '休憩時間が不適切な値です'
                    );
                    continue;
                }

                if (($clockIn && $breakStart < $clockIn) || ($clockOut && $breakStart > $clockOut)) {
                    $validator->errors()->add(
                        "breaks.$index.start",
                        '休憩時間が不適切な値です'
                    );
                }

                if ($clockOut && $breakEnd > $clockOut) {
                    $validator->errors()->add(
                        "breaks.$index.end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }

                $validBreaks[] = [
                    'index' => $index,
                    'start' => $breakStart,
                    'end' => $breakEnd,
                ];
            }

            for ($i = 0; $i < count($validBreaks); $i++) {
                for ($j = $i + 1; $j < count($validBreaks); $j++) {
                    $first = $validBreaks[$i];
                    $second = $validBreaks[$j];

                    if ($first['start'] < $second['end'] && $second['start'] < $first['end']) {
                        $validator->errors()->add(
                            "breaks.{$first['index']}.start",
                            '休憩時間が不適切な値です'
                        );

                        $validator->errors()->add(
                            "breaks.{$second['index']}.start",
                            '休憩時間が不適切な値です'
                        );
                    }
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }


            $originalClockIn = optional($attendance->clock_in_at)->format('H:i');
            $originalClockOut = optional($attendance->clock_out_at)->format('H:i');

            $originalBreaks = $attendance->breakTimes
                ->map(function ($break) {
                    return [
                        'start' => optional($break->break_start_at)->format('H:i'),
                        'end' => optional($break->break_end_at)->format('H:i'),
                    ];
                })
                ->filter(function ($break) {
                    return $break['start'] || $break['end'];
                })
                ->values()
                ->toArray();

            $inputBreaks = collect($breaks)
                ->map(function ($break) {
                    return [
                        'start' => $break['start'] ?? null,
                        'end' => $break['end'] ?? null,
                    ];
                })
                ->filter(function ($break) {
                    return $break['start'] || $break['end'];
                })
                ->values()
                ->toArray();

            $isSame =
                $clockIn === $originalClockIn &&
                $clockOut === $originalClockOut &&
                $inputBreaks == $originalBreaks;

            if ($isSame) {
                $validator->errors()->add('no_change', '修正箇所がありません');
            }
        });
    }
    protected function getAttendance(): ?Attendance
    {
        $attendance = $this->route('attendance');

        if ($attendance instanceof Attendance) {
            return $attendance;
        }

        $id = $this->route('id');

        if ($id) {
            return Attendance::with('breakTimes')->find($id);
        }

        return null;
    }
}
