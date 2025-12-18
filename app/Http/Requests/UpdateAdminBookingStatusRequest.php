<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminBookingStatusRequest extends FormRequest
{
    /**
     * السماح بتنفيذ الطلب
     */
    public function authorize(): bool
    {
        // لاحقًا ممكن نضيف تحقق من أن المستخدم Admin
        return true;
    }

    /**
     * قواعد التحقق من بيانات الطلب
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,confirmed,cancelled',
        ];
    }

    /**
     * رسائل مخصصة (اختياري)
     */
    public function messages(): array
    {
        return [
            'status.required' => 'حقل الحالة مطلوب.',
            'status.in' => 'القيمة المسموح بها للحالة هي: pending, confirmed, cancelled.',
        ];
    }
}
