@extends('layouts.student')

@section('page_title', 'لوحة تحكم الطالب')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-1">أهلاً، {{ auth()->user()->name }} 👋</h3>
            <p class="text-muted mb-0">
                هنا هتلاقي ملخص سريع لحسابك، وأحدث الحجوزات، وتنبيهات مهمة.
            </p>
        </div>

        <a href="{{ route('student.teachers.index') }}" class="btn btn-primary">
            🔎 ابحث عن معلّم
        </a>
    </div>

    {{-- ✅ المرحلة 3: رسالة تحفيزية ذكية --}}
    <div class="alert alert-{{ $motivationType ?? 'info' }} mb-3">
        <div class="fw-bold mb-1">{{ $motivationTitle ?? '' }}</div>
        <div>{{ $motivationText ?? '' }}</div>
    </div>

    {{-- ✅ المرحلة 3: إحصائيات هذا الشهر + Progress --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted">حجوزات هذا الشهر</div>
                    <div class="fs-3 fw-bold">{{ $bookingsThisMonth ?? 0 }}</div>
                    <div class="small text-muted mt-1">
                        مؤكد: {{ $confirmedThisMonth ?? 0 }} — معلّق: {{ $pendingThisMonth ?? 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="text-muted">Progress هذا الشهر</div>
                            <div class="fw-bold">{{ $progressPercent ?? 0 }}%</div>
                        </div>
                        <div class="small text-muted">
                            هدف مقترح: 80%+
                        </div>
                    </div>

                    <div class="progress" style="height: 12px;">
                        <div
                            class="progress-bar"
                            role="progressbar"
                            style="width: {{ $progressPercent ?? 0 }}%;"
                            aria-valuenow="{{ $progressPercent ?? 0 }}"
                            aria-valuemin="0"
                            aria-valuemax="100">
                        </div>
                    </div>

                    <div class="small text-muted mt-2">
                        النسبة = (الحجوزات المؤكدة هذا الشهر ÷ إجمالي حجوزات هذا الشهر)
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ المرحلة 2: التنبيهات (معلّق/لا) --}}
    <div class="mb-3">
        @if(!empty($hasPending))
            <div class="alert alert-warning d-flex align-items-center justify-content-between">
                <div class="mb-0">
                    ⏳ لديك <strong>{{ $pendingBookings }}</strong> حجز/حجوزات معلّقة.
                    يمكنك مراجعتها من صفحة الحجوزات.
                </div>
                <a href="{{ route('student.bookings.index') }}" class="btn btn-sm btn-outline-dark">
                    عرض الحجوزات
                </a>
            </div>
        @else
            <div class="alert alert-success mb-0">
                ✅ لا توجد حجوزات معلّقة حالياً.
            </div>
        @endif
    </div>

    {{-- ✅ المرحلة 1: كروت الملخص --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted">إجمالي الحجوزات</div>
                            <div class="fs-3 fw-bold">{{ $totalBookings ?? 0 }}</div>
                        </div>
                        <div class="fs-4">📚</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted">المؤكدة</div>
                            <div class="fs-3 fw-bold">{{ $confirmedBookings ?? 0 }}</div>
                        </div>
                        <div class="fs-4">✅</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted">المعلّقة</div>
                            <div class="fs-3 fw-bold">{{ $pendingBookings ?? 0 }}</div>
                        </div>
                        <div class="fs-4">⏳</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted">الملغاة</div>
                            <div class="fs-3 fw-bold">{{ $cancelledBookings ?? 0 }}</div>
                        </div>
                        <div class="fs-4">❌</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ أقرب/آخر حجز --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold">📌 أقرب/آخر حجز</span>
            <a href="{{ route('student.bookings.index') }}" class="btn btn-sm btn-outline-secondary">
                عرض كل الحجوزات
            </a>
        </div>

        <div class="card-body">
            @if(!empty($latestBooking))
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <div class="text-muted">رقم الحجز</div>
                        <div class="fw-bold">#{{ $latestBooking->id }}</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="text-muted">الحالة</div>
                        <div class="fw-bold">
                            @php($st = $latestBooking->status ?? '-')
                            @if($st === 'confirmed')
                                <span class="badge bg-success">مؤكد</span>
                            @elseif($st === 'pending')
                                <span class="badge bg-warning text-dark">معلّق</span>
                            @elseif($st === 'cancelled' || $st === 'canceled')
                                <span class="badge bg-danger">ملغي</span>
                            @else
                                <span class="badge bg-secondary">{{ $st }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="text-muted">تاريخ الإنشاء</div>
                        <div class="fw-bold">
                            {{ optional($latestBooking->created_at)->format('Y-m-d') ?? '-' }}
                        </div>
                    </div>

                    <div class="col-12 col-md-3 text-md-end">
                        <a href="{{ route('student.bookings.show', $latestBooking->id) }}" class="btn btn-outline-primary">
                            تفاصيل الحجز
                        </a>
                    </div>
                </div>
            @else
                <div class="alert alert-info mb-0">
                    لا يوجد لديك حجوزات حتى الآن. اضغط على <strong>ابحث عن معلّم</strong> لبدء أول حجز.
                </div>
            @endif
        </div>
    </div>

    {{-- ✅ آخر الحجوزات --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-bold">🧾 آخر الحجوزات</span>
            <a href="{{ route('student.bookings.index') }}" class="btn btn-sm btn-outline-secondary">
                عرض الكل
            </a>
        </div>

        <div class="card-body">
            @if(!empty($recentBookings) && $recentBookings->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المعلّم</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th class="text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBookings as $b)
                                @php($st = $b->status ?? '-')
                                <tr>
                                    <td class="fw-bold">#{{ $b->id }}</td>
                                    <td>{{ optional($b->teacher)->name ?? ('Teacher ID: '.($b->teacher_id ?? '-')) }}</td>

                                    <td>
                                        @if($st === 'confirmed')
                                            <span class="badge bg-success">مؤكد</span>
                                        @elseif($st === 'pending')
                                            <span class="badge bg-warning text-dark">معلّق</span>
                                        @elseif($st === 'cancelled' || $st === 'canceled')
                                            <span class="badge bg-danger">ملغي</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $st }}</span>
                                        @endif
                                    </td>

                                    <td>{{ optional($b->created_at)->format('Y-m-d') ?? '-' }}</td>

                                    <td class="text-end">
                                        <a href="{{ route('student.bookings.show', $b->id) }}" class="btn btn-sm btn-outline-primary">
                                            تفاصيل
                                        </a>

                                        @if($st === 'pending')
                                            <form action="{{ route('student.bookings.cancel', $b->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('هل أنت متأكد من إلغاء الحجز؟')">
                                                    إلغاء
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                إلغاء
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info mb-0">
                    لا يوجد حجوزات لعرضها حالياً.
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
