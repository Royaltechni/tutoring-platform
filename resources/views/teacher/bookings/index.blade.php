@extends('layouts.teacher')

@section('page_title', 'حجوزات دروسي (كمعلّم)')

@push('styles')
<style>
    .modal { z-index: 200000 !important; pointer-events: auto !important; }
    .modal-backdrop { z-index: 199999 !important; }

    .modal, .modal *{
        opacity: 1 !important;
        filter: none !important;
        pointer-events: auto !important;
    }

    .modal .modal-content{
        border: 0;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
    }
</style>
@endpush

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="mb-1">حجوزاتي</h2>
            <p class="text-muted mb-0 small">
                هنا يمكنك استعراض جميع الحجوزات الخاصة بك وتأكيدها أو إلغاؤها.
            </p>
        </div>
    </div>

    @php
        $statusLabels = [
            ''           => 'الكل',
            'pending'    => 'قيد الانتظار',
            'confirmed'  => 'مؤكد',
            'cancelled'  => 'ملغى',
        ];
    @endphp

    {{-- فلاتر الحالة --}}
    <div class="mb-3">
        <div class="btn-group">
            @foreach ($statusLabels as $value => $label)
                <a href="{{ route('teacher.bookings.index', $value ? ['status' => $value] : []) }}"
                   class="btn btn-sm {{ ($status ?? '') === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card border-0 shadow-sm">
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
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            @php
                                $statusClass = match($booking->status) {
                                    'confirmed' => 'success',
                                    'cancelled', 'canceled' => 'danger',
                                    'pending' => 'warning',
                                    default => 'secondary',
                                };

                                $studentName = optional($booking->student)->name ?? 'طالب';
                            @endphp
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>{{ $studentName }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ $booking->status }}
                                    </span>
                                </td>
                                <td>{{ $booking->total_amount ?? '-' }}</td>
                                <td>{{ optional($booking->created_at)->format('Y-m-d H:i') }}</td>
                                <td class="d-flex flex-wrap gap-1">

                                    <a href="{{ route('teacher.bookings.show', $booking) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        تفاصيل
                                    </a>

                                    @if ($booking->status === 'pending')
                                        <button type="button"
                                                class="btn btn-sm btn-success js-open-status-modal"
                                                data-action="{{ route('teacher.bookings.updateStatus', $booking) }}"
                                                data-status="confirmed"
                                                data-title="تأكيد الحجز"
                                                data-message="هل تريد تأكيد هذا الحجز؟"
                                                data-note="سيتم إخطار الطالب فورًا بتأكيد الحجز.">
                                            تأكيد
                                        </button>

                                        <button type="button"
                                                class="btn btn-sm btn-danger js-open-status-modal"
                                                data-action="{{ route('teacher.bookings.updateStatus', $booking) }}"
                                                data-status="cancelled"
                                                data-title="إلغاء الحجز"
                                                data-message="هل تريد إلغاء هذا الحجز؟"
                                                data-note="سيتم إخطار الطالب فورًا بإلغاء الحجز.">
                                            إلغاء
                                        </button>

                                    @elseif ($booking->status === 'confirmed')
                                        <button type="button"
                                                class="btn btn-sm btn-danger js-open-status-modal"
                                                data-action="{{ route('teacher.bookings.updateStatus', $booking) }}"
                                                data-status="cancelled"
                                                data-title="إلغاء الحجز"
                                                data-message="هل تريد إلغاء هذا الحجز المؤكد؟"
                                                data-note="سيتم إخطار الطالب فورًا بإلغاء الحجز.">
                                            إلغاء
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    لا توجد حجوزات مطابقة للبحث.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($bookings instanceof \Illuminate\Contracts\Pagination\Paginator)
                <div class="p-3">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ✅ Modal محسّن --}}
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('teacherStatusModal');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);

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

    modalEl.addEventListener('hidden.bs.modal', function () {
        btn.disabled = false;
        btn.textContent = lastBtnText || 'نعم';
    });
});
</script>
@endpush
