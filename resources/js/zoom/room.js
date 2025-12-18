import ZoomMtgEmbedded from "@zoom/meetingsdk/embedded";
console.log("ROOM_JS_LOADED ✅", new Date().toISOString());

function qs(id) { return document.getElementById(id); }

function showOverlay(meta = "") {
  const el = qs("reconnectOverlay");
  const metaEl = qs("reconnectMeta");
  if (metaEl) metaEl.textContent = meta || "—";
  if (el) el.classList.add("show");
}

function hideOverlay() {
  const el = qs("reconnectOverlay");
  if (el) el.classList.remove("show");
}

function toast(msg) {
  const body = qs("appToastBody");
  const toastEl = qs("appToast");
  if (!toastEl || !body) return;
  body.textContent = msg;

  try {
    const t = window.bootstrap?.Toast?.getOrCreateInstance(toastEl, { delay: 4500 });
    t?.show();
  } catch (e) {
    console.log("Toast:", msg);
  }
}

async function fetchSignature({ meetingNumber, role }) {
  const url = window.__MEETING__?.signatureUrl;
  if (!url) throw new Error("signatureUrl is missing");

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

  const res = await fetch(url, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
    },
    body: JSON.stringify({ meetingNumber, role }),
  });

  if (!res.ok) {
    let details = "";
    try {
      const j = await res.json();
      details = j?.message ? ` ${j.message}` : JSON.stringify(j);
    } catch (e) {
      try { details = await res.text(); } catch (e2) {}
    }
    throw new Error(`Signature request failed: ${res.status}${details ? " - " + details : ""}`);
  }

  const data = await res.json();
  if (!data?.signature) throw new Error("Signature missing in response");
  return data.signature;
}

function installPopupSuppressor() {
  const keywords = ["Fail to join", "Signature is invalid", "signature is invalid", "fail to join"];

  const tryCloseInNode = (node) => {
    if (!node) return;

    const text = (node.innerText || node.textContent || "");
    const hit = keywords.some(k => text.includes(k));
    if (!hit) return;

    const buttons = Array.from(document.querySelectorAll("button"));
    const closeBtn = buttons.find(b => {
      const t = (b.innerText || "").trim().toLowerCase();
      return t === "close" || t === "ok" || t.includes("close") || t.includes("ok");
    });

    if (closeBtn) closeBtn.click();
    toast("حصل انقطاع بسيط… جارِ إعادة الاتصال تلقائيًا.");
  };

  const obs = new MutationObserver((mutations) => {
    for (const m of mutations) {
      for (const n of m.addedNodes || []) tryCloseInNode(n);
    }
  });

  obs.observe(document.documentElement, { childList: true, subtree: true });
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

function normMsg(err) {
  return (
    err?.reason ||
    err?.message ||
    err?.errorMessage ||
    (typeof err === "string" ? err : JSON.stringify(err, null, 2))
  );
}

function isSignatureInvalid(msg = "") {
  const t = String(msg || "").toLowerCase();
  return t.includes("signature is invalid") || (t.includes("signature") && t.includes("invalid"));
}

function isRetryable(msg = "") {
  const t = String(msg || "").toLowerCase();
  return t.includes("fail to join") || t.includes("timeout") || t.includes("connection") || t.includes("reconnect");
}

(async function boot() {
  const cfg = window.__MEETING__ || {};
  console.log("MEETING_READY", cfg);

  if (!cfg.meetingNumber) { toast("بيانات الاجتماع غير مكتملة: meetingNumber مفقود."); return; }
  if (!cfg.signatureUrl) { toast("بيانات الاجتماع غير مكتملة: signatureUrl مفقود."); return; }
  if (!cfg.sdkKey) { toast("بيانات الاجتماع غير مكتملة: sdkKey مفقود."); return; }

  installPopupSuppressor();

  const client = ZoomMtgEmbedded.createClient();
  const meetingContainer = document.getElementById("zoom-meeting-container");
  if (!meetingContainer) { toast("لا يمكن العثور على حاوية Zoom."); return; }

  await client.init({
    zoomAppRoot: meetingContainer,
    language: "en-US",
    customize: {
      video: { isResizable: true, viewSizes: { default: { width: "100%", height: "100%" } } },
      meetingInfo: ["topic", "host", "mn", "pwd"],
      toolbar: {
        buttons: [{
          text: "خروج",
          className: "btn btn-sm btn-outline-light",
          onClick: async () => {
            try { await client.leaveMeeting(); } catch (e) {}
            window.location.href = cfg.leaveUrl || "/";
          },
        }],
      },
    },
  });

  // Join guard
  let joinInProgress = false;

  async function joinOnce() {
    if (joinInProgress) return;
    joinInProgress = true;

    console.log("JOIN_ONCE_CALLED");
    const signature = await fetchSignature({ meetingNumber: String(cfg.meetingNumber), role: cfg.role ?? 0 });

    // ✅ Zoom example for component view includes sdkKey
    await client.join({
      sdkKey: cfg.sdkKey,
      signature,
      meetingNumber: String(cfg.meetingNumber),
      password: cfg.passWord || "",
      userName: cfg.userName || "User",
      userEmail: cfg.userEmail || undefined,
    });

    joinInProgress = false;
  }

  let attempt = 0;
  const maxAttempts = 4;

  while (attempt < maxAttempts) {
    try {
      attempt += 1;
      showOverlay(attempt === 1 ? "جارِ تجهيز الاتصال…" : `محاولة إعادة الاتصال رقم ${attempt} من ${maxAttempts}`);

      await joinOnce();

      hideOverlay();
      toast("تم الدخول للاجتماع.");
      break;

    } catch (err) {
      const msg = normMsg(err);
      console.error("Zoom join error (raw):", err);
      console.error("Zoom join error (msg):", msg);

      // stop duplicated join loop if it happened
      if (String(msg).toLowerCase().includes("duplicated join operation")) {
        hideOverlay();
        toast("تم إرسال طلب دخول بالفعل… انتظر لحظات.");
        break;
      }

      // stop if signature invalid (credentials issue)
      if (isSignatureInvalid(msg)) {
        hideOverlay();
        toast("التوقيع غير صحيح (Signature invalid). راجع Meeting SDK Key/Secret في .env.");
        break;
      }

      if (isRetryable(msg)) {
        showOverlay("انقطع الاتصال… جارِ إعادة الاتصال بتوقيع جديد.");
        const wait = 600 * Math.pow(2, attempt - 1);
        await sleep(wait);

        try { await client.leaveMeeting(); } catch (e) {}
        joinInProgress = false;
        continue;
      }

      hideOverlay();
      toast("تعذر الدخول للاجتماع. حاول مرة أخرى.");
      break;
    }
  }

  // Waiting room UX
  try {
    client.on("waiting-room-user-added", (payload) => {
      const name = payload?.userName || payload?.displayName || "طالب";
      toast(`طلب دخول جديد من: ${name}`);
    });
    client.on("waiting-room-user-removed", (payload) => {
      const name = payload?.userName || payload?.displayName || "طالب";
      toast(`تم التعامل مع طلب الدخول: ${name}`);
    });
  } catch (e) {}
})();
