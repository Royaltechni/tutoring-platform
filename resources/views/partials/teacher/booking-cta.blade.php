<div class="card mt-3" dir="rtl">
    <div class="card-body">
        {{-- ✅ في RTL: start = يمين / end = يسار --}}
        <div class="d-flex gap-2 justify-content-start">

            @php
                $btnClass = 'btn';
                $fixedW   = 'min-width:140px;';
            @endphp

            @if($canOnline)
                <a style="{{ $fixedW }}" href="{{ url('/student/bookings/create?teacher_id=' . $teacher->id . '&mode=online') }}"
                   class="btn btn-primary">
                    احجز أونلاين
                </a>
            @else
                <button style="{{ $fixedW }}" class="btn btn-primary" disabled>احجز أونلاين</button>
            @endif

            @if($canOnsite)
                <a style="{{ $fixedW }}" href="{{ url('/student/bookings/create?teacher_id=' . $teacher->id . '&mode=onsite') }}"
                   class="btn btn-primary">
                    احجز حضوري
                </a>
            @else
                <button style="{{ $fixedW }}" class="btn btn-primary" disabled>احجز حضوري</button>
            @endif

            <button style="{{ $fixedW }}" type="button" class="btn btn-outline-secondary" disabled>
                إرسال رسالة (قريبًا)
            </button>

            @if($teacher->offers_trial ?? false)
                <button style="{{ $fixedW }}" type="button" class="btn btn-success" disabled>
                    حصة تجريبية (قريبًا)
                </button>
            @endif

        </div>
    </div>
</div>
