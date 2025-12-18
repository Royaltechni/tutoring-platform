<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherMiddleware
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

        // لو المستخدم مش معلّم → رجّعه للداشبورد الخاص به
        if ($user->role !== 'teacher') {

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user->role === 'student') {
                return redirect()->route('student.dashboard');
            }

            return redirect()->route('home');
        }

        return $next($request);
    }
}
