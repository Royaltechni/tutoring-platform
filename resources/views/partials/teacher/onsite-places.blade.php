<div class="card mb-3">
    <div class="card-header text-end">أماكن الحصص الحضورية</div>
    <div class="card-body text-end">
        @if($canOnsite)
            <p class="mb-2"><strong>الدولة:</strong> {{ $countryName }}</p>

            <div class="mb-2">
                <strong>المدن:</strong>
                <div class="mt-2 d-flex flex-wrap gap-2 justify-content-start">
                    @if(!empty($cityNames) && count($cityNames))
                        @foreach($cityNames as $cn)
                            <span class="badge bg-secondary">{{ $cn }}</span>
                        @endforeach
                    @elseif(!empty($cityTextFallback))
                        <span class="badge bg-secondary">{{ $cityTextFallback }}</span>
                    @else
                        <span class="text-muted small">لم تُحدّد المدن بعد.</span>
                    @endif
                </div>
            </div>
        @else
            <p class="text-muted mb-0">هذا المعلّم لا يقدّم حصصًا حضورية حاليًا.</p>
        @endif
    </div>
</div>
