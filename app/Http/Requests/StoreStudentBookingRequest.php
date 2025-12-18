<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_profile_id' => 'required|integer|exists:teacher_profiles,id',

            // ✅ مؤقتًا بدون exists عشان نعدّي مرحلة التجربة
            'lesson_delivery_mode_id' => 'required|integer',
            'city_id' => 'required|integer',

            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3', // مثال: AED, USD
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
