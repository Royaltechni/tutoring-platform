@extends('layouts.app')

@section('page_title', 'لا يمكن الدخول للاجتماع')

@section('content')
<div class="container py-5">
    <div class="card">
        <div class="card-body text-center">
            <div class="display-6 mb-2">⛔</div>
            <h4 class="mb-2">لا يمكن الدخول للاجتماع الآن</h4>

            @php $reason = $state['reason'] ?? 'unknown'; @endphp

            <p class="text-muted mb-3">
                السبب: <strong>{{ $reason }}</strong>
            </p>

            @if(in_array($reason, ['too_early','too_late'], true))
                <div class="small text-muted mb-3">
                    <div>يفتح عند: {{ optional($state['opens_at'] ?? null)->format('Y-m-d H:i') ?? '-' }}</div>
                    <div>يغلق عند: {{ optional($state['closes_at'] ?? null)->format('Y-m-d H:i') ?? '-' }}</div>
                    <div>Server Now: {{ optional($state['server_now'] ?? null)->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s') }}</div>
                </div>
            @endif

            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                رجوع
            </a>
        </div>
    </div>
</div>
@endsection
