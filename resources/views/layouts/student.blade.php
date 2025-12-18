<!DOCTYPE html>
@php
    $locale = app()->getLocale(); // ar أو en
    $isRtl  = $locale === 'ar';
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    
<head>
    <meta charset="utf-8">
    <title>@yield('page_title', 'Student Panel')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    {{-- ✅ Bootstrap RTL لو اللغة عربية --}}
    @if($isRtl)
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif

    <style>
        html { overflow-y: scroll; }
        body { background: #f7f7f7; margin: 0; overflow-x: hidden; }

        :root{
            --student-sidebar-w: 240px;
            --student-gap: 20px;
            --student-main-offset: calc(var(--student-sidebar-w) + var(--student-gap));
        }

        /* ====== Sidebar ثابتة ====== */
        .student-sidebar {
            width: var(--student-sidebar-w);
            min-height: 100vh;
            background: #16a34a;
            padding: 20px;
            position: fixed;
            top: 0;

            /* ✅ الوضع الافتراضي LTR */
            left: 0;
            right: auto;

            box-sizing: border-box;
            color: #fff;
            z-index: 99999;
        }

        .student-sidebar h4{
            font-size: 20px;
            margin-bottom: 24px;
            white-space: nowrap;
        }

        /* روابط السايدبار */
        .student-sidebar a.nav-item{
            display: flex;
            align-items: center;
            gap: 10px;

            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            text-decoration: none;

            color: #eafff0;
            box-sizing: border-box;

            position: relative;
            z-index: 100000;
            cursor: pointer;
        }

        .student-sidebar a.nav-item:hover{
            background: rgba(255,255,255,.15);
            color: #fff;
        }

        .student-sidebar a.nav-item.active{
            background: #ffffff;
            color: #14532d;
        }

        .student-sidebar .nav-icon{
            width: 22px;
            text-align: center;
            flex: 0 0 22px;
        }
        .student-sidebar .nav-text{
            flex: 1;
        }

        /* ====== Main Content ثابت مع السايدبار ====== */
        .student-main{
            /* ✅ الوضع الافتراضي LTR */
            margin-left: var(--student-main-offset);
            margin-right: 0;

            width: calc(100% - var(--student-main-offset));
            min-height: 100vh;

            position: relative;
            z-index: 1;
        }

        /* ====== Top Navbar ====== */
        .top-navbar{
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #ddd;
            padding: 10px 24px;

            display: flex;
            align-items: center;
            gap: 16px;

            position: relative;
            z-index: 2;
        }

        .top-navbar-title{
            font-size: 18px;
            font-weight: 700;
            white-space: nowrap;
        }

        .top-navbar-right{
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            background-color: #14532d;
            color: #fff;
            flex: 0 0 34px;
        }

        /* ✅ نمنع underline على زر الحساب */
        .user-menu-btn{
            text-decoration: none !important;
        }

        /* =========================================================
           ✅ Fix modals stacking
        ========================================================= */
        .modal{
            position: fixed !important;
            inset: 0 !important;
            z-index: 200000 !important;
            pointer-events: auto !important;
        }
        .modal.show{ z-index: 200000 !important; pointer-events: auto !important; }

        .modal-backdrop{
            position: fixed !important;
            inset: 0 !important;
            z-index: 199999 !important;
        }
        .modal-backdrop.show{ z-index: 199999 !important; }

        .modal .modal-dialog{
            position: relative !important;
            z-index: 200001 !important;
            pointer-events: auto !important;
        }
        .modal .modal-content{
            z-index: 200002 !important;
            pointer-events: auto !important;
            opacity: 1 !important;
            filter: none !important;

            border: 0;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }

        .notif-badge{
            position: absolute;
            top: -6px;
            right: -6px;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 999px;
        }
        .notif-item{ white-space: normal; line-height: 1.2; }
        .notif-time{ font-size: 12px; }

        @media (max-width: 991px){
            .student-main{
                width: calc(100% - var(--student-main-offset));
            }
        }

        /* =========================================================
           ✅ RTL Overrides
        ========================================================= */
        html[dir="rtl"] .student-sidebar{
            left: auto;
            right: 0;
        }
        html[dir="rtl"] .student-main{
            margin-left: 0;
            margin-right: var(--student-main-offset);
        }

        html[dir="rtl"] .student-sidebar h4{ text-align: right; }
        html[dir="rtl"] .student-sidebar .nav-text{ text-align: right; }

        html[dir="rtl"] .top-navbar{ direction: rtl; }
        html[dir="rtl"] .top-navbar-right{
            margin-left: 0;
            margin-right: auto;
        }
        html[dir="rtl"] .top-navbar-title{ text-align: right; }
    </style>

    @stack('styles')
</head>
<body>

@php
    $user = auth()->user();
    $userName = $user?->name ?? ($isRtl ? 'طالب' : 'Student');
    $userInitial = strtoupper(mb_substr($userName, 0, 1));

    $unreadCount = $user?->unreadNotifications()->count() ?? 0;
    $latestNotifs = $user?->notifications()->latest()->limit(5)->get() ?? collect();

    // ✅ محاذاة الـ dropdown حسب الاتجاه
    // في RTL الأفضل تكون "start" علشان تفتح للداخل
    $dropAlignClass = $isRtl ? 'dropdown-menu-start' : 'dropdown-menu-end';
@endphp

<div class="d-flex">

    {{-- ✅ Sidebar --}}
    <aside class="student-sidebar" id="studentSidebar">
        <h4 class="mb-4">{{ $isRtl ? 'لوحة الطالب' : 'Student Panel' }}</h4>

        <a href="{{ route('student.dashboard') }}"
           class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
            <span class="nav-icon">🏠</span>
            <span class="nav-text">{{ $isRtl ? 'الصفحة الرئيسية' : 'Dashboard' }}</span>
        </a>

        <a href="{{ route('student.bookings.index') }}"
           class="nav-item {{ request()->routeIs('student.bookings.*') ? 'active' : '' }}">
            <span class="nav-icon">📚</span>
            <span class="nav-text">{{ $isRtl ? 'حجوزاتي' : 'My Bookings' }}</span>
        </a>

        <a href="{{ route('student.teachers.index') }}"
           class="nav-item {{ request()->routeIs('student.teachers.*') ? 'active' : '' }}">
            <span class="nav-icon">👨‍🏫</span>
            <span class="nav-text">{{ $isRtl ? 'ابحث عن معلّم' : 'Find Teacher' }}</span>
        </a>
    </aside>

    {{-- ✅ Main --}}
    <div class="student-main flex-fill">

        {{-- ✅ Top Navbar --}}
        <header class="top-navbar">
            <div class="top-navbar-title">
                @yield('page_title', $isRtl ? 'منطقة الطالب' : 'Student Area')
            </div>

            <div class="top-navbar-right">

                {{-- 🔔 Notifications --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm position-relative dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="{{ $isRtl ? 'الإشعارات' : 'Notifications' }}">
                        🔔
                        @if($unreadCount > 0)
                            <span class="badge bg-danger notif-badge" id="notifBadge">{{ $unreadCount }}</span>
                        @endif
                    </button>

                    <div class="dropdown-menu {{ $dropAlignClass }} p-0" style="width: 340px;">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong>{{ $isRtl ? 'الإشعارات' : 'Notifications' }}</strong>

                            @if($unreadCount > 0)
                                <form action="{{ route('notifications.readAll') }}" method="POST" class="m-0">
                                    @csrf
                                    <button class="btn btn-link btn-sm p-0">
                                        {{ $isRtl ? 'تحديد الكل كمقروء' : 'Mark all read' }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="list-group list-group-flush">
                            @forelse($latestNotifs as $n)
                                @php
                                    $data = $n->data ?? [];
                                    $title = $data['title'] ?? ($isRtl ? 'إشعار' : 'Notification');
                                    $message = $data['message'] ?? '';
                                    $bookingId = $data['booking_id'] ?? null;

                                    $targetUrl = $bookingId ? route('student.bookings.show', $bookingId) : '#';

                                    $isUnread = is_null($n->read_at);
                                    $readUrl = route('notifications.read', $n->id);
                                @endphp

                                <div class="list-group-item notif-item {{ $isUnread ? 'bg-light' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ $title }}</div>
                                            @if($message)
                                                <div class="text-muted small">{{ $message }}</div>
                                            @endif
                                            <div class="text-muted notif-time mt-1">
                                                {{ optional($n->created_at)->diffForHumans() }}
                                            </div>
                                        </div>

                                        <div class="d-flex flex-column gap-1 align-items-end">
                                            @if($bookingId)
                                                <a href="{{ $targetUrl }}"
                                                   class="btn btn-sm btn-outline-primary notif-open"
                                                   data-read-url="{{ $readUrl }}"
                                                   data-is-unread="{{ $isUnread ? '1' : '0' }}">
                                                    {{ $isRtl ? 'فتح' : 'Open' }}
                                                </a>
                                            @endif

                                            @if($isUnread)
                                                <form action="{{ $readUrl }}" method="POST" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                        {{ $isRtl ? 'مقروء' : 'Read' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-3 py-3 text-center text-muted">
                                    {{ $isRtl ? 'لا توجد إشعارات حالياً.' : 'No notifications yet.' }}
                                </div>
                            @endforelse
                        </div>

                        <div class="px-3 py-2 border-top text-center">
                            <small class="text-muted">
                                {{ $isRtl ? 'يتم عرض آخر 5 إشعارات' : 'Showing latest 5 notifications' }}
                            </small>
                        </div>
                    </div>
                </div>

                {{-- ✅ User menu (Avatar + Name -> Dropdown) --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary d-flex align-items-center gap-2 user-menu-btn dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <span class="avatar-circle">{{ $userInitial }}</span>
                        <span class="small">{{ $userName }}</span>
                    </button>

                    <ul class="dropdown-menu {{ $dropAlignClass }}">
                        <li class="dropdown-header small text-muted">
                            {{ $isRtl ? 'مسجل الدخول باسم' : 'Logged in as' }}
                            <strong>{{ $userName }}</strong>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        {{-- (اختياري لاحقًا) --}}
                        {{-- <li><a class="dropdown-item" href="#">{{ $isRtl ? 'حسابي' : 'My Profile' }}</a></li> --}}

                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="px-3 py-1">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-danger small">
                                    🚪 {{ $isRtl ? 'تسجيل الخروج' : 'Logout' }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

            </div>
        </header>

        <main class="container py-4">
            @includeWhen(View::exists('partials.alerts'), 'partials.alerts')
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function () {
        const sidebar = document.getElementById('studentSidebar');
        if (!sidebar) return;

        sidebar.querySelectorAll('a.nav-item[href]').forEach(a => {
            a.addEventListener('click', function (e) {
                if (e.defaultPrevented) {
                    window.location.href = a.href;
                    return;
                }
                setTimeout(() => {
                    if (window.location.href !== a.href) {
                        window.location.href = a.href;
                    }
                }, 0);
            }, true);
        });
    })();
</script>

<script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrf) return;

        document.addEventListener('click', async function (e) {
            const btn = e.target.closest('a.notif-open');
            if (!btn) return;

            e.preventDefault();

            const targetUrl = btn.getAttribute('href');
            const readUrl = btn.getAttribute('data-read-url');
            const isUnread = btn.getAttribute('data-is-unread') === '1';

            if (!readUrl) {
                window.location.href = targetUrl;
                return;
            }

            try {
                await fetch(readUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: new URLSearchParams()
                });

                if (isUnread) {
                    const badge = document.getElementById('notifBadge');
                    if (badge) {
                        const n = parseInt(badge.textContent || '0', 10);
                        const newN = Math.max(0, n - 1);
                        if (newN === 0) {
                            badge.remove();
                        } else {
                            badge.textContent = String(newN);
                        }
                    }
                }
            } catch (err) {}

            window.location.href = targetUrl;
        }, true);
    })();
</script>

@stack('scripts')
@stack('modals')

</body>
</html>
