@extends('layouts.app')
@section('page_title', 'المعلّمون (ملفات التعريف)')

@push('styles')
<style>
    .status-card {
        border-radius: 14px;
        border: 1px solid rgba(0,0,0,.08);
        background: #fff;
    }
    .status-card .count {
        font-size: 1.8rem;
        font-weight: bold;
    }
    .filter-tabs .nav-link {
        border-radius: 20px;
        padding: .4rem 1rem;
    }
    .status-badge { min-width: 120px; text-align: center; }
    .missing-docs { font-size: .75rem; }
    .search-input { max-width: 320px; }
    .actions .btn { min-width: 84px; }

    /* ✅ تنبيه تأخير المراجعة */
    .review-delay-badge{
        font-size: .72rem;
        border-radius: 999px;
        padding: .25rem .5rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 1px solid rgba(0,0,0,.08);
        background: #fff3cd;
        color: #664d03;
        margin-top: 6px;
    }

    .stage-select { min-width: 180px; }
</style>
@endpush

@section('content')
<div class="container py-4">

    <h1 class="mb-4">المعلّمون (ملفات التعريف)</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        use App\Models\User;

        $totalCount    = User::where('role','teacher')->count();
        $pendingCount  = User::where('role','teacher')->where('teacher_status','pending')->count();
        $approvedCount = User::where('role','teacher')->where('teacher_status','approved')->count();
        $rejectedCount = User::where('role','teacher')->where('teacher_status','rejected')->count();

        $currentStatus = request('status');
        $currentQ      = request('q');
        $missingOn     = request('missing') == '1';

        // ✅ المرحلة: all|submitted|draft
        // (مهم: draft وليس drafts حتى يتوافق مع الكنترولر)
        $currentStage  = request('stage', 'all');

        // ✅ عدد أيام التأخير المسموح قبل إظهار التحذير
        $delayDays = 7;
    @endphp

    {{-- Counters --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="status-card p-3 text-center">
                <div class="count">{{ $totalCount }}</div>
                <div class="text-muted">الإجمالي</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card p-3 text-center">
                <div class="count text-warning">{{ $pendingCount }}</div>
                <div class="text-muted">قيد المراجعة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card p-3 text-center">
                <div class="count text-success">{{ $approvedCount }}</div>
                <div class="text-muted">مفعَّلون</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card p-3 text-center">
                <div class="count text-danger">{{ $rejectedCount }}</div>
                <div class="text-muted">مرفوضون</div>
            </div>
        </div>
    </div>

    {{-- Tabs + Filters --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

        <ul class="nav nav-pills filter-tabs gap-2 mb-0">
            <li class="nav-item">
                <a class="nav-link {{ !$currentStatus ? 'active' : '' }}"
                   href="{{ route('admin.teachers.index', array_filter(['stage'=>$currentStage,'q'=>$currentQ,'missing'=>$missingOn?1:null])) }}">
                    الكل
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus==='pending' ? 'active' : '' }}"
                   href="{{ route('admin.teachers.index', array_filter(['status'=>'pending','stage'=>$currentStage,'q'=>$currentQ,'missing'=>$missingOn?1:null])) }}">
                    قيد المراجعة
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus==='approved' ? 'active' : '' }}"
                   href="{{ route('admin.teachers.index', array_filter(['status'=>'approved','stage'=>$currentStage,'q'=>$currentQ,'missing'=>$missingOn?1:null])) }}">
                    مفعَّلون
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $currentStatus==='rejected' ? 'active' : '' }}"
                   href="{{ route('admin.teachers.index', array_filter(['status'=>'rejected','stage'=>$currentStage,'q'=>$currentQ,'missing'=>$missingOn?1:null])) }}">
                    مرفوضون
                </a>
            </li>
        </ul>

        <form id="teachersFilterForm"
              method="GET"
              action="{{ route('admin.teachers.index') }}"
              class="d-flex align-items-center gap-2">

            @if($currentStatus)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            {{-- ✅ Stage --}}
            <select name="stage" class="form-select stage-select" onchange="this.form.submit()">
                <option value="all" {{ $currentStage==='all' ? 'selected' : '' }}>المرحلة: الكل</option>
                <option value="submitted" {{ $currentStage==='submitted' ? 'selected' : '' }}>تم الإرسال فقط</option>
                <option value="draft" {{ $currentStage==='draft' ? 'selected' : '' }}>مسودات فقط</option>
            </select>

            {{-- ✅ Missing docs --}}
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="missingOnly" name="missing" value="1"
                       {{ $missingOn ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="form-check-label" for="missingOnly">
                    مستندات ناقصة فقط
                </label>
            </div>

            {{-- ✅ Search (server-side + instant filter on current page) --}}
            <input type="text" id="teacherSearch" name="q" value="{{ $currentQ }}" class="form-control search-input"
                   placeholder="بحث بالاسم أو الإيميل…">

            <button class="btn btn-outline-secondary" type="submit">بحث</button>
        </form>
    </div>

    @if($teachers->count() === 0)
        <div class="alert alert-info">لا يوجد معلّمون في هذه الفئة.</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="teachersTable">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>الإيميل</th>
                        <th>المادة</th>
                        <th>المرحلة</th>
                        <th>الحالة</th>
                        <th>مستندات</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($teachers as $teacher)
                        @php
                            $profile = $teacher->teacherProfile;
                            $status  = $teacher->teacher_status ?? 'pending';

                            $missingDocs = [];
                            if(!$profile?->profile_photo_path)   $missingDocs[] = 'صورة';
                            if(!$profile?->id_document_path)     $missingDocs[] = 'هوية';
                            if(!$profile?->teaching_permit_path) $missingDocs[] = 'تصريح';
                            $isMissing = count($missingDocs) ? 1 : 0;

                            $rejectionReason =
                                $profile->rejection_reason
                                ?? $profile->admin_rejection_reason
                                ?? null;

                            // ✅ المرحلة من submitted_at
                            $isSubmitted = !empty($profile?->submitted_at);

                            // ✅ التأخير: لو مُرسل نحسب من submitted_at، لو مسودة من created_at
                            $baseDate = $isSubmitted ? $profile->submitted_at : $teacher->created_at;
                            $daysWaiting = $baseDate ? now()->diffInDays($baseDate) : 0;
                            $isDelayed = ($status === 'pending' && $baseDate && $daysWaiting >= $delayDays);

                            /**
                             * ✅ المرحلة 4: الأزرار تعمل فقط لو:
                             * - تم الإرسال
                             * - والحالة pending
                             */
                            $canReview = $isSubmitted && $status === 'pending';

                            $reviewTooltip = !$isSubmitted
                                ? 'لا يمكن التفعيل/الرفض قبل إرسال الملف للمراجعة'
                                : ($status !== 'pending'
                                    ? 'تم اتخاذ قرار بالفعل (غير قابل للتعديل الآن)'
                                    : '');
                        @endphp

                        <tr
                            data-name="{{ strtolower($teacher->name ?? '') }}"
                            data-email="{{ strtolower($teacher->email ?? '') }}"
                        >
                            <td>{{ $teacher->id }}</td>

                            <td class="teacher-name">{{ $teacher->name }}</td>
                            <td class="teacher-email">{{ $teacher->email }}</td>
                            <td>{{ $profile->main_subject ?? '-' }}</td>

                            <td>
                                @if($isSubmitted)
                                    <span class="badge bg-primary">📩 تم الإرسال</span>
                                    <div class="text-muted small mt-1">
                                        {{ optional($profile->submitted_at)->format('Y-m-d') }}
                                    </div>
                                @else
                                    <span class="badge bg-secondary">📝 مسودة</span>
                                @endif
                            </td>

                            <td>
                                @if($status==='approved')
                                    <span class="badge bg-success status-badge">✔ مفعَّل</span>
                                @elseif($status==='rejected')
                                    <span class="badge bg-danger status-badge">
                                        ⛔ مرفوض
                                        @if($rejectionReason)
                                            <span title="{{ $rejectionReason }}" style="cursor:help;"> ⓘ</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark status-badge">⏳ قيد المراجعة</span>

                                    @if($isDelayed)
                                        <div class="review-delay-badge"
                                             title="قيد المراجعة منذ {{ optional($baseDate)->format('Y-m-d') }} ({{ $daysWaiting }} يوم)">
                                            ⚠️ متأخر {{ $daysWaiting }} يوم
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <td>
                                @if($isMissing)
                                    <span class="badge bg-warning text-dark missing-docs"
                                          title="ناقص: {{ implode('، ', $missingDocs) }}">
                                        مستندات ناقصة
                                    </span>
                                @else
                                    <span class="badge bg-success">مكتملة</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-flex flex-wrap gap-1 justify-content-end actions">

                                    <a href="{{ route('admin.teachers.show', $teacher->id) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        مراجعة
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.teachers.approve', $teacher->id) }}"
                                          onsubmit="return confirm('تأكيد تفعيل هذا المعلّم؟');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-success btn-sm"
                                                {{ $canReview ? '' : 'disabled' }}
                                                title="{{ $canReview ? 'تفعيل الحساب' : $reviewTooltip }}">
                                            تفعيل
                                        </button>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('admin.teachers.reject', $teacher->id) }}"
                                          class="reject-form">
                                        @csrf
                                        @method('PATCH')

                                        <input type="hidden" name="rejection_reason" value="">
                                        <input type="hidden" name="admin_note" value="">

                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                {{ $canReview ? '' : 'disabled' }}
                                                title="{{ $canReview ? 'رفض الحساب' : $reviewTooltip }}">
                                            رفض
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $teachers->links() }}
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {

    // ✅ رفض سريع: سبب + ملاحظة
    // مهم: لو الزر Disabled ماينفّذش أي prompts
    document.querySelectorAll('.reject-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && btn.disabled) {
                e.preventDefault();
                return;
            }

            const ok = confirm('تأكيد رفض هذا المعلّم؟');
            if (!ok) { e.preventDefault(); return; }

            const reason = prompt('سبب الرفض (مفضّل كتابته):', '') || '';
            const note   = prompt('ملاحظة للأدمن (اختياري):', '') || '';

            form.querySelector('input[name="rejection_reason"]').value = reason.trim();
            form.querySelector('input[name="admin_note"]').value = note.trim();
        });
    });

    // ✅ فلترة فورية أثناء الكتابة (على نتائج الصفحة الحالية)
    const searchInput = document.getElementById('teacherSearch');

    function rows() {
        return Array.from(document.querySelectorAll('#teachersTable tbody tr'));
    }

    function applyInstantFilter() {
        const q = (searchInput?.value || '').trim().toLowerCase();

        rows().forEach(row => {
            const name  = row.getAttribute('data-name')  || '';
            const email = row.getAttribute('data-email') || '';
            const match = (!q || name.includes(q) || email.includes(q));
            row.style.display = match ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyInstantFilter);
    }

    applyInstantFilter();

})();
</script>
@endpush
