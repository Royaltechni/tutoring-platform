@extends('layouts.teacher')

@section('page_title', 'لوحة تحكم المعلّم')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
        <div>
            <h2 class="mb-1">لوحة تحكم المعلّم</h2>
            <p class="text-muted mb-0 small">
                مرحبًا {{ $teacher->name ?? 'معلّم' }}، هذا ملخص حجوزاتك على المنصّة.
            </p>
        </div>

        <a href="{{ route('teacher.bookings.index') }}" class="btn btn-sm btn-primary">
            عرض جميع الحجوزات
        </a>
    </div>

    {{-- ✅ تنبيه سريع --}}
    @if(($byStatus['pending'] ?? 0) > 0)
        <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4">
            <div class="mb-0">
                ⏳ لديك <strong>{{ $byStatus['pending'] }}</strong> حجز/حجوزات قيد الانتظار وتحتاج إجراء.
            </div>
            <a href="{{ route('teacher.bookings.index') }}?status=pending" class="btn btn-sm btn-outline-dark">
                عرض الحجوزات المعلّقة
            </a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">إجمالي الحجوزات</h6>
                    <div class="fs-3 fw-bold">{{ $totalBookings }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">جلسات اليوم</h6>
                    <div class="fs-3 fw-bold">{{ $todayBookings }}</div>
                    
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">الحجوزات حسب الحالة</h6>

                    <div class="d-flex justify-content-between small mb-1">
                        <span>قيد الانتظار</span>
                        <span class="fw-semibold">{{ $byStatus['pending'] ?? 0 }}</span>
                    </div>

                    <div class="d-flex justify-content-between small mb-1">
                        <span>مؤكّد</span>
                        <span class="fw-semibold">{{ $byStatus['confirmed'] ?? 0 }}</span>
                    </div>

                    <div class="d-flex justify-content-between small">
                        <span>ملغى</span>
                        <span class="fw-semibold">{{ $byStatus['cancelled'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- أحدث الحجوزات --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span class="fw-semibold">أحدث الحجوزات</span>
            <a href="{{ route('teacher.bookings.index') }}" class="btn btn-sm btn-outline-primary">
                عرض الكل
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>اسم الطالب</th>
                            <th>الحالة</th>
                            <th>المبلغ</th>
                            <th>تاريخ الإنشاء</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestBookings as $booking)
                            @php
                                $statusClass = match($booking->status) {
                                    'confirmed' => 'success',
                                    'cancelled', 'canceled' => 'danger',
                                    'pending' => 'warning',
                                    default => 'secondary',
                                };

                                // ✅ اسم الطالب: من Map (لو عندنا studentKey) أو fallback
                                $studentName = '-';
                                if (!empty($studentKey) && !empty($booking->{$studentKey})) {
                                    $studentName = $studentsMap[$booking->{$studentKey}] ?? ('Student ID: '.$booking->{$studentKey});
                                } elseif (!empty($booking->student_name)) {
                                    $studentName = $booking->student_name;
                                }

                                // ✅ مبلغ آمن (لو العمود مختلف)
                                $amount = $booking->total_amount ?? $booking->amount ?? $booking->price ?? '-';
                            @endphp
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>{{ $studentName }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ $booking->status }}
                                    </span>
                                </td>
                                <td>{{ $amount }}</td>
                                <td>{{ optional($booking->created_at)->format('Y-m-d H:i') }}</td>

                                <td class="text-end">
                                    <a href="{{ route('teacher.bookings.show', $booking) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        تفاصيل
                                    </a>

                                    {{-- ✅ إجراءات سريعة للحجز المعلّق --}}
                                    @if($booking->status === 'pending')
                                        <form action="{{ route('teacher.bookings.updateStatus', $booking) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="btn btn-sm btn-success"
                                                onclick="return confirm('تأكيد هذا الحجز؟')">
                                                تأكيد
                                            </button>
                                        </form>

                                        <form action="{{ route('teacher.bookings.updateStatus', $booking) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('إلغاء هذا الحجز؟')">
                                                إلغاء
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled>تأكيد</button>
                                        <button class="btn btn-sm btn-outline-secondary" disabled>إلغاء</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    لا توجد حجوزات حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
