@extends('layouts.app')

@section('page_title', 'لوحة تحكم الأدمن')

@section('content')

<div class="container py-4">

    <h2 class="mb-4">لوحة تحكم الأدمن</h2>

    {{-- الصف الأول: إحصائيات عامة --}}
    <div class="row g-3 mb-3">

        <div class="col-md-3">
            <div class="card text-bg-primary h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">إجمالي الحجوزات</h6>
                    <h3 class="mb-0">{{ $totalBookings }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-success h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">المؤكَّدة</h6>
                    <h3 class="mb-0">{{ $confirmedBookings }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-warning h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">قيد المراجعة</h6>
                    <h3 class="mb-0">{{ $pendingBookings }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-danger h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">الملغاة</h6>
                    <h3 class="mb-0">{{ $cancelledBookings }}</h3>
                </div>
            </div>
        </div>

    </div>

    {{-- الصف الثاني: حجوزات اليوم / الشهر --}}
    <div class="row g-3 mb-3">

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">حجوزات اليوم</h6>
                    <h3 class="mb-0">{{ $todayBookings }}</h3>
                    <p class="text-muted mb-0 mt-1">
                        عدد الحجوزات التي تم إنشاؤها اليوم.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">حجوزات هذا الشهر</h6>
                    <h3 class="mb-0">{{ $thisMonthBookings }}</h3>
                    <p class="text-muted mb-0 mt-1">
                        إجمالي الحجوزات خلال هذا الشهر.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">إجمالي الحجوزات (كل الوقت)</h6>
                    <h3 class="mb-0">{{ $totalBookings }}</h3>
                    <p class="text-muted mb-0 mt-1">
                        إجمالي جميع الحجوزات المسجَّلة.
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- الصف الثالث: المبالغ / الإيرادات --}}
    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">إجمالي الإيرادات (المؤكَّدة)</h6>
                    <h3 class="mb-0">
                        {{ number_format($totalRevenue, 2) }}
                    </h3>
                    <p class="text-muted mb-0 mt-1">
                        مجموع مبالغ جميع الحجوزات المؤكَّدة.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title mb-2">إيرادات هذا الشهر (المؤكَّدة)</h6>
                    <h3 class="mb-0">
                        {{ number_format($thisMonthRevenue, 2) }}
                    </h3>
                    <p class="text-muted mb-0 mt-1">
                        مجموع مبالغ الحجوزات المؤكَّدة خلال هذا الشهر.
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- جدول آخر الحجوزات --}}
    <div class="card">
        <div class="card-header">
            أحدث الحجوزات
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الطالب</th>
                            <th>المعلّم</th>
                            <th>الحالة</th>
                            <th>المبلغ الإجمالي</th>
                            <th>تاريخ الإنشاء</th>
                            <th style="width: 140px;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestBookings as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>{{ optional($booking->student)->name ?? '-' }}</td>
                                <td>{{ optional($booking->teacher)->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        {{ $booking->status }}
                                    </span>
                                </td>
                                <td>{{ $booking->total_amount ?? 0 }}</td>
                                <td>{{ $booking->created_at }}</td>
                                <td class="d-flex flex-wrap gap-1">
                                    {{-- زر عرض التفاصيل --}}
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        عرض
                                    </a>

                                    {{-- زر التعديل --}}
                                    <a href="{{ route('admin.bookings.edit', $booking) }}"
                                       class="btn btn-sm btn-warning">
                                        تعديل
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3">
                                    لا توجد حجوزات حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
