<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherProfileReviewed
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // لو مش مسجّل دخول أو مش معلّم → كمل عادي
        if (!$user || $user->role !== 'teacher') {
            return $next($request);
        }

        // نسمّي بعض المسارات اللي هنستثنيها عشان مايحصلش loop
        $routeName = $request->route()?->getName();

        $isProfileRoute = in_array($routeName, [
            'teacher.profile.edit',
            'teacher.profile.update',
        ]);

        // لو معلّم وحسابه مش approved
        if ($user->teacher_status !== 'approved' && !$isProfileRoute) {

            // رسالة بسيطة توضح له الوضع
            session()->flash('info', 'حسابك حاليًا قيد المراجعة من إدارة المنصّة، برجاء استكمال بيانات ملفك وانتظار التفعيل.');

            // تحويل إجباري لصفحة تعديل البروفايل
            return redirect()->route('teacher.profile.edit');
        }

        return $next($request);
    }
}
