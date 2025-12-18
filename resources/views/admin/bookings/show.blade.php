@extends('layouts.app')

@section('page_title', 'تفاصيل الحجز (أدمن)')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="mb-1">تفاصيل الحجز #{{ $booking->id }}</h2>
            <div class="text-muted small">UUID: {{ $booking->uuid ?? '-' }}</div>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
            ← رجوع إلى الحجوزات
        </a>
    </div>

    @php
        $status = $booking->status ?? '';
        $statusClass = match($status) {
            'confirmed' => 'success',
            'pending' => 'warning',
            'cancelled','canceled' => 'danger',
            default => 'secondary',
        };

        // meeting (قد لا يكون محمّل - نعمل load داخل الصفحة)
        $booking->loadMissing(['meeting','student','teacherProfile.user']);
        $meeting = $booking->meeting;
    @endphp

    {{-- بيانات عامة --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">بيانات الحجز</div>
                <div class="card-body">
                    <p class="mb-1"><strong>الحالة:</strong> <span class="badge bg-{{ $statusClass }}">{{ $status }}</span></p>
                    <p class="mb-1"><strong>تاريخ الإنشاء:</strong> {{ optional($booking->created_at)->format('Y-m-d H:i') ?? '-' }}</p>
                    <p class="mb-1"><strong>وقت أول حصة:</strong> {{ optional($booking->first_lesson_at)->format('Y-m-d H:i') ?? '-' }}</p>
                    <p class="mb-1"><strong>المدة:</strong> {{ $booking->duration_minutes ?? '-' }} دقيقة</p>
                    <p class="mb-0"><strong>المبلغ:</strong> {{ $booking->total_amount ?? '-' }} {{ $booking->currency ?? 'AED' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">الأطراف</div>
                <div class="card-body">
                    <p class="mb-1"><strong>الطالب:</strong> {{ optional($booking->student)->name ?? '-' }} ({{ optional($booking->student)->email ?? '-' }})</p>
                    <p class="mb-0"><strong>المعلّم:</strong> {{ optional(optional($booking->teacherProfile)->user)->name ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- بلوك الاجتماع --}}
    <div class="card mb-4 border-success">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Meeting</span>
            <div class="d-flex align-items-center gap-2">
                @if($meeting)
                    <span class="badge bg-light text-success">Status: {{ $meeting->status }}</span>
                @else
                    <span class="badge bg-light text-dark">لم يتم إنشاؤه بعد</span>
                @endif
            </div>
        </div>

        <div class="card-body">

            @if(!$meeting)
                <div class="alert alert-warning mb-3">
                    لا يوجد Meeting مرتبط بالحجز بعد. سيتم إنشاؤه تلقائيًا عند تحويل الحجز إلى confirmed.
                </div>
            @else
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-2">التحكم الزمني (Server)</div>
                            <div class="small text-muted">
                                <div><strong>Scheduled:</strong>
                                    {{ optional($meeting->scheduled_start_at)->format('Y-m-d H:i') ?? '-' }}
                                    →
                                    {{ optional($meeting->scheduled_end_at)->format('Y-m-d H:i') ?? '-' }}
                                </div>
                                <div><strong>Join Window:</strong>
                                    {{ optional($meeting->allow_join_from)->format('Y-m-d H:i') ?? '-' }}
                                    →
                                    {{ optional($meeting->allow_join_until)->format('Y-m-d H:i') ?? '-' }}
                                </div>
                                <div><strong>Server Now:</strong> {{ now()->format('Y-m-d H:i:s') }}</div>
                            </div>

                            <div class="mt-3">
                                <a class="btn btn-outline-success"
                                   href="{{ route('meetings.room', $booking->id) }}"
                                   target="_blank">
                                    فتح غرفة الاجتماع (كأدمن)
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-2">التسجيل (إجباري + تحت تحكم الأدمن)</div>

                            <div class="mb-2">
                                <span class="badge bg-warning text-dark">Recording Required</span>
                                @if($meeting->recording_admin_enabled)
                                    <span class="badge bg-success">Enabled</span>
                                @else
                                    <span class="badge bg-danger">Disabled</span>
                                @endif
                                <span class="badge bg-light text-dark">Status: {{ $meeting->recording_status }}</span>
                            </div>

                            <form class="d-flex gap-2 flex-wrap" method="POST"
                                  action="{{ route('admin.bookings.meeting.toggleRecording', $booking->id) }}">
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="enabled" value="{{ $meeting->recording_admin_enabled ? 0 : 1 }}">
                                <button type="submit"
                                        class="btn {{ $meeting->recording_admin_enabled ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                    {{ $meeting->recording_admin_enabled ? 'إيقاف التسجيل (منع)' : 'تفعيل التسجيل (سماح)' }}
                                </button>
                            </form>

                            <hr>

                            <form class="d-flex gap-2 flex-wrap align-items-center" method="POST"
                                  action="{{ route('admin.bookings.meeting.extend', $booking->id) }}">
                                @csrf
                                @method('PUT')
                                <label class="small text-muted mb-0">تمديد (دقائق):</label>
                                <input type="number" name="minutes" class="form-control" style="max-width:120px" min="1" max="180" value="10">
                                <button type="submit" class="btn btn-outline-success">تمديد</button>
                            </form>

                            <form class="mt-3" method="POST"
                                  action="{{ route('admin.bookings.meeting.forceEnd', $booking->id) }}">
                                @csrf
                                @method('PUT')
                                <div class="input-group">
                                    <input type="text" name="reason" class="form-control" placeholder="سبب الإنهاء (اختياري)">
                                    <button type="submit" class="btn btn-outline-danger">إنهاء الاجتماع فورًا</button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- إعدادات اجتماع على مستوى الحجز (اختياري) --}}
    <div class="card mb-4">
        <div class="card-header">إعدادات الاجتماع للحجز (اختياري)</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.bookings.meeting.settings', $booking->id) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-4">
                    <label class="form-label">Early Join (دقائق)</label>
                    <input type="number" min="0" max="60" name="meeting_early_join_minutes" class="form-control"
                           value="{{ $booking->meeting_early_join_minutes ?? 10 }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Grace After (دقائق)</label>
                    <input type="number" min="0" max="60" name="meeting_grace_after_minutes" class="form-control"
                           value="{{ $booking->meeting_grace_after_minutes ?? 10 }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Duration Override (دقائق)</label>
                    <input type="number" min="15" max="240" name="meeting_duration_minutes" class="form-control"
                           value="{{ $booking->meeting_duration_minutes ?? ($booking->duration_minutes ?? 60) }}">
                </div>

                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="meeting_notes" class="form-control" rows="2">{{ $booking->meeting_notes ?? '' }}</textarea>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">حفظ الإعدادات</button>
                </div>
            </form>

            <div class="small text-muted mt-2">
                حفظ الإعدادات سيؤثر على نافذة الدخول (Server) بعد إعادة حساب الـ Meeting.
            </div>
        </div>
    </div>

</div>
@endsection
