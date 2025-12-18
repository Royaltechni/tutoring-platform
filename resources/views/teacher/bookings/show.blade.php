@extends('layouts.teacher')

@section('page_title', 'تفاصيل الحجز')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">تفاصيل الحجز #{{ $booking->id }}</h2>

        <a href="{{ route('teacher.bookings.index') }}" class="btn btn-outline-secondary btn-sm">
            ← الرجوع إلى قائمة الحجوزات
        </a>
    </div>

    {{-- الصف الأول: معلومات الحجز + بيانات الطالب --}}
    <div class="row g-3 mb-4">

        {{-- معلومات الحجز --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    معلومات الحجز
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>رقم الحجز:</strong> #{{ $booking->id }}</p>

                    @php
                        $statusClass = match($booking->status) {
                            'confirmed' => 'success',
                            'cancelled', 'canceled' => 'danger',
                            'pending' => 'warning',
                            default => 'secondary',
                        };
                    @endphp

                    <p class="mb-1">
                        <strong>حالة الحجز:</strong>
                        <span class="badge bg-{{ $statusClass }}">
                            {{ $booking->status }}
                        </span>
                    </p>

                    <p class="mb-1">
                        <strong>نوع الحجز:</strong>
                        {{ $booking->booking_type ?? 'normal' }}
                    </p>

                    <p class="mb-1">
                        <strong>حالة الدفع:</strong>
                        {{ $booking->payment_status ?? 'pending' }}
                    </p>

                    <p class="mb-1">
                        <strong>تاريخ الإنشاء:</strong>
                        {{ optional($booking->created_at)->format('Y-m-d H:i') }}
                    </p>

                    <p class="mb-1">
                        <strong>تاريخ أول حصة:</strong>
                        {{ optional($booking->first_lesson_at)->format('Y-m-d H:i') ?? '-' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- بيانات الطالب (بدون إيميل / تلفون) --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    بيانات الطالب
                </div>
                <div class="card-body">
                    @php
                        $student = $booking->student ?? null;
                    @endphp

                    <p class="mb-1">
                        <strong>اسم الطالب:</strong>
                        {{ $student?->name ?? 'غير متوفر' }}
                    </p>
                </div>
            </div>
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
            default     => 'warning',
        };
    @endphp

        <div class="alert alert-light border mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong>حالة الاجتماع:</strong>
                    <span class="badge bg-{{ $mBadge }}">{{ $mStatus }}</span>
                </div>

                @if(!empty($meeting->forced_ended_at))
                    <div class="text-muted small">
                        تم إنهاؤه من الأدمن: {{ \Carbon\Carbon::parse($meeting->forced_ended_at)->format('Y-m-d H:i') }}
                    </div>
                @endif
            </div>
        </div>
    @endif


    {{-- ✅ قسم الاجتماع (Batch 1) --}}
    @php
        $isConfirmedForMeeting = ($booking->status === 'confirmed');
        $isCancelledForMeeting = in_array($booking->status, ['cancelled','canceled'], true);
    @endphp

    @if($isConfirmedForMeeting && !$isCancelledForMeeting)
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>الاجتماع</span>
                <span class="badge bg-light text-success">داخل المنصّة</span>
            </div>
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="fw-semibold">ابدأ الاجتماع في وقت الحصة فقط</div>
                    <div class="text-muted small">
                        الدخول والتحكم الزمني والصلاحيات كلها من السيرفر.
                        لو دخلت بدري/متأخر ستظهر رسالة منع الدخول.
                    </div>
                </div>

                <a href="{{ route('meetings.room', $booking->id) }}"
                   class="btn btn-success btn-lg">
                    ▶️ ابدأ الاجتماع
                </a>
            </div>
        </div>
    @endif

    {{-- ✅ بلوك طلب الإلغاء (يظهر فقط لو الحجز confirmed والطالب عمل request) --}}
    @php
        // القيم المتوقعة:
        // cancel_request_status: null | pending | approved | rejected
        $cancelReqStatus = $booking->cancel_request_status ?? null;
        $hasCancelReq = in_array($cancelReqStatus, ['pending','approved','rejected'], true);
        $isConfirmed = $booking->status === 'confirmed';
        $isCancelled = in_array($booking->status, ['cancelled','canceled'], true);
    @endphp

    @if($isConfirmed && $hasCancelReq)
        @php
            $reqBadge = match($cancelReqStatus){
                'pending'  => ['warning', 'قيد المراجعة'],
                'approved' => ['success', 'تم القبول'],
                'rejected' => ['danger',  'تم الرفض'],
                default    => ['secondary', '—'],
            };
        @endphp

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>طلب إلغاء من الطالب</span>
                <span class="badge bg-{{ $reqBadge[0] }}">{{ $reqBadge[1] }}</span>
            </div>

            <div class="card-body">
                <div class="mb-2">
                    <strong>تاريخ الطلب:</strong>
                    @if(!empty($booking->cancel_requested_at))
                        {{ \Carbon\Carbon::parse($booking->cancel_requested_at)->format('Y-m-d H:i') }}
                    @else
                        -
                    @endif
                </div>

                <div class="mb-3">
                    <strong>سبب الطلب (إن وُجد):</strong><br>
                    <span class="text-muted">
                        {{ $booking->cancel_request_reason ?: 'لم يكتب الطالب سببًا.' }}
                    </span>
                </div>

                @if($cancelReqStatus === 'pending')
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button"
                                class="btn btn-success js-open-cancel-request-modal"
                                data-action="{{ route('teacher.bookings.cancelRequest.approve', $booking) }}"
                                data-title="قبول طلب الإلغاء"
                                data-message="هل تريد قبول طلب الإلغاء وإلغاء الحجز؟"
                                data-note="سيتم تحويل حالة الحجز إلى (ملغى) وإخطار الطالب فورًا.">
                            ✅ قبول الإلغاء
                        </button>

                        <button type="button"
                                class="btn btn-outline-danger js-open-cancel-request-modal"
                                data-action="{{ route('teacher.bookings.cancelRequest.reject', $booking) }}"
                                data-title="رفض طلب الإلغاء"
                                data-message="هل تريد رفض طلب الإلغاء والإبقاء على الحجز مؤكد؟"
                                data-note="سيتم إخطار الطالب برفض الطلب، وسيظل الحجز (مؤكد).">
                            ❌ رفض الطلب
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- الصف الثاني: تفاصيل الدرس + الأسعار --}}
    <div class="row g-3 mb-4">

        {{-- تفاصيل الدرس --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    تفاصيل الدرس
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>المادة:</strong> {{ $booking->subject ?? '-' }}</p>
                    <p class="mb-1"><strong>الصف:</strong> {{ $booking->grade ?? '-' }}</p>
                    <p class="mb-1"><strong>نوع المنهج:</strong> {{ $booking->curriculum ?? '-' }}</p>

                    <p class="mb-1">
                        <strong>طريقة الدرس:</strong>
                        @if ($booking->mode === 'online')
                            أونلاين
                        @elseif ($booking->mode === 'onsite')
                            حضوري
                        @else
                            -
                        @endif
                    </p>

                    <p class="mb-1"><strong>مدة الحصة:</strong> {{ $booking->duration_minutes ?? '-' }} دقيقة</p>
                    <p class="mb-1"><strong>عدد الحصص:</strong> {{ $booking->lessons_count ?? 1 }}</p>
                    <p class="mb-1"><strong>المكان (للحصة الحضورية):</strong> {{ $booking->location ?? '-' }}</p>

                    <p class="mb-1">
                        <strong>ملاحظات الطالب:</strong><br>
                        {{ $booking->notes ?? 'لا توجد ملاحظات' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- الأسعار --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    تفاصيل السعر
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <strong>سعر الحصة الواحدة:</strong>
                        {{ number_format($booking->price_per_lesson ?? 0, 2) }}
                        {{ $booking->currency ?? 'AED' }}
                    </p>

                    <p class="mb-1"><strong>عدد الحصص:</strong> {{ $booking->lessons_count ?? 1 }}</p>

                    <p class="mb-0">
                        <strong>إجمالي السعر:</strong>
                        {{ number_format($booking->total_price ?? $booking->total_amount ?? 0, 2) }}
                        {{ $booking->currency ?? 'AED' }}
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- الصف الثالث: المرفقات --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    المرفقات التي رفعها الطالب
                </div>
                <div class="card-body p-0">
                    @if($attachments->isEmpty())
                        <p class="text-muted p-3 mb-0">لا توجد مرفقات لهذا الحجز.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم الملف</th>
                                    <th>تم الرفع بواسطة</th>
                                    <th>تاريخ الرفع</th>
                                    <th>تحميل</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($attachments as $index => $file)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $file->original_name }}</td>
                                        <td>
                                            @if($file->uploaded_by_type === 'student')
                                                الطالب
                                            @elseif($file->uploaded_by_type === 'teacher')
                                                المعلّم
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($file->created_at)->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <a href="{{ asset('storage/' . $file->file_path) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               target="_blank">
                                                تحميل
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- الصف الرابع: الإجراءات --}}
    <div class="card">
        <div class="card-header">إجراءات</div>
        <div class="card-body">

            {{-- لو ملغي بالفعل --}}
            @if($isCancelled)
                <p class="text-muted mb-0">لا توجد إجراءات متاحة (الحجز ملغى).</p>

            {{-- pending --}}
            @elseif ($booking->status === 'pending')
                <div class="d-flex flex-wrap gap-2">
                    <button type="button"
                            class="btn btn-success js-open-status-modal"
                            data-action="{{ route('teacher.bookings.updateStatus', $booking) }}"
                            data-status="confirmed"
                            data-title="تأكيد الحجز"
                            data-message="هل تريد تأكيد هذا الحجز؟"
                            data-note="سيتم إخطار الطالب فورًا بتأكيد الحجز.">
                        ✅ تأكيد الحجز
                    </button>

                    <button type="button"
                            class="btn btn-danger js-open-status-modal"
                            data-action="{{ route('teacher.bookings.updateStatus', $booking) }}"
                            data-status="cancelled"
                            data-title="إلغاء الحجز"
                            data-message="هل تريد إلغاء هذا الحجز؟"
                            data-note="سيتم إخطار الطالب فورًا بإلغاء الحجز.">
                        ❌ إلغاء الحجز
                    </button>
                </div>

            {{-- confirmed --}}
            @elseif ($booking->status === 'confirmed')
                <button type="button"
                        class="btn btn-danger js-open-status-modal"
                        data-action="{{ route('teacher.bookings.updateStatus', $booking) }}"
                        data-status="cancelled"
                        data-title="إلغاء الحجز"
                        data-message="هل تريد إلغاء هذا الحجز المؤكد؟"
                        data-note="سيتم إخطار الطالب فورًا بإلغاء الحجز.">
                    ❌ إلغاء الحجز المؤكد
                </button>

            @else
                <p class="text-muted mb-0">لا توجد إجراءات متاحة لهذه الحالة.</p>
            @endif

        </div>
    </div>

</div>

{{-- ✅ Modal حالة الحجز (confirm/cancel) --}}
<div class="modal fade" id="teacherStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-body text-center py-4 px-4">
                <div class="fs-1 mb-2" id="teacherStatusModalIcon">⚠️</div>

                <h5 class="mb-2" id="teacherStatusModalTitle">تأكيد</h5>

                <p class="text-muted mb-2" id="teacherStatusModalMessage">هل أنت متأكد؟</p>

                <div class="small text-muted mb-4" id="teacherStatusModalNote"></div>

                <div class="d-flex justify-content-center gap-3">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        إلغاء
                    </button>

                    <form id="teacherStatusModalForm" method="POST" class="m-0">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" id="teacherStatusModalStatus" value="">
                        <button type="submit" class="btn btn-primary" id="teacherStatusModalConfirmBtn">
                            نعم
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ✅ Modal قرار المعلّم على طلب الإلغاء (approve/reject) --}}
<div class="modal fade" id="teacherCancelRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-body text-center py-4 px-4">
                <div class="fs-1 mb-2" id="teacherCancelRequestModalIcon">🟡</div>

                <h5 class="mb-2" id="teacherCancelRequestModalTitle">قرار</h5>

                <p class="text-muted mb-2" id="teacherCancelRequestModalMessage">هل أنت متأكد؟</p>

                <div class="small text-muted mb-4" id="teacherCancelRequestModalNote"></div>

                <div class="d-flex justify-content-center gap-3">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        رجوع
                    </button>

                    <form id="teacherCancelRequestModalForm" method="POST" class="m-0">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-warning" id="teacherCancelRequestModalConfirmBtn">
                            تنفيذ
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // Modal status (confirm/cancel)
    // =========================
    const statusModalEl = document.getElementById('teacherStatusModal');
    if (statusModalEl && typeof bootstrap !== 'undefined') {

        const modal  = new bootstrap.Modal(statusModalEl);

        const form   = document.getElementById('teacherStatusModalForm');
        const status = document.getElementById('teacherStatusModalStatus');
        const title  = document.getElementById('teacherStatusModalTitle');
        const msg    = document.getElementById('teacherStatusModalMessage');
        const note   = document.getElementById('teacherStatusModalNote');
        const icon   = document.getElementById('teacherStatusModalIcon');
        const btn    = document.getElementById('teacherStatusModalConfirmBtn');

        let lastBtnText = 'نعم';

        document.querySelectorAll('.js-open-status-modal').forEach(el => {
            el.addEventListener('click', function () {
                const action  = this.getAttribute('data-action');
                const newStat = this.getAttribute('data-status');
                const t       = this.getAttribute('data-title') || 'تأكيد';
                const m       = this.getAttribute('data-message') || 'هل أنت متأكد؟';
                const n       = this.getAttribute('data-note') || '';

                form.action = action;
                status.value = newStat;
                title.textContent = t;
                msg.textContent = m;
                note.textContent = n;

                btn.classList.remove('btn-primary','btn-success','btn-danger');
                btn.disabled = false;

                if (newStat === 'confirmed') {
                    icon.textContent = '✅';
                    btn.classList.add('btn-success');
                    btn.textContent = 'نعم، تأكيد';
                } else if (newStat === 'cancelled') {
                    icon.textContent = '❌';
                    btn.classList.add('btn-danger');
                    btn.textContent = 'نعم، إلغاء';
                } else {
                    icon.textContent = '⚠️';
                    btn.classList.add('btn-primary');
                    btn.textContent = 'نعم';
                }

                lastBtnText = btn.textContent;
                modal.show();
            });
        });

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.textContent = 'جارٍ التنفيذ...';
        });

        statusModalEl.addEventListener('hidden.bs.modal', function () {
            btn.disabled = false;
            btn.textContent = lastBtnText || 'نعم';
        });
    }

    // =========================
    // Modal cancel request (approve/reject)
    // =========================
    const crEl = document.getElementById('teacherCancelRequestModal');
    if (crEl && typeof bootstrap !== 'undefined') {

        const crModal = new bootstrap.Modal(crEl);

        const crForm  = document.getElementById('teacherCancelRequestModalForm');
        const crTitle = document.getElementById('teacherCancelRequestModalTitle');
        const crMsg   = document.getElementById('teacherCancelRequestModalMessage');
        const crNote  = document.getElementById('teacherCancelRequestModalNote');
        const crIcon  = document.getElementById('teacherCancelRequestModalIcon');
        const crBtn   = document.getElementById('teacherCancelRequestModalConfirmBtn');

        let lastText = 'تنفيذ';

        document.querySelectorAll('.js-open-cancel-request-modal').forEach(el => {
            el.addEventListener('click', function () {
                const action = this.getAttribute('data-action');
                const t      = this.getAttribute('data-title') || 'قرار';
                const m      = this.getAttribute('data-message') || 'هل أنت متأكد؟';
                const n      = this.getAttribute('data-note') || '';

                crForm.action = action;
                crTitle.textContent = t;
                crMsg.textContent = m;
                crNote.textContent = n;

                crBtn.classList.remove('btn-warning','btn-success','btn-danger');
                crBtn.disabled = false;

                if (t.includes('قبول')) {
                    crIcon.textContent = '✅';
                    crBtn.classList.add('btn-success');
                    crBtn.textContent = 'نعم، قبول';
                } else if (t.includes('رفض')) {
                    crIcon.textContent = '❌';
                    crBtn.classList.add('btn-danger');
                    crBtn.textContent = 'نعم، رفض';
                } else {
                    crIcon.textContent = '🟡';
                    crBtn.classList.add('btn-warning');
                    crBtn.textContent = 'تنفيذ';
                }

                lastText = crBtn.textContent;
                crModal.show();
            });
        });

        crForm.addEventListener('submit', function () {
            crBtn.disabled = true;
            crBtn.textContent = 'جارٍ التنفيذ...';
        });

        crEl.addEventListener('hidden.bs.modal', function () {
            crBtn.disabled = false;
            crBtn.textContent = lastText || 'تنفيذ';
        });
    }

});
</script>
@endpush
