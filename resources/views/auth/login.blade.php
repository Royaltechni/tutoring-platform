<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تسجيل الدخول</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <h3 class="mb-3 text-center">تسجيل الدخول</h3>
            <p class="text-muted text-center mb-4">
                أدخل البريد الإلكتروني وكلمة المرور الخاصة بك.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control"
                           required
                           autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password"
                           name="password"
                           class="form-control"
                           required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox"
                           name="remember"
                           class="form-check-input"
                           id="remember">
                    <label class="form-check-label" for="remember">
                        تذكّرني
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    دخول
                </button>
            </form>
        </div>
    </div>
</div>
<div class="mt-3 text-center">
    <small>
        ليس لديك حساب؟
        <a href="{{ route('register') }}">أنشئ حسابًا جديدًا</a>
    </small>
</div>


</body>
</html>
