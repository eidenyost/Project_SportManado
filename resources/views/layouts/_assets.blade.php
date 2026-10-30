@php($assetVersion = is_file(public_path('assets/app.css')) ? filemtime(public_path('assets/app.css')) : 'missing')
<link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ $assetVersion }}">
<script defer src="{{ asset('assets/alpine.min.js') }}?v={{ $assetVersion }}"></script>
<script src="{{ asset('assets/lucide.min.js') }}?v={{ $assetVersion }}"></script>
<script defer src="{{ asset('assets/forms.js') }}?v={{ $assetVersion }}"></script>
