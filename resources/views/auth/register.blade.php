<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>إنشاء حساب جديد</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow-sm" style="max-width: 460px; width: 100%;">
        <div class="card-body p-4">
            <h3 class="mb-3 text-center">إنشاء حساب جديد</h3>
            <p class="text-muted text-center mb-4">
                من فضلك أدخل بياناتك واختر نوع الحساب.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}">
                @csrf

                {{-- الاسم --}}
                <div class="mb-3">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           class="form-control"
                           required>
                </div>

                {{-- البريد --}}
                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control"
                           required>
                </div>

               {{-- نوع الحساب --}}
<div class="mb-3">
    <label class="form-label d-block">نوع الحساب</label>

    {{-- طالب / ولي أمر --}}
    <div class="form-check">
        <input class="form-check-input"
               type="radio"
               name="role"
               id="role_student"
               value="student"
               {{ old('role', 'student') === 'student' ? 'checked' : '' }}>
        <label class="form-check-label" for="role_student">
            طالب / ولي أمر
        </label>
    </div>

    {{-- معلّم --}}
    <div class="form-check">
        <input class="form-check-input"
               type="radio"
               name="role"
               id="role_teacher"
               value="teacher"
               {{ old('role') === 'teacher' ? 'checked' : '' }}>
        <label class="form-check-label" for="role_teacher">
            معلّم
        </label>
    </div>
</div>


                {{-- كلمة المرور --}}
                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password"
                           name="password"
                           class="form-control"
                           required>
                </div>

                {{-- تأكيد كلمة المرور --}}
                <div class="mb-4">
                    <label class="form-label">تأكيد كلمة المرور</label>
                    <input type="password"
                           name="password_confirmation"
                           class="form-control"
                           required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    إنشاء الحساب
                </button>
            </form>

            <div class="mt-3 text-center">
                <small>
                    لديك حساب بالفعل؟
                    <a href="{{ route('login') }}">تسجيل الدخول</a>
                </small>
            </div>
        </div>
    </div>
</div>

</body>
</html>
