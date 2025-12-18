<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // هنسيبها true دلوقتي، بما إن عندنا auth:sanctum على الroute
        return true;
    }

    public function rules(): array
    {
        return [
            // الحالات المسموحة مبدئيًا
            'status' => 'required|string|in:pending,confirmed,cancelled',
        ];
    }
}
