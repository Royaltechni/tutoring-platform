<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Tutoring Platform</title>

    {{-- Bootstrap 5 من CDN --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f3f4f6;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #111827;
            color: #e5e7eb;
        }
        .sidebar a {
            color: #9ca3af;
            text-decoration: none;
            display: block;
            padding: 0.65rem 1rem;
            border-radius: 0.375rem;
        }
        .sidebar a.active,
        .sidebar a:hover {
            background-color: #1f2937;
            color: #fff;
        }
        .page-title {
            font-weight: 600;
        }
        .card-stat {
            border-radius: 0.75rem;
        }

        /* شكل بسيط للأفاتار */
        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            background-color: #2563eb;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">

        {{-- Sidebar --}}
        <nav class="col-md-2 d-none d-md-block sidebar py-4">
            <div class="px-3 mb-4">
                <h4 class="mb-0">Admin Panel</h4>
                <small class="text-muted">Tutoring Platform</small>
            </div>

<div class="px-2">
    @php
        // نستخدم اسم المسار الحالي عشان نحدد الزر النشط
        $isDashboard = request()->routeIs('admin.dashboard');
        $isBookings  = request()->routeIs('admin.bookings.*');
        $isTeachers  = request()->routeIs('admin.teachers.*');
        $isStudents  = request()->routeIs('admin.students.*');
    @endphp

    {{-- Dashboard --}}
    <a href="{{ route('admin.dashboard') }}"
       class="{{ $isDashboard ? 'active' : '' }}">
        📊 Dashboard
    </a>

    {{-- Bookings --}}
    <a href="{{ route('admin.bookings.index') }}"
       class="{{ $isBookings ? 'active' : '' }}">
        📚 Bookings
    </a>

    {{-- Teachers --}}
    <a href="{{ route('admin.teachers.index') }}"
       class="{{ $isTeachers ? 'active' : '' }}">
        👨‍🏫 Teachers
    </a>

    {{-- Students --}}
    <a href="{{ route('admin.students.index') }}"
       class="{{ $isStudents ? 'active' : '' }}">
        👨‍🎓 Students
    </a>
</div>


        </nav>

        {{-- Main Content --}}
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            @php
                $user = auth()->user();
                $userName = $user?->name ?? 'Admin';
                $userInitial = strtoupper(mb_substr($userName, 0, 1));
            @endphp

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="page-title">@yield('title', 'Dashboard')</h2>

                {{-- 🔽 Dropdown المستخدم + Logout --}}
                <div class="dropdown">
                    <button
                        class="btn btn-outline-secondary d-flex align-items-center gap-2"
                        type="button"
                        id="userMenuButton"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <span class="avatar-circle">
                            {{ $userInitial }}
                        </span>
                        <span class="small">
                            {{ $userName }}
                        </span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                        <li class="dropdown-header small text-muted">
                            Logged in as <strong>{{ $userName }}</strong>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="px-3">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-danger small">
                                    🚪 Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
