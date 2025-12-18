<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // لو مش مسجّل دخول → رجّعه لصفحة اللوجين
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // لو المستخدم مش أدمن → رجّعه للداشبورد الخاص به
        if ($user->role !== 'admin') {

            if ($user->role === 'teacher') {
                return redirect()->route('teacher.dashboard');
            }

            if ($user->role === 'student') {
                return redirect()->route('student.dashboard');
            }

            // أي رول تاني (لو حصل)
            return redirect()->route('home');
        }

        // لو كل شيء تمام → كمّل الطلب عادي
        return $next($request);
    }
}
