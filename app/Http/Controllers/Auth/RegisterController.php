<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * عرض صفحة إنشاء الحساب
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * تنفيذ عملية التسجيل
     */
    public function register(Request $request)
    {
        // ✅ التحقق من البيانات
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:6'],
            // نوع الحساب: إما student (طالب/ولي أمر) أو teacher (معلّم)
            'role'     => ['required', 'in:student,teacher'],
        ]);

        // ✅ إنشاء المستخدم
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
        ]);

        // ✅ تسجيل الدخول مباشرة بعد التسجيل
        Auth::login($user);

        // ✅ توجيه المستخدم حسب نوع الحساب
        if ($user->role === 'teacher') {
            // معلّم → لوحة تحكم المعلّم
            return redirect()->route('teacher.dashboard');
        }

        // طالب / ولي أمر → لوحة تحكم الطالب
        return redirect()->route('student.dashboard');
    }
}
