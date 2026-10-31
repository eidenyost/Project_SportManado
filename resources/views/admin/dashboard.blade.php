@extends('layouts.admin')

@section('title', 'Dashboard Admin – SportManado')

@push('styles')
<style>
    .status-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .25rem .75rem; border-radius: 9999px;
        font-size: .7rem; font-weight: 700; letter-spacing: .02em;
    }
    .badge-active     { background: rgba(16,185,129,.1); color: #10b981; border: 1px solid rgba(16,185,129,.3); }
    .badge-pending    { background: rgba(245,158,11,.1);  color: #f59e0b; border: 1px solid rgba(245,158,11,.3); }
    .badge-rejected   { background: rgba(239,68,68,.1);   color: #ef4444; border: 1px solid rgba(239,68,68,.3); }
    .badge-cancelled  { background: rgba(239,68,68,.08);  color: #ef4444; border: 1px solid rgba(239,68,68,.2); }
    .badge-draft      { background: rgba(255,255,255,.05); color: #94a3b8; border: 1px solid rgba(255,255,255,.1); }
    .badge-confirmed  { background: rgba(16,185,129,.1); color: #10b981; border: 1px solid rgba(16,185,129,.3); }

    .btn-action-delete {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #ef4444;
        transition: all 0.2s;
    }
    .btn-action-delete:hover {
        background: rgba(239, 68, 68, 0.18);
        color: #ef4444;
    }
</style>
@endpush

@section('content')
<span hidden data-refresh-on-return></span>
<div class="space-y-6">

    @if($tab === 'overview')
        @include('admin.partials._overview')
    @endif

    @if($tab === 'organizer_verifications')
        @include('admin.partials._organizer_verifications')
    @endif

    @if($tab === 'pending_validation')
        @include('admin.partials._pending_validation')
    @endif

    @if($tab === 'all_events')
        @include('admin.partials._all_events')
    @endif

    @if($tab === 'manage_users')
        @include('admin.partials._manage_users')
    @endif

    @if($tab === 'payment_monitoring')
        @include('admin.partials._payment_monitoring')
    @endif

    @if($tab === 'withdrawal_requests')
        @include('admin.partials._withdrawal_requests')
    @endif

    @if($tab === 'gallery')
        @include('admin.partials._gallery')
    @endif

</div>



@endsection

@push('scripts')
<script>
    const dateEl = document.getElementById('current-date');
    if (dateEl) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateEl.innerText = new Date().toLocaleDateString('id-ID', options);
    }
</script>
@endpush
