@extends('layouts.student')

@section('page_title', 'تفاصيل الحجز (للطالب)')

@push('styles')
<style>
    /* ✅ إصلاحات قوية لضمان أن الـ Modal يظهر Popup في المنتصف ويكون فعّال */
    .modal { z-index: 200000 !important; pointer-events: auto !important; }
    .modal-backdrop { z-index: 199999 !important; }

    /* ✅ لو عندك CSS عام بيكسر z-index/opacity داخل المحتوى */
    .modal, .modal *{
        opacity: 1 !important;
        filter: none !important;
        pointer-events: auto !important;
    }

    .modal .modal-dialog,
    .modal .modal-content{
        transform: none !important;
    }

    /* ✅ تحسين مظهر الـ modal */
    .modal .modal-content{
        border: 0;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
    }

    /* ✅ تأكد أن العناصر القابلة للضغط داخل المودال فوق أي طبقات */
    .modal .btn, .modal a, .modal button, .modal form{
        position: relative;
        z-index: 200001 !important;
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">تفاصيل الحجز</h1>
        <a href="{{ route('student.bookings.index') }}" class="btn btn-secondary">
            ← رجوع إلى قائمة الحجوزات
        </a>
    </div>

    {{-- بيانات الحجز --}}
    <div class="card mb-4">
        <div class="card-header">
            بيانات الحجز
        </div>
        <div class="card-body">
            <p><strong>رقم الحجز:</strong> {{ $booking->id }} (UUID: {{ $booking->uuid }})</p>

            <p><strong>الحالة الحالية:</strong>
                @php
                    $status = $booking->status;
                    $badgeClass = match ($status) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled', 'canceled' => 'danger',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $badgeClass }}">
                    {{ $status }}
                </span>
            </p>

            <p><strong>تاريخ الحجز (للدرس):</strong>
                {{ optional($booking->booking_date)->format('Y-m-d') ?? '-' }}
            </p>

            <p><strong>تاريخ إنشاء الطلب:</strong>
                {{ optional($booking->created_at)->format('Y-m-d H:i') ?? '-' }}
            </p>
        </div>
    </div>

    {{-- ===================== ✅ قسم الاجتماع (Batch 1) ===================== --}}
    @php
        $isCancelled = in_array($booking->status, ['cancelled', 'canceled'], true);
        $isConfirmedForMeeting = $booking->status === 'confirmed';
    @endphp

    @if (!$isCancelled && $isConfirmedForMeeting)
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>الاجتماع</span>
                <span class="badge bg-light text-success">داخل المنصّة</span>
            </div>
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="fw-semibold">ابدأ الاجتماع في وقت الحصة فقط</div>
                    <div class="text-muted small">
                        زر "ابدأ الاجتماع" سيعمل فقط داخل نافذة الوقت المسموحة من السيرفر.
                        إذا كان الدخول مبكرًا أو متأخرًا ستظهر رسالة مناسبة.
                    </div>
                </div>
                    @php
                        $booking->loadMissing(['meeting']);
                        $meeting = $booking->meeting;
                    @endphp

                    @if($meeting)
                        @php
                            $mStatus = $meeting->status ?? 'scheduled';
                            $mBadge = match($mStatus){
                                'live'      => 'success',
                                'ended'     => 'secondary',
                                'cancelled' => 'danger',
                                default     => 'warning', // scheduled
                            };
                        @endphp

                        <div class="mb-2">
                            <strong>حالة الاجتماع:</strong>
                            <span class="badge bg-{{ $mBadge }}">{{ $mStatus }}</span>

                            @if(!empty($meeting->forced_ended_at))
                                <span class="text-muted small ms-2">
                                    (تم إنهاؤه من الأدمن: {{ \Carbon\Carbon::parse($meeting->forced_ended_at)->format('Y-m-d H:i') }})
                                </span>
                            @endif
                        </div>
                    @endif

                <a href="{{ route('meetings.room', $booking->id) }}"
                   class="btn btn-success btn-lg">
                    ▶️ ابدأ الاجتماع
                </a>
            </div>
        </div>
    @endif
    {{-- =================================================================== --}}

    <div class="row">
        {{-- بيانات الطالب --}}
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    بيانات الطالب
                </div>
                <div class="card-body">
                    <p><strong>الاسم:</strong> {{ optional($booking->student)->name ?? '-' }}</p>
                    <p><strong>البريد الإلكتروني:</strong> {{ optional($booking->student)->email ?? '-' }}</p>
                    <p><strong>العنوان:</strong> {{ $booking->address ?: '-' }}</p>
                    <p><strong>ملاحظات ولي الأمر:</strong> {{ $booking->notes ?: '-' }}</p>
                </div>
            </div>
        </div>

        {{-- بيانات المعلّم / المدينة --}}
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    بيانات المعلّم / المدينة
                </div>
                <div class="card-body">
                    <p><strong>المعلّم:</strong>
                        {{ optional(optional($booking->teacherProfile)->user)->name ?? '-' }}
                    </p>
                    <p><strong>المدينة:</strong>
                        {{ optional($booking->city)->name_en ?? '-' }}
                    </p>
                    <p><strong>طريقة الدرس:</strong>
                        {{ optional($booking->deliveryMode)->name_en ?? '-' }}
                    </p>
                    <p><strong>المبلغ:</strong>
                        {{ $booking->total_amount }} {{ $booking->currency }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- تاريخ تغيّر الحالة (لو موجود) --}}
    @if (!empty($booking->statusHistories) && $booking->statusHistories->count())
        <div class="card mb-4">
            <div class="card-header">
                تاريخ تغيّر الحالة
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>من حالة</th>
                            <th>إلى حالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($booking->statusHistories as $index => $history)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $history->old_status }}</td>
                                <td>{{ $history->new_status }}</td>
                                <td>{{ optional($history->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ✅ إجراءات على الحجز --}}
    <div class="card">
        <div class="card-header">
            إجراءات على الحجز
        </div>
        <div class="card-body">

            @php
                $isCancelled = in_array($booking->status, ['cancelled', 'canceled'], true);
                $isConfirmed = $booking->status === 'confirmed';
                $isPending   = $booking->status === 'pending';

                // ✅ حالة طلب الإلغاء (من الأعمدة الجديدة)
                $cancelReqStatus = $booking->cancel_request_status; // pending/approved/rejected/null
                $hasCancelRequest = !empty($booking->cancel_requested_at);

                // ✅ رسالة لطيفة حسب الحالة
                $cancelStatusBadge = match($cancelReqStatus) {
                    'pending'  => ['warning', 'قيد المراجعة'],
                    'approved' => ['success', 'تمت الموافقة'],
                    'rejected' => ['danger', 'تم الرفض'],
                    default    => ['secondary', 'لا يوجد'],
                };
            @endphp

            {{-- ✅ لو ملغى بالفعل --}}
            @if ($isCancelled)
                <div class="alert alert-secondary mb-0">
                    هذا الحجز ملغى بالفعل.
                </div>

            {{-- ✅ لو confirmed: طلب إلغاء بدل إلغاء مباشر --}}
            @elseif ($isConfirmed)

                {{-- لو فيه طلب إلغاء سابق --}}
                @if ($hasCancelRequest)
                    <div class="alert alert-light border mb-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">طلب الإلغاء</div>
                                <div class="text-muted small">
                                    تاريخ الطلب:
                                    {{ optional($booking->cancel_requested_at)->format('Y-m-d H:i') ?? '-' }}
                                </div>
                            </div>

                            <span class="badge bg-{{ $cancelStatusBadge[0] }}">
                                {{ $cancelStatusBadge[1] }}
                            </span>
                        </div>

                        @if (!empty($booking->cancel_request_reason))
                            <hr class="my-2">
                            <div class="text-muted small">
                                <strong>السبب:</strong>
                                {{ $booking->cancel_request_reason }}
                            </div>
                        @endif

                        @if ($cancelReqStatus === 'rejected' && !empty($booking->cancel_handle_note))
                            <div class="mt-2 text-muted small">
                                <strong>سبب الرفض:</strong>
                                {{ $booking->cancel_handle_note }}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-info mb-3">
                        هذا الحجز <strong>مؤكّد</strong>. لا يمكن إلغاؤه مباشرة من طرف الطالب،
                        لكن يمكنك إرسال <strong>طلب إلغاء</strong> ليراجعه المعلّم.
                    </div>
                @endif

                {{-- زر إرسال الطلب:
                     ✅ يظهر لو:
                     - لا يوجد طلب سابق
                     - أو كان rejected (نسمح بإعادة الإرسال)
                     ❌ لا يظهر لو pending/approved
                --}}
                @if (!$hasCancelRequest || $cancelReqStatus === 'rejected')
                    <button type="button"
                            class="btn btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#requestCancelModal">
                        🟡 طلب إلغاء الحجز
                    </button>
                @else
                    @if ($cancelReqStatus === 'pending')
                        <div class="alert alert-warning mb-0">
                            طلب الإلغاء تم إرساله بالفعل وهو <strong>قيد المراجعة</strong>.
                        </div>
                    @elseif ($cancelReqStatus === 'approved')
                        <div class="alert alert-success mb-0">
                            تمت الموافقة على طلب الإلغاء. (قد يتم تحويل الحجز إلى ملغى تلقائيًا حسب إعدادات النظام)
                        </div>
                    @endif
                @endif

            {{-- ✅ pending فقط: نسمح بالإلغاء المباشر مع Popup --}}
            @elseif ($isPending)
                <button type="button"
                        class="btn btn-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#cancelBookingModal">
                    إلغاء الحجز
                </button>

            @else
                <div class="alert alert-secondary mb-0">
                    لا توجد إجراءات متاحة لهذه الحالة.
                </div>
            @endif

        </div>
    </div>
@endsection

{{-- ✅ Modal تأكيد الإلغاء (Pending فقط) --}}
@push('modals')
@if (!in_array($booking->status, ['cancelled', 'canceled'], true) && $booking->status === 'pending')
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-body text-center py-4 px-4">
                    <div class="fs-1 mb-2">⚠️</div>
                    <h5 class="mb-2">تأكيد إلغاء الحجز</h5>
                    <p class="text-muted mb-4">
                        هل أنت متأكد أنك تريد إلغاء هذا الحجز؟
                    </p>

                    <div class="d-flex justify-content-center gap-3">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">
                            رجوع
                        </button>

                        <form action="{{ route('student.bookings.cancel', $booking) }}"
                              method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-danger">
                                نعم، إلغاء
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endif
@endpush

{{-- ✅ Modal طلب إلغاء (Confirmed فقط) --}}
@push('modals')
@php
    $isConfirmed = $booking->status === 'confirmed';
    $hasCancelRequest = !empty($booking->cancel_requested_at);
    $cancelReqStatus = $booking->cancel_request_status;
@endphp

@if (!in_array($booking->status, ['cancelled', 'canceled'], true) && $isConfirmed && (!$hasCancelRequest || $cancelReqStatus === 'rejected'))
    <div class="modal fade" id="requestCancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-body text-center py-4 px-4">
                    <div class="fs-1 mb-2">🟡</div>
                    <h5 class="mb-2">طلب إلغاء الحجز</h5>
                    <p class="text-muted mb-3">
                        اكتب سببًا مختصرًا (اختياريًا) ثم أرسل الطلب ليظهر للمعلّم للمراجعة.
                    </p>

                    <form action="{{ route('student.bookings.requestCancel', $booking) }}" method="POST" class="m-0">
                        @csrf

                        <div class="mb-3 text-start">
                            <label class="form-label">سبب طلب الإلغاء (اختياري)</label>
                            <textarea name="reason" class="form-control" rows="3" maxlength="500"
                                      placeholder="مثال: تغيير موعد، ظرف طارئ، ..."></textarea>
                            <div class="form-text">حد أقصى 500 حرف.</div>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">
                                رجوع
                            </button>

                            <button type="submit" class="btn btn-warning">
                                إرسال الطلب
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endif
@endpush
