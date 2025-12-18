{{-- ✅ Success Message --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm"
         role="alert"
         data-auto-hide="true">
        <div class="fs-4">✅</div>
        <div>
            <strong>تم بنجاح</strong><br>
            {{ session('success') }}
        </div>
        <button type="button"
                class="btn-close ms-auto"
                data-bs-dismiss="alert"
                aria-label="Close"></button>
    </div>
@endif

{{-- ℹ️ Info Message --}}
@if (session('info'))
    <div class="alert alert-info alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm"
         role="alert"
         data-auto-hide="true">
        <div class="fs-4">ℹ️</div>
        <div>
            <strong>معلومة</strong><br>
            {{ session('info') }}
        </div>
        <button type="button"
                class="btn-close ms-auto"
                data-bs-dismiss="alert"
                aria-label="Close"></button>
    </div>
@endif

{{-- ⚠️ Warning Message --}}
@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm"
         role="alert"
         data-auto-hide="true">
        <div class="fs-4">⚠️</div>
        <div>
            <strong>تنبيه</strong><br>
            {{ session('warning') }}
        </div>
        <button type="button"
                class="btn-close ms-auto"
                data-bs-dismiss="alert"
                aria-label="Close"></button>
    </div>
@endif

{{-- ❌ Error Message --}}
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm"
         role="alert"
         data-auto-hide="true">
        <div class="fs-4">❌</div>
        <div>
            <strong>حدث خطأ</strong><br>
            {{ session('error') }}
        </div>
        <button type="button"
                class="btn-close ms-auto"
                data-bs-dismiss="alert"
                aria-label="Close"></button>
    </div>
@endif

{{-- ⚠️ Validation Errors --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm"
         role="alert"
         data-auto-hide="false">
        <div class="d-flex align-items-start gap-2">
            <div class="fs-4">⚠️</div>
            <div>
                <strong>يرجى مراجعة البيانات التالية:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"></button>
    </div>
@endif

{{-- ✅ Auto hide alerts (success / info / warning / error only) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert[data-auto-hide="true"]').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 3500); // 3.5 ثواني
    });
});
</script>
