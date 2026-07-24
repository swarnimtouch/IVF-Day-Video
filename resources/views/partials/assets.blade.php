@if (file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
    <script type="module" src="{{ asset('assets/app.js') }}" defer></script>
@endif
