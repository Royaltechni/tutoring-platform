<!DOCTYPE html>
@php
    $locale = app()->getLocale();          // ar أو en
    $isRtl  = $locale === 'ar';
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@yield('page_title', 'Teacher Panel')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- مهم للـ fetch/axios لو هتستخدمهم --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        html { overflow-y: scroll; }

        :root{
            --sidebar-w: 240px;
            --sidebar-gap: 20px;
        }

        body { background-color: #f5f5f5; }

        .teacher-sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background-color: #0d6efd;
            color: #fff;

            position: fixed;
            top: 0;
            left: 0;

            padding: 16px;
            box-sizing: border-box;
        }

        .teacher-sidebar h4{
            margin: 0 0 16px 0;
            font-weight: 700;
            direction: ltr;
            text-align: left;
            white-space: nowrap;
        }

        .teacher-main {
            margin-left: calc(var(--sidebar-w) + var(--sidebar-gap));
            min-height: 100vh;
        }

        .teacher-sidebar a.nav-link {
            color: rgba(255,255,255,0.9);
            font-weight: 600;

            display: flex;
            align-items: center;
            gap: 10px;

            padding: 10px 12px;
            border-radius: 8px;

            direction: ltr;
            box-sizing: border-box;
        }

        .teacher-sidebar a.nav-link .nav-icon{
            width: 22px;
            flex: 0 0 22px;
            text-align: center;
        }

        .teacher-sidebar a.nav-link .nav-text{
            flex: 1;
            text-align: left;
        }

        .teacher-sidebar a.nav-link.active {
            background-color: #ffffff;
            color: #0d6efd;
        }

        .teacher-sidebar a.nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .teacher-topbar{
            height: 60px;
            background: #fff;
            border-bottom: 1px solid #ddd;

            display: flex;
            align-items: center;
            padding: 10px 20px;

            direction: ltr;
        }

        .teacher-topbar .topbar-title{
            font-weight: 700;
            font-size: 18px;

            margin-right: auto;
            direction: rtl;
            text-align: left;
            white-space: nowrap;
        }

        .teacher-topbar .topbar-actions{
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 12px;
            direction: ltr;
        }

        main.teacher-content{
            padding: 24px;
        }

        /* ✅ Badge صغير فوق الجرس */
        .notif-badge{
            position: absolute;
            top: -6px;
            right: -6px;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 999px;
        }

        .notif-item{
            white-space: normal;
            line-height: 1.2;
        }

        .notif-time{
            font-size: 12px;
        }
    </style>

    @stack('styles')
</head>
<body>

    {{-- ✅ Sidebar --}}
    <aside class="teacher-sidebar">
        <h4>{{ $isRtl ? 'لوحة المعلّم' : 'Teacher Panel' }}</h4>

        <nav class="nav nav-pills flex-column gap-1">

            <a href="{{ route('teacher.dashboard') }}"
               class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">🏠</span>
                <span class="nav-text">{{ $isRtl ? 'الرئيسية' : 'Dashboard' }}</span>
            </a>

            <a href="{{ route('teacher.bookings.index') }}"
               class="nav-link {{ request()->routeIs('teacher.bookings.*') ? 'active' : '' }}">
                <span class="nav-icon">📚</span>
                <span class="nav-text">{{ $isRtl ? 'الحجوزات' : 'Bookings' }}</span>
            </a>

            <a href="{{ route('teacher.profile.edit') }}"
               class="nav-link {{ request()->routeIs('teacher.profile.*') ? 'active' : '' }}">
                <span class="nav-icon">👤</span>
                <span class="nav-text">{{ $isRtl ? 'ملفي' : 'My Profile' }}</span>
            </a>

        </nav>
    </aside>

    {{-- ✅ Main Content --}}
    <div class="teacher-main">

        {{-- ✅ Topbar --}}
        <header class="teacher-topbar">

            <div class="topbar-title">
                @yield('page_title', ($isRtl ? 'منطقة المعلّم' : 'Teacher Area'))
            </div>

            <div class="topbar-actions">
                @php
                    // ✅ آخر 5 إشعارات فقط (بدون تحميل الكل)
                    $notifUser = auth()->user();
                    $unreadCount = $notifUser?->unreadNotifications()->count() ?? 0;
                    $latestNotifs = $notifUser?->notifications()->latest()->limit(5)->get() ?? collect();
                @endphp

                {{-- 🔔 Notifications --}}
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm position-relative dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="{{ $isRtl ? 'الإشعارات' : 'Notifications' }}">
                        🔔
                        @if($unreadCount > 0)
                            <span class="badge bg-danger notif-badge">{{ $unreadCount }}</span>
                        @endif
                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px;">
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

                                    // ✅ رابط مناسب للمعلم: يفتح تفاصيل الحجز
                                    $targetUrl = $bookingId ? route('teacher.bookings.show', $bookingId) : '#';

                                    $isUnread = is_null($n->read_at);
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
                                                <a href="{{ $targetUrl }}" class="btn btn-sm btn-outline-primary">
                                                    {{ $isRtl ? 'فتح' : 'Open' }}
                                                </a>
                                            @endif

                                            @if($isUnread)
                                                <form action="{{ route('notifications.read', $n->id) }}" method="POST" class="m-0">
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

                <span class="text-muted small">
                    {{ auth()->user()->name ?? ($isRtl ? 'معلّم' : 'Teacher') }}
                </span>

                <form action="{{ route('logout') }}" method="POST" class="mb-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        {{ $isRtl ? 'تسجيل الخروج' : 'Logout' }}
                    </button>
                </form>
            </div>

        </header>

        <main class="teacher-content">
            @includeWhen(View::exists('partials.alerts'), 'partials.alerts')
            @yield('content')
        </main>

    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
