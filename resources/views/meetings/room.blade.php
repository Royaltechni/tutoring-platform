@extends('layouts.app')

@section('page_title', 'غرفة الصف المباشرة')

@section('content')

<!-- شاشة الانتظار الاحترافية -->
<div id="loading-screen" style="background: #0b1220; min-height: 100vh; width: 100%; position: fixed; top: 0; left: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; flex-direction: column; text-white">
    <div id="loader-content" class="text-center">
        <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status"></div>
        <h2 class="mt-4 fw-bold" style="color: #fff;">جاري تحضير الغرفة المباشرة</h2>
        <p id="status-text" style="color: #aaa;">يتم الآن الاتصال بسيرفرات زووم، يرجى الانتظار ثواني...</p>
    </div>
</div>

<!-- تحميل المكتبات الأساسية -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/redux/4.2.1/redux.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/redux-thunk/2.4.2/redux-thunk.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"></script>

<!-- مكتبة زووم الرسمية إصدار 3.11.0 -->
<script src="https://source.zoom.us/zoom-meeting-3.11.0.min.js"></script>

<script>
    // 1. إعدادات الاجتماع (تأكدي أن الـ Signature Route والـ SDK Key في الـ config صحيحة)
    const ZOOM_DATA = {
        mn: "{{ $meeting->provider_meeting_number ?? '' }}",
        pwd: "{{ $meeting->provider_passcode ? \Illuminate\Support\Facades\Crypt::decryptString($meeting->provider_passcode) : '' }}",
        name: "{{ auth()->user()->name }}",
        role: {{ in_array(auth()->user()->role, ['admin', 'teacher']) ? 1 : 0 }},
        sdkKey: "{{ config('services.zoom.sdk_key') }}", 
        signatureUrl: "{{ route('zoom.signature') }}",
        leaveUrl: "{{ url('/dashboard') }}"
    };

    // 2. وظيفة تهيئة النظام وتحميل موارد زووم
    function initSystem() {
        if (typeof ZoomMtg !== 'undefined') {
            console.log("Zoom SDK Loaded. Preparing...");
            
            ZoomMtg.setZoomJSLib('https://source.zoom.us/3.11.0/lib', '/av');
            ZoomMtg.preLoadWasm();
            ZoomMtg.prepareWebSDK();

            // البدء التلقائي بعد التحميل مباشرة
            setTimeout(() => {
                startMeeting();
            }, 1500);
        } else {
            console.log("Waiting for Zoom SDK...");
            setTimeout(initSystem, 1000);
        }
    }

    // 3. وظيفة جلب التوقيع وبدء الاجتماع فعلياً
    async function startMeeting() {
        const status = document.getElementById('status-text');
        status.innerText = "جاري تأمين الاتصال وتوليد التوقيع الرقمي...";

        try {
            const response = await fetch(ZOOM_DATA.signatureUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    meetingNumber: ZOOM_DATA.mn,
                    role: ZOOM_DATA.role
                })
            });

            const data = await response.json();

            if (!data.signature) {
                throw new Error(data.error || "فشل في توليد التوقيع");
            }

            status.innerText = "تم الاتصال! جاري الدخول للغرفة الآن...";

            // تنفيذ الـ Init والـ Join
            ZoomMtg.init({
                leaveUrl: ZOOM_DATA.leaveUrl,
                patchJsMedia: true, // سطر سحري لحل تعليقة "جاري الاتصال"
                success: function() {
                    // إظهار شاشة زووم المخفية
                    document.getElementById('zmmtg-root').style.display = 'block';
                    
                    ZoomMtg.join({
                        meetingNumber: ZOOM_DATA.mn,
                        userName: ZOOM_DATA.name,
                        signature: data.signature,
                        sdkKey: ZOOM_DATA.sdkKey,
                        passWord: ZOOM_DATA.pwd,
                        success: (res) => {
                            console.log("Successfully joined the meeting");
                            // إخفاء شاشة التحميل تماماً بمجرد الدخول
                            document.getElementById('loading-screen').style.display = 'none';
                        },
                        error: (err) => {
                            console.error("Join Error:", err);
                            alert("حدث خطأ أثناء الانضمام: " + (err.errorMessage || "تأكد من بيانات الميتنج"));
                            location.reload();
                        }
                    });
                },
                error: (err) => {
                    console.error("Init Error:", err);
                    alert("خطأ في تهيئة النظام: " + err.errorMessage);
                }
            });

        } catch (e) {
            console.error("Signature Fetch Error:", e);
            status.innerHTML = `<span class="text-danger">فشل الاتصال: ${e.message}</span><br><a href="javascript:location.reload()" class="btn btn-sm btn-primary mt-2">إعادة المحاولة</a>`;
        }
    }

    // بدء التشغيل عند تحميل الصفحة
    window.onload = initSystem;
</script>

<style>
    /* إعدادات هامة جداً لزووم في المتصفح */
    #zmmtg-root { 
        display: none; /* يظهر فقط عند نجاح الـ Init */
        width: 100%; 
        height: 100%; 
        position: fixed; 
        top: 0; 
        left: 0; 
        z-index: 100000;
        background-color: #000;
    }

    /* إصلاح تداخل الفريمات في بعض المتصفحات */
    body { 
        overflow: hidden !important; 
        background-color: #000;
    }

    .spinner-border {
        border-width: 0.25em;
    }
</style>

@endsection