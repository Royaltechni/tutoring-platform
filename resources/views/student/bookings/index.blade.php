@extends('layouts.student')

@section('page_title', 'حجوزاتي (كطالب)')

@section('content')
{{-- ✅ ملاحظة: لا نضع container هنا لأن الـ layout بالفعل يحتوي على <main class="container py-4"> --}}

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h3 mb-1">حجوزاتي</h1>
        <p class="text-muted mb-0">
            هذه الصفحة تعرض جميع الحجوزات الخاصة بالطالب.
        </p>
    </div>

    {{-- ✅ زر حجز جديد --}}
    <a href="{{ route('student.teachers.index') }}" class="btn btn-primary">
        ابحث عن معلّم +
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if ($bookings->count() === 0)
    <div class="alert alert-info">
        لا توجد حجوزات حتى الآن.
    </div>
@else
    <div class="card">
        <div class="card-header">
            قائمة الحجوزات
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th>المعلّم</th>
                            <th>المدينة</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>تاريخ الحجز</th>
                            <th>تاريخ الإنشاء</th>
                            <th style="width: 10%;">تفاصيل</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td>
                                    {{ $loop->iteration + ($bookings->currentPage() - 1) * $bookings->perPage() }}
                                </td>

                                <td>
                                    @if ($booking->teacherProfile && $booking->teacherProfile->user)
                                        {{ $booking->teacherProfile->user->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    {{ optional($booking->city)->name ?? '-' }}
                                </td>

                                <td>
                                    {{ number_format($booking->total_amount, 2) }} {{ $booking->currency }}
                                </td>

                                <td>
                                    @php
                                        $status = $booking->status;
                                        $badgeClass = match ($status) {
                                            'confirmed' => 'badge bg-success',
                                            'cancelled' => 'badge bg-danger',
                                            'pending' => 'badge bg-warning text-dark',
                                            default => 'badge bg-secondary',
                                        };
                                    @endphp

                                    <span class="{{ $badgeClass }}">
                                        {{ $status }}
                                    </span>
                                </td>

                                <td>
                                    @if ($booking->booking_date)
                                        {{ $booking->booking_date->format('Y-m-d') }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    {{ $booking->created_at->format('Y-m-d H:i') }}
                                </td>

                                <td>
                                    <a href="{{ route('student.bookings.show', $booking) }}"
                                       class="btn btn-sm btn-primary">
                                        تفاصيل
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $bookings->links() }}
        </div>
    </div>
@endif
@endsection
