{{-- ROOM_BLADE_LOADED --}}

@extends('layouts.app')

@section('page_title', 'غرفة الاجتماع')

@section('content')

<style>
  :root{
    --zoom-bg:#0b1220;
    --top-offset: 72px; /* غيّرها لو ارتفاع الهيدر عندك مختلف */
  }

  /* ✅ مهم: ما نلمسش html/body عشان ما نبوّظش صفحات تانية */
  .meeting-page{
    min-height: calc(100vh - var(--top-offset));
    background: linear-gradient(180deg, #0b1220 0%, #0a0f1a 100%);
    padding: 12px 0;
    overflow: hidden; /* ✅ يمنع scroll داخل صفحة الاجتماع فقط */
  }

  .zoom-shell{
    position: relative;
    width: 100%;
    height: calc(100vh - var(--top-offset) - 24px); /* - padding top/bottom */
    overflow: hidden;
    border-radius: 18px;
    background: rgba(255,255,255,.04);
    box-shadow: 0 18px 60px rgba(0,0,0,.35);
    border: 1px solid rgba(255,255,255,.08);
  }

  /* fallback لو المتصفح ما فهمش calc */
  @supports not (height: calc(100vh - 1px)) {
    .zoom-shell { height: 100vh; }
    .meeting-page { min-height: 100vh; }
  }

  #zoom-meeting-container{ width: 100%; height: 100%; }

  .reconnect-overlay{
    position:absolute; inset:0; z-index: 99999;
    display:none; align-items:center; justify-content:center;
    background: rgba(7,12,20,.72);
    backdrop-filter: blur(6px);
  }
  .reconnect-overlay.show{ display:flex; }

  .reconnect-card{
    width:min(520px,92vw);
    border-radius:18px;
    padding:18px;
    background: rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.1);
    color:#fff;
  }

  .spinner{
    width:18px;height:18px;border-radius:50%;
    border:2px solid rgba(255,255,255,.35);
    border-top-color:#fff;
    animation:spin .9s linear infinite;
  }
  @keyframes spin{to{transform:rotate(360deg)}}

  .toast-container{z-index:100000;}
</style>

@php
  $booking = $meeting->booking ?? ($booking ?? null);

  $meetingArr = is_object($meeting) && method_exists($meeting, 'toArray') ? $meeting->toArray() : (array)$meeting;
  $bookingArr = is_object($booking) && method_exists($booking, 'toArray') ? $booking->toArray() : (array)$booking;

  $urlCandidates = array_filter([
      data_get($meetingArr, 'provider_start_url'),
      data_get($meetingArr, 'provider_join_url'),
      data_get($meetingArr, 'start_url'),
      data_get($meetingArr, 'join_url'),
      data_get($meetingArr, 'meeting_start_url'),
      data_get($meetingArr, 'meeting_join_url'),

      data_get($bookingArr, 'provider_start_url'),
      data_get($bookingArr, 'provider_join_url'),
      data_get($bookingArr, 'start_url'),
      data_get($bookingArr, 'join_url'),
      data_get($bookingArr, 'meeting_start_url'),
      data_get($bookingArr, 'meeting_join_url'),
  ]);

  $anyUrl = $urlCandidates ? array_values($urlCandidates)[0] : null;

  // ✅ الأولوية لحقول الـ provider_* الموجودة عندك في DB
  $mn = data_get($meetingArr, 'provider_meeting_number')
     ?? data_get($meetingArr, 'provider_meeting_id')
     ?? data_get($meetingArr, 'meeting_number')
     ?? data_get($meetingArr, 'zoom_meeting_number')
     ?? data_get($meetingArr, 'zoom_meeting_id')
     ?? data_get($meetingArr, 'meeting_id')
     ?? data_get($meetingArr, 'meeting_no')
     ?? data_get($meetingArr, 'meeting_num')
     ?? data_get($meetingArr, 'mn')
     ?? data_get($meetingArr, 'meta.meeting_id')
     ?? data_get($meetingArr, 'meta.zoom_meeting_id')
     ?? data_get($meetingArr, 'payload.meeting_id')

     ?? data_get($bookingArr, 'provider_meeting_number')
     ?? data_get($bookingArr, 'provider_meeting_id')
     ?? data_get($bookingArr, 'meeting_number')
     ?? data_get($bookingArr, 'zoom_meeting_number')
     ?? data_get($bookingArr, 'zoom_meeting_id')
     ?? data_get($bookingArr, 'meeting_id')
     ?? data_get($bookingArr, 'meeting_no')
     ?? data_get($bookingArr, 'meeting_num')
     ?? data_get($bookingArr, 'meta.meeting_id')
     ?? data_get($bookingArr, 'meta.zoom_meeting_id')
     ?? data_get($bookingArr, 'payload.meeting_id')
     ?? null;

  if (!$mn && $anyUrl) {
      $path = parse_url($anyUrl, PHP_URL_PATH) ?: '';
      if (preg_match('~/(?:wc/)?j/(\d+)~', $path, $m)) {
          $mn = $m[1];
      } else {
          $query = parse_url($anyUrl, PHP_URL_QUERY) ?: '';
          parse_str($query, $qs);
          $mn = $qs['confno'] ?? $qs['meetingNumber'] ?? $qs['meeting_id'] ?? $mn;
      }
  }

  if (!$mn && !empty($bookingArr)) {
      foreach ($bookingArr as $k => $v) {
          if (is_array($v)) continue;
          $sv = is_scalar($v) ? (string)$v : '';
          if ($sv !== '' && preg_match('/^\d{9,12}$/', $sv)) {
              if (preg_match('/meeting|zoom|provider/i', (string)$k)) {
                  $mn = $sv;
                  break;
              }
          }
      }
      if (!$mn) {
          foreach ($bookingArr as $k => $v) {
              if (is_array($v)) continue;
              $sv = is_scalar($v) ? (string)$v : '';
              if ($sv !== '' && preg_match('/^\d{9,12}$/', $sv)) {
                  $mn = $sv;
                  break;
              }
          }
      }
  }

  // ✅ تنظيف رقم الميتنج (لازم أرقام فقط)
  if ($mn !== null) {
      $mn = preg_replace('/\D+/', '', (string)$mn);
      if ($mn === '') $mn = null;
  }

  // ✅ الأولوية لـ provider_passcode
  $pwd = data_get($meetingArr, 'provider_passcode')
      ?? data_get($meetingArr, 'password')
      ?? data_get($meetingArr, 'passcode')
      ?? data_get($meetingArr, 'zoom_password')
      ?? data_get($meetingArr, 'meeting_password')
      ?? data_get($meetingArr, 'meta.password')
      ?? data_get($meetingArr, 'meta.passcode')

      ?? data_get($bookingArr, 'provider_passcode')
      ?? data_get($bookingArr, 'password')
      ?? data_get($bookingArr, 'passcode')
      ?? data_get($bookingArr, 'zoom_password')
      ?? data_get($bookingArr, 'meeting_password')
      ?? data_get($bookingArr, 'meta.password')
      ?? data_get($bookingArr, 'meta.passcode')
      ?? '';

  if (!$pwd && $anyUrl) {
      $query = parse_url($anyUrl, PHP_URL_QUERY) ?: '';
      parse_str($query, $qs);
      $pwd = $qs['pwd'] ?? '';
  }

  $roleVal = $role ?? 0;

  $mn  = $mn !== null ? (string)$mn : null;
  $pwd = $pwd !== null ? (string)$pwd : '';

  // ✅ sdkKey من config أولاً ثم env
  $sdkKey = config('services.zoom.sdk_key') ?: env('ZOOM_MEETING_SDK_KEY');

  // ✅ Signature URL: استخدم الموجود فعلياً عندك بدون ما يكسر الصفحة
  $signatureUrl = \Illuminate\Support\Facades\Route::has('zoom.sdk.signature')
      ? route('zoom.sdk.signature')
      : route('zoom.signature');

  // ✅ هل البيئة Production؟
  $isProd = app()->environment('production');
@endphp

{{-- ✅ Debug element (آمن) --}}
<div
  id="meeting-debug"
  data-mn="{{ $mn ?? '' }}"
  data-role="{{ $roleVal }}"
  data-has-pwd="{{ $pwd ? '1' : '0' }}"
  style="display:none"
></div>

<script>
  // ✅ تجميعة إعدادات واحدة للـ SDK
  window.__MEETING__ = {
    meetingNumber: @json($mn),
    passWord: @json($pwd),
    userName: @json(auth()->user()->name ?? 'User'),
    userEmail: @json(auth()->user()->email ?? null),
    role: @json($roleVal),

    signatureUrl: @json($signatureUrl),
    sdkKey: @json($sdkKey),
    leaveUrl: @json(url('/')),
  };

  // ✅ تنظيف Console في Production فقط (بدون التأثير على Local)
  window.__ZOOM_PROD__ = @json($isProd);

  if (window.__ZOOM_PROD__) {
    (function(){
      const safe = console.error.bind(console); // نخلي الأخطاء بس
      console.log = function(){};
      console.info = function(){};
      console.warn = function(){};
      // لو تحب نخلي error فقط:
      console.error = safe;
    })();
  }

  // للتشخيص في local
  if (!window.__ZOOM_PROD__) {
    console.log('MEETING_READY', window.__MEETING__);
  }
</script>

<div class="meeting-page">
  <div class="container-fluid">
    <div class="zoom-shell">
      <div id="zoom-meeting-container"></div>

      <div id="reconnectOverlay" class="reconnect-overlay">
        <div class="reconnect-card">
          <strong>جارِ إعادة الاتصال…</strong>
          <div class="d-flex align-items-center gap-2 mt-2">
            <span class="spinner"></span>
            <span id="reconnectMeta">—</span>
          </div>
        </div>
      </div>

      <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="appToast" class="toast text-bg-dark border-0">
          <div class="d-flex">
            <div class="toast-body" id="appToastBody"></div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

@vite(['resources/js/zoom/room.js'])

@endsection
