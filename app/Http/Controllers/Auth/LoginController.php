<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * عرض نموذج تسجيل الدخول
     */
    public function showLoginForm()
    {
        // 👈 نخليها بسيطة: دايمًا ترجع صفحة اللوجين
        // من غير أي Redirect عشان مايحصلش Loop
        return view('auth.login');
    }

    /**
     * تنفيذ عملية تسجيل الدخول
     */
    public function login(Request $request)
    {
        // 1) التحقق من البيانات
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        // 2) محاولة تسجيل الدخول
        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'بيانات الدخول غير صحيحة.'])
                ->withInput($request->only('email'));
        }

        // 3) حماية السيشن
        $request->session()->regenerate();

        $user = Auth::user();

        // 4) توجيه حسب نوع المستخدم
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'teacher') {
            $profile = $user->teacherProfile;

            // لو مفيش بروفايل أو الحساب مش مفعَّل → يروّح يعدّل البروفايل
            if (
                !$profile ||
                $profile->account_status !== TeacherProfile::STATUS_APPROVED
            ) {
                return redirect()->route('teacher.profile.edit')
                    ->with('success', 'برجاء استكمال بيانات ملفك، وسيتم تفعيل الحساب بعد مراجعة الإدارة.');
            }

            // معلّم وحسابه مفعَّل
            return redirect()->route('teacher.dashboard');
        }

        if ($user->role === 'student') {
            return redirect()->route('student.dashboard');
        }

        // لو لأي سبب نوع المستخدم مختلف
        return redirect()->to('/');
    }

    /**
     * تسجيل خروج
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
