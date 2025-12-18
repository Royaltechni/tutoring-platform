<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StudentController extends Controller
{
    /**
     * عرض قائمة الطلاب (حسابات role = student)
     */
    public function index()
    {
        $students = User::where('role', 'student')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.students.index', compact('students'));
    }
}
