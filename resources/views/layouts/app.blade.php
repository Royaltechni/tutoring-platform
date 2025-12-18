<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <title>Admin Panel</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ✅ مهم لطلبات fetch/AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    {{-- ✅ (جديد) Stack إضافي لأي حاجات لازم تتحط في <head> من صفحات معينة (مثل Zoom CSS/SDK head) --}}
    @stack('head')

    {{-- ✅ مهم: لتفعيل @push('styles') في صفحات الأدمن --}}
    @stack('styles')

    <style>
        /* ✅ يمنع “التحرك” بسبب ظهور/اختفاء الـ Scrollbar بين الصفحات */
        html { overflow-y: scroll; }

        body {
            background: #f7f7f7;
        }

        /* ------- Sidebar على اليسار ------- */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #1e1e2d;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
            box-sizing: border-box;
        }

        /* ✅ تثبيت عنوان Admin Panel ناحية اليسار */
        .sidebar h4 {
            font-size: 20px;
            margin-bottom: 24px;

            direction: ltr;
            text-align: left;
            white-space: nowrap;
        }

        .sidebar a {
            color: white;
            display: block;
            padding: 10px 12px;
            border-radius: 6px;
            text-decoration: none;
            margin-bottom: 8px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .sidebar a:hover {
            background: #343454;
        }

        .main-content {
            margin-left: 260px;
        }

        /* ------- Top Navbar ------- */
        .top-navbar {
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #ddd;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            gap: 16px;

            direction: ltr; /* ✅ تثبيت ترتيب العناصر: العنوان يسار + الأيقونات يمين */
        }

        .top-navbar-title {
            font-size: 18px;
            font-weight: 600;

            margin-right: auto; /* ✅ يثبت العنوان ناحية اليسار */
            direction: rtl;     /* ✅ العنوان عربي طبيعي */
            text-align: left;
        }

        .top-navbar-right {
            margin-left: auto; /* ✅ يثبت الأفاتار/الإشعارات ناحية اليمين */
            display: flex;
            align-items: center;
            gap: 12px;

            direction: ltr; /* ✅ ترتيب الأيقونات ثابت */
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar,
        .user-avatar-img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            background: #4285f4;
            color: #fff;
            cursor: pointer;
        }

        .user-avatar-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ------- Dark Mode ------- */
        body.dark-mode {
            background: #101018;
            color: #e5e5e5;
        }

        body.dark-mode .sidebar {
            background: #151521;
        }

        body.dark-mode .top-navbar {
            background: #1f1f2f;
            border-bottom-color: #333;
        }

        body.dark-mode .card {
            background: #1e1e2d;
            color: #e5e5e5;
            border-color: #333;
        }

        body.dark-mode table {
            color: #e5e5e5;
        }

        body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * {
            color: #e5e5e5;
        }

        body.dark-mode .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }

        body.dark-mode .btn-outline-secondary {
            border-color: #555;
            color: #ddd;
        }

        body.dark-mode .btn-outline-secondary:hover {
            background: #555;
            color: #fff;
        }

        /* =========================
           ✅ تثبيت مكان الأيقونات (شمال) داخل السايدبار + ستايل Active ثابت
           ========================= */
        .sidebar a.nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            direction: ltr; /* يثبت الأيقونة شمال */
        }

        .sidebar a.nav-item .nav-icon {
            width: 22px;
            text-align: center;
            flex: 0 0 22px;
        }

        .sidebar a.nav-item .nav-text {
            flex: 1;
            text-align: left;
        }

        /* ✅ Active ثابت بدون Bootstrap bg-light (عشان مايحصلش أي “إحساس” بتغير المقاس) */
        .sidebar a.nav-item.active {
            background: #ffffff;
            color: #111827;
        }

        body.dark-mode .sidebar a.nav-item.active {
            background: #2b2b3d;
            color: #fff;
        }
    </style>
</head>

<body>
@php
    $user = auth()->user();
    $name = $user?->name ?? 'User';

    $parts = preg_split('/\s+/u', trim($name));
    $initials = '';
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= mb_substr($p, 0, 1);
        }
        if (mb_strlen($initials) >= 2) break;
    }
    if ($initials === '') {
        $initials = 'U';
    }

    $avatarPath = $user->profile_photo_path ?? null;
    $avatarUrl  = $avatarPath ? asset('storage/'.$avatarPath) : null;
@endphp

<div class="d-flex">

    {{-- Sidebar --}}
    <div class="sidebar text-white">
        <h4 class="mb-4">Admin Panel</h4>

        @php
            $isAdminDashboard = request()->routeIs('admin.dashboard');
            $isAdminBookings  = request()->routeIs('admin.bookings.*');
            $isAdminTeachers  = request()->routeIs('admin.teachers.*');
            $isAdminStudents  = request()->routeIs('admin.students.*');
        @endphp

        <a href="{{ route('admin.dashboard') }}"
           class="nav-item {{ $isAdminDashboard ? 'active' : '' }}">
            <span class="nav-icon">🏠</span>
            <span class="nav-text">Dashboard</span>
        </a>

        <a href="{{ route('admin.bookings.index') }}"
           class="nav-item {{ $isAdminBookings ? 'active' : '' }}">
            <span class="nav-icon">📅</span>
            <span class="nav-text">Bookings</span>
        </a>

        <a href="{{ route('admin.teachers.index') }}"
           class="nav-item {{ $isAdminTeachers ? 'active' : '' }}">
            <span class="nav-icon">👨‍🏫</span>
            <span class="nav-text">Teachers</span>
        </a>

        <a href="{{ route('admin.students.index') }}"
           class="nav-item {{ $isAdminStudents ? 'active' : '' }}">
            <span class="nav-icon">👨‍🎓</span>
            <span class="nav-text">Students</span>
        </a>
    </div>

    {{-- Main Content --}}
    <div class="main-content flex-fill">

        <div class="top-navbar">

            <div class="top-navbar-title">
                @yield('page_title', 'لوحة التحكم')
            </div>

            <div class="top-navbar-right">

                <button type="button"
                        class="btn btn-outline-secondary btn-sm position-relative"
                        title="الإشعارات">
                    🔔
                    <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger"
                          style="font-size: 10px;">
                        0
                    </span>
                </button>

                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        id="toggleDarkMode"
                        title="الوضع الليلي">
                    🌙
                </button>

                <div class="user-box dropdown">

                    @if($avatarUrl)
                        <div class="user-avatar-img"
                             id="userMenu"
                             data-bs-toggle="dropdown"
                             aria-expanded="false">
                            <img src="{{ $avatarUrl }}" alt="Avatar">
                        </div>
                    @else
                        <div class="user-avatar"
                             id="userMenu"
                             data-bs-toggle="dropdown"
                             aria-expanded="false">
                            {{ mb_strtoupper($initials) }}
                        </div>
                    @endif

                    <span>{{ $name }}</span>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li class="dropdown-item text-muted" style="font-size: 13px;">
                            {{ $user?->email }}
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger">تسجيل الخروج</button>
                            </form>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        <div class="p-4">
            @yield('content')
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- ✅ (جديد) Stack لسكربتات Vendor الخاصة بصفحات معينة فقط (مثل تحميل Zoom SDK) --}}
@stack('vendor_scripts')

<script>
    /**
     * ✅ (جديد) Zoom SDK Loader (بدون ما نحمله على كل الصفحات)
     * الصفحة اللي محتاجاه (room.blade.php) هي اللي هتستدعي loadZoomSdk(...)
     */
    window.loadZoomSdk = function (srcUrl) {
        return new Promise(function (resolve, reject) {
            if (!srcUrl) {
                reject(new Error('Zoom SDK URL is missing'));
                return;
            }

            // لو ات加载 قبل كده
            if (window.__zoomSdkLoaded) {
                resolve(true);
                return;
            }

            var existing = document.querySelector('script[data-zoom-sdk="1"]');
            if (existing) {
                existing.addEventListener('load', function () {
                    window.__zoomSdkLoaded = true;
                    resolve(true);
                });
                existing.addEventListener('error', function () {
                    reject(new Error('Failed to load Zoom SDK script'));
                });
                return;
            }

            var s = document.createElement('script');
            s.src = srcUrl;
            s.async = true;
            s.defer = true;
            s.setAttribute('data-zoom-sdk', '1');

            s.onload = function () {
                window.__zoomSdkLoaded = true;
                resolve(true);
            };

            s.onerror = function () {
                reject(new Error('Failed to load Zoom SDK script'));
            };

            document.body.appendChild(s);
        });
    };
</script>

{{-- ✅ مهم: لتفعيل @push('scripts') في صفحات الأدمن --}}
@stack('scripts')

<script>
    (function () {
        const body = document.body;
        const stored = localStorage.getItem('tp_dark_mode');
        if (stored === 'on') {
            body.classList.add('dark-mode');
        }

        const btn = document.getElementById('toggleDarkMode');
        if (btn) {
            btn.addEventListener('click', function () {
                body.classList.toggle('dark-mode');
                localStorage.setItem(
                    'tp_dark_mode',
                    body.classList.contains('dark-mode') ? 'on' : 'off'
                );
            });
        }
    })();
</script>

</body>
</html>
