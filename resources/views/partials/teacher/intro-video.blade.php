@php
    // ==============================
    // Teacher Intro Video (Shared)
    // ==============================

    /** @var \App\Models\User $teacher */
    $profile = $profile ?? ($teacher->teacherProfile ?? null);

    $videoType = $videoType ?? null; // youtube|vimeo|mp4
    $videoSrc  = $videoSrc  ?? null;

    // عنوان اختياري
    $title = $title ?? 'فيديو تعريفي';

    // لو ما اتبعتش جاهزين، نستنتجهم من rawVideo
    if (!$videoType || !$videoSrc) {
        $rawVideo =
            ($profile->intro_video_url ?? null)
            ?? ($profile->intro_video_path ?? null)
            ?? ($profile->intro_video ?? null)
            ?? ($teacher->intro_video_url ?? null)
            ?? ($teacher->intro_video_path ?? null)
            ?? ($teacher->intro_video ?? null);

        if (!empty($rawVideo)) {
            $url = trim($rawVideo);
            $isExternal = str_starts_with($url, 'http://') || str_starts_with($url, 'https://');

            // ✅ ملف محلي
            if (!$isExternal) {
                $u = ltrim($url, '/');
                if (str_starts_with($u, 'public/')) $u = substr($u, 7);

                $isStoragePrefixed = str_starts_with($u, 'storage/');
                $full = $isStoragePrefixed ? asset($u) : asset('storage/'.$u);

                if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $full)) {
                    $videoType = 'mp4';
                    $videoSrc  = $full;
                }
            } else {
                // ✅ mp4 خارجي
                if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
                    $videoType = 'mp4';
                    $videoSrc  = $url;
                }
                // ✅ YouTube
                elseif (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                    $videoType = 'youtube';
                    $vid = null;

                    if (preg_match('~youtube\.com/embed/([a-zA-Z0-9_-]+)~', $url, $m)) $vid = $m[1] ?? null;
                    if (!$vid && preg_match('~youtube\.com/shorts/([a-zA-Z0-9_-]+)~', $url, $m)) $vid = $m[1] ?? null;

                    if (!$vid && str_contains($url, 'youtu.be/')) {
                        $parsed = parse_url($url);
                        $vid = ltrim($parsed['path'] ?? '', '/');
                    }

                    if (!$vid && str_contains($url, 'youtube.com/watch')) {
                        $parsed = parse_url($url);
                        $query  = [];
                        parse_str($parsed['query'] ?? '', $query);
                        $vid = $query['v'] ?? null;
                    }

                    $videoSrc = $vid ? 'https://www.youtube.com/embed/'.$vid : null;
                    if (!$videoSrc) $videoType = null;
                }
                // ✅ Vimeo
                elseif (str_contains($url, 'vimeo.com')) {
                    $videoType = 'vimeo';
                    $vid = null;

                    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) $vid = $m[1] ?? null;

                    $videoSrc = $vid ? 'https://player.vimeo.com/video/'.$vid : null;
                    if (!$videoSrc) $videoType = null;
                }
            }
        }
    }
@endphp

@if(!empty($videoType) && !empty($videoSrc))
    <div class="card mb-3">
        <div class="card-header text-end">{{ $title }}</div>
        <div class="card-body">
            <div class="ratio ratio-16x9">
                @if($videoType === 'mp4')
                    <video
                        src="{{ $videoSrc }}"
                        controls
                        playsinline
                        style="width:100%; height:100%; object-fit: cover;"></video>
                @else
                    <iframe
                        src="{{ $videoSrc }}"
                        title="Intro video"
                        loading="lazy"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                    ></iframe>
                @endif
            </div>
        </div>
    </div>
@endif
