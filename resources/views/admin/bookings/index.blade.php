@extends('layouts.app')


@section('page_title', 'الحجوزات (لوحة الأدمن)')


@section('content')
<div class="container-fluid mt-4">

    <h2 class="mb-2">حجوزات الطلاب</h2>
    <p class="text-muted">
        هنا يمكنك متابعة جميع الحجوزات، تعديل البيانات، وتأكيد أو إلغاء الحجز.
    </p>

    {{-- أزرار الفلترة حسب الحالة --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div class="btn-group" role="group">
            <a href="{{ route('admin.bookings.index') }}"
               class="btn btn-outline-secondary {{ request('status') ? '' : 'active' }}">
                الكل
            </a>

            <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}"
               class="btn btn-outline-warning {{ request('status') === 'pending' ? 'active' : '' }}">
                قيد الانتظار
            </a>

            <a href="{{ route('admin.bookings.index', ['status' => 'confirmed']) }}"
               class="btn btn-outline-success {{ request('status') === 'confirmed' ? 'active' : '' }}">
                مؤكّد
            </a>

            <a href="{{ route('admin.bookings.index', ['status' => 'cancelled']) }}"
               class="btn btn-outline-danger {{ request('status') === 'cancelled' ? 'active' : '' }}">
                ملغى
            </a>
        </div>

        {{-- شارات بسيطة لعدد الحجوزات حسب الحالة (لو المتغيّر $stats مرسَل من الكنترولر) --}}
        @isset($stats)
            <div class="d-flex gap-2">
                <span class="badge bg-success">
                    مؤكّد: {{ $stats['confirmed'] ?? 0 }}
                </span>
                <span class="badge bg-warning text-dark">
                    قيد الانتظار: {{ $stats['pending'] ?? 0 }}
                </span>
                <span class="badge bg-danger">
                    ملغى: {{ $stats['cancelled'] ?? 0 }}
                </span>
            </div>
        @endisset
    </div>

    {{-- جدول الحجوزات --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>اسم الطالب</th>
                            <th>البريد الإلكتروني</th>
                            <th>المدينة</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th style="width:260px;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $status = $booking->status;

                            // منطق تفعيل / تعطيل الأزرار
                            $canEdit    = $status !== 'cancelled';
                            $canConfirm = $status === 'pending';
                            $canCancel  = $status !== 'cancelled';
                        @endphp

                        <tr>
                            <td>{{ $booking->id }}</td>
                            <td>{{ $booking->student_name ?? '-' }}</td>
                            <td>{{ $booking->student_email ?? '-' }}</td>
                            <td>{{ $booking->city ?? '-' }}</td>
                            <td>
                                @if ($status === 'pending')
                                    <span class="badge bg-warning text-dark">pending</span>
                                @elseif ($status === 'confirmed')
                                    <span class="badge bg-success">confirmed</span>
                                @elseif ($status === 'cancelled')
                                    <span class="badge bg-danger">cancelled</span>
                                @else
                                    <span class="badge bg-secondary">{{ $status }}</span>
                                @endif
                            </td>
                            <td>{{ $booking->created_at?->format('Y-m-d H:i') }}</td>

                            {{-- نفس عدد الأزرار في كل صف – الأزرار غير المسموح بها معطّلة فقط --}}
                            <td>
                                <div class="d-flex justify-content-center gap-2 flex-wrap">

                                    {{-- تفاصيل (دائمًا فعّال) --}}
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        تفاصيل
                                    </a>

                                    {{-- تعديل --}}
                                    <a href="{{ $canEdit ? route('admin.bookings.edit', $booking) : '#' }}"
                                       class="btn btn-sm btn-warning {{ $canEdit ? '' : 'disabled' }}"
                                       @unless($canEdit) style="pointer-events:none; opacity:.5;" aria-disabled="true" @endunless>
                                        تعديل
                                    </a>

                                    {{-- تأكيد --}}
                                    <form action="{{ route('admin.bookings.updateStatus', $booking) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="confirmed">

                                        <button type="submit"
                                                class="btn btn-sm btn-success {{ $canConfirm ? '' : 'disabled' }}"
                                                @unless($canConfirm) style="pointer-events:none; opacity:.5;" aria-disabled="true" @endunless>
                                            تأكيد
                                        </button>
                                    </form>

                                    {{-- إلغاء --}}
                                    <form action="{{ route('admin.bookings.updateStatus', $booking) }}"
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="cancelled">

                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger {{ $canCancel ? '' : 'disabled' }}"
                                                @unless($canCancel) style="pointer-events:none; opacity:.5;" aria-disabled="true" @endunless>
                                            إلغاء
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-muted">
                                لا يوجد حجوزات حتى الآن.
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
