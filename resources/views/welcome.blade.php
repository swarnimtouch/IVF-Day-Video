<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Doctor Video Personalisation | Meyer Vitabiotics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="page-shell">
        <section class="form-card" aria-labelledby="page-title">
            <header class="brand-header">
                <img src="{{ asset('assets/meyer-vitabiotics.jpg') }}" alt="Meyer Vitabiotics" class="brand-logo">
                <span class="header-rule" aria-hidden="true"></span>
                <p>Doctor video personalisation</p>
            </header>

            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <strong>Please check the details below.</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @if (session('video_url'))
                <div class="result-panel" id="result">
                    <div class="success-icon">✓</div>
                    <div>
                        <h2>Your personalised video is ready</h2>
                        <p>{{ session('doctor_label') }}</p>
                    </div>
                    <video controls playsinline preload="metadata" src="{{ session('video_url') }}"></video>
                    <a class="download-button" href="{{ session('video_url') }}" download>Download video</a>
                    <a class="new-link" href="{{ route('video.form') }}">Create another video</a>
                </div>
            @else
                <div class="intro">
                    <span class="eyebrow">Personalised in moments</span>
                    <h1 id="page-title">Create a doctor video</h1>
                    <p>Enter the doctor’s details and add a clear photograph. The personalised introduction will appear from 28 seconds.</p>
                </div>

                <form action="{{ route('video.generate') }}" method="POST" enctype="multipart/form-data" id="video-form">
                    @csrf
                    <div class="field-grid">
                        <label class="field full">
                            <span>Employee code</span>
                            <input type="text" name="employee_code" value="{{ old('employee_code') }}" maxlength="30" placeholder="e.g. MV1024" required autocomplete="off">
                        </label>

                        <label class="field prefix-field">
                            <span>Prefix</span>
                            <select name="prefix" required>
                                <option value="" disabled {{ old('prefix') ? '' : 'selected' }}>Select</option>
                                @foreach (['Dr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'] as $prefix)
                                    <option value="{{ $prefix }}" @selected(old('prefix') === $prefix)>{{ $prefix }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="field name-field">
                            <span>Doctor name</span>
                            <input type="text" name="doctor_name" value="{{ old('doctor_name') }}" maxlength="80" placeholder="Full name" required autocomplete="name">
                        </label>

                        <label class="field full">
                            <span>City</span>
                            <input type="text" name="city" value="{{ old('city') }}" maxlength="80" placeholder="Enter city" required autocomplete="address-level2">
                        </label>
                    </div>

                    <div class="photo-section">
                        <div class="section-heading">
                            <div>
                                <span>Doctor photo</span>
                                <small>JPG or PNG · max 8 MB</small>
                            </div>
                            <span class="step-pill">Circular crop</span>
                        </div>

                        <div class="photo-workspace">
                            <button type="button" class="photo-picker" id="photo-picker">
                                <span class="photo-preview" id="photo-preview">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0h14Z"/></svg>
                                </span>
                                <span><strong>Choose a photo</strong><small>Click to browse</small></span>
                            </button>
                            <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" hidden>
                            <input type="file" name="photo" id="cropped-photo" hidden required>

                            <div class="crop-area" id="crop-area" hidden>
                                <canvas id="crop-canvas" width="360" height="360" aria-label="Photo crop preview"></canvas>
                                <div class="crop-controls">
                                    <label for="zoom">Zoom</label>
                                    <input type="range" id="zoom" min="1" max="3" value="1" step="0.01">
                                    <p>Drag the photo to reposition it inside the circle.</p>
                                </div>
                            </div>
                        </div>
                        <p class="photo-error" id="photo-error" role="alert"></p>
                    </div>

                    <button class="submit-button" type="submit" id="submit-button">
                        <span class="button-label">Generate personalised video</span>
                        <span class="button-loader" aria-hidden="true"></span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <p class="processing-note">Video processing may take up to a minute. Please keep this page open.</p>
                </form>
            @endif
        </section>
        <p class="footer-note">For authorised Meyer Vitabiotics employees only</p>
    </main>
</body>
</html>
