<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TeacherProfile;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_profile_id' => 'required|exists:teacher_profiles,id',
            'lesson_delivery_mode_id' => 'required|exists:lesson_delivery_modes,id',
            'city_id' => 'nullable|exists:cities,id',
            'scheduled_start_at' => 'required|date|after:now',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $teacherId = $this->input('teacher_profile_id');
            $modeId = $this->input('lesson_delivery_mode_id');
            $cityId = $this->input('city_id');

            $teacher = TeacherProfile::find($teacherId);

            if (!$teacher) {
                return;
            }

            $mode = $teacher->deliveryModes()
                ->where('lesson_delivery_modes.id', $modeId)
                ->first();

            if (!$mode || !$mode->pivot->is_active) {
                $validator->errors()->add(
                    'lesson_delivery_mode_id',
                    'This teacher does not offer the selected delivery mode.'
                );
            }

            if ($mode && $mode->code !== 'online') {
                if (!$cityId) {
                    $validator->errors()->add('city_id', 'City is required for onsite lessons.');
                } else {
                    $coversCity = $teacher->cities()->where('cities.id', $cityId)->exists();
                    if (!$coversCity) {
                        $validator->errors()->add('city_id', 'The teacher does not cover this city.');
                    }
                }
            }
        });
    }
}
