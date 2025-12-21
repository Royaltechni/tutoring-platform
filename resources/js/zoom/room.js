// resources/js/zoom/room.js

console.log("Room JS: Initializing...");

function initZoom() {
    if (typeof ZoomMtg !== 'undefined') {
        console.log("ZoomMtg Library: Detected ✅");
        ZoomMtg.setZoomJSLib('https://source.zoom.us/3.1.0/lib', '/av');
        ZoomMtg.preLoadWasm();
        ZoomMtg.prepareWebSDK();
        showJoinButton();
    } else {
        console.error("ZoomMtg Library: NOT FOUND! ❌");
        document.getElementById('status-text').innerText = "خطأ: فشل تحميل مكتبة زووم. يرجى تحديث الصفحة.";
    }
}

function showJoinButton() {
    const container = document.getElementById('zoom-meeting-container');
    const mn = window.__MEETING__.meetingNumber;

    if (mn && mn.length >= 9) {
        container.innerHTML = `
            <div class="text-center p-5 text-white">
                <h2 class="mb-4">غرفة الصف المباشرة</h2>
                <div class="bg-dark p-3 rounded mb-4 d-inline-block">
                    <p class="mb-1">رقم الاجتماع: <span class="text-info">${mn}</span></p>
                    <p class="mb-0">الاسم: <span class="text-info">${window.__MEETING__.userName}</span></p>
                </div>
                <br>
                <button id="btn-join" class="btn btn-primary btn-lg px-5 py-3 shadow-lg">
                    دخول الحصة الآن
                </button>
            </div>
        `;
        document.getElementById('btn-join').addEventListener('click', startMeeting);
    } else {
        document.getElementById('status-text').innerText = "خطأ: رقم الاجتماع غير صالح (" + (mn || 'فارغ') + ")";
        console.error("Invalid Meeting Number:", mn);
    }
}

async function startMeeting() {
    const btn = document.getElementById('btn-join');
    btn.disabled = true;
    btn.innerText = "جاري الاتصال...";

    try {
        const response = await fetch(window.__MEETING__.signatureUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                meetingNumber: window.__MEETING__.meetingNumber,
                role: window.__MEETING__.role
            })
        });

        const data = await response.json();
        if (!data.signature) throw new Error("Signature not found");

        ZoomMtg.init({
            leaveUrl: window.__MEETING__.leaveUrl,
            success: function() {
                ZoomMtg.join({
                    meetingNumber: window.__MEETING__.meetingNumber,
                    userName: window.__MEETING__.userName,
                    signature: data.signature,
                    sdkKey: window.__MEETING__.sdkKey,
                    passWord: window.__MEETING__.passWord,
                    success: () => { console.log("Joined successfully"); },
                    error: (err) => { alert("Join Error: " + err.errorMessage); btn.disabled = false; }
                });
            },
            error: (err) => { alert("Init Error: " + err.errorMessage); btn.disabled = false; }
        });
    } catch (error) {
        alert("Error: " + error.message);
        btn.disabled = false;
    }
}

// البدء عند جاهزية الصفحة
window.addEventListener('load', function() {
    // ننتظر قليلاً للتأكد من أن الـ CDN تم تنفيذه
    setTimeout(initZoom, 1000);
});