<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeacherBookingController extends Controller
{
    /**
     * إرجاع بيانات تجريبية مع بيانات المعلّم الحالي
     */
    public function index(Request $request)
    {
        $user = $request->user(); // نفس auth()->user()

        return response()->json([
            'message' => 'Teacher bookings test endpoint is working',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            // لو عندك علاقة roles هترجع أسمائها، ولو مش موجودة مش هتعمل مشكلة
            'roles'   => method_exists($user, 'roles')
                ? $user->roles->pluck('name')
                : [],

            // بيانات تجريبية للحجوزات
            'bookings' => [
                [
                    'id'           => 1,
                    'subject'      => 'Math',
                    'status'       => 'pending',
                    'scheduled_at' => now()->addDay()->toDateTimeString(),
                ],
            ],
        ]);
    }
}
