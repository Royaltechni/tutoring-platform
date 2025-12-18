@extends('layouts.app')
@section('page_title', 'تعديل الحجز (لوحة الأدمن)')

@section('content')
    {{-- الهيدر --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="mb-1">تعديل الحجز #{{ $booking->id }}</h2>
            <p class="text-muted mb-0 small">
                يمكنك من هنا مراجعة بيانات الحجز وتعديل المبلغ، المدينة، أو حالة الحجز.
            </p>
        </div>

        <div class="btn-group">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary">
                ← قائمة الحجوزات
            </a>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-secondary">
                تفاصيل الحجز
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            يوجد بعض الأخطاء في البيانات، برجاء مراجعتها ثم المحاولة مرة أخرى.
        </div>
    @endif

    <div class="row g-4">
        {{-- ملخص الحجز --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-light border-0 rounded-top-3">
                    <strong>ملخص الحجز</strong>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <small class="text-muted d-block">اسم الطالب</small>
                        <span class="fw-semibold">{{ $booking->student_name ?? '-' }}</span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">البريد الإلكتروني</small>
                        <span class="fw-semibold">{{ $booking->student_email ?? '-' }}</span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">المدينة</small>
                        <span class="fw-semibold">{{ $booking->city ?? '-' }}</span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">المبلغ الحالي</small>
                        <span class="fw-semibold">{{ $booking->total_amount ?? '-' }}</span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">الحالة الحالية</small>
                        @php
                            $statusClass = match($booking->status) {
                                'confirmed' => 'success',
                                'cancelled', 'canceled' => 'danger',
                                'pending' => 'warning',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }} px-3 py-2">
                            {{ $booking->status }}
                        </span>
                    </div>

                    <div class="mb-0">
                        <small class="text-muted d-block">تاريخ الإنشاء</small>
                        <span class="fw-semibold">
                            {{ optional($booking->created_at)->format('Y-m-d H:i') }}
                        </span>
                    </div>

                </div>
            </div>
        </div>

        {{-- نموذج التعديل --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-light border-0 rounded-top-3">
                    <strong>تعديل بيانات الحجز</strong>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        {{-- المبلغ الكلي --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label">المبلغ الكلي</label>
                            <input
                                type="number"
                                step="0.01"
                                name="total_amount"
                                class="form-control @error('total_amount') is-invalid @enderror"
                                value="{{ old('total_amount', $booking->total_amount) }}"
                            >
                            @error('total_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- المدينة --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label">المدينة</label>
                            <select
                                name="city"
                                class="form-select @error('city') is-invalid @enderror"
                            >
                                <option value="">-- اختر المدينة --</option>
                                @foreach ($cities as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('city', $booking->city) === $value ? 'selected' : '' }}>
                                        {{ $label }} ({{ $value }})
                                    </option>
                                @endforeach
                            </select>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- الحالة --}}
                        <div class="col-12">
                            <label class="form-label">حالة الحجز</label>
                            @php
                                $statuses = [
                                    'pending'   => 'قيد الانتظار',
                                    'confirmed' => 'مؤكد',
                                    'cancelled' => 'ملغى',
                                ];
                            @endphp
                            <select
                                name="status"
                                class="form-select @error('status') is-invalid @enderror"
                            >
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('status', $booking->status) === $value ? 'selected' : '' }}>
                                        {{ $label }} ({{ $value }})
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary px-4">
                                💾 حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
