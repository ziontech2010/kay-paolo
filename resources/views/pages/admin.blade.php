@extends('layouts.site')

@section('title', 'Admin | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Admin</h1>
        <div class="breadcrumb"><a href="{{ route('home') }}">Home</a><span class="sep">/</span><span>Admin</span></div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap admin-page-wrap">
        <div class="page-back-actions">
            <button type="button" class="btn btn-secondary btn-back" data-go-back>&larr; Back</button>
        </div>

        @if (session('status'))
            <div class="api-alert success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="api-alert error">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="contact-form admin-content-form" method="POST" action="{{ route('admin.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="admin-edit-grid">
                <section class="admin-edit-panel">
                    <h2>Homepage Copy</h2>
                    <div class="field">
                        <label for="meta_description">Meta Description</label>
                        <textarea id="meta_description" name="meta_description" rows="3" required>{{ old('meta_description', $content['meta_description']) }}</textarea>
                    </div>
                    <div class="field">
                        <label for="who_headline">Who We Are Headline</label>
                        <input id="who_headline" name="who_headline" type="text" value="{{ old('who_headline', $content['who_headline']) }}" required>
                    </div>
                    <div class="field">
                        <label for="who_body">Who We Are Text</label>
                        <textarea id="who_body" name="who_body" rows="7" required>{{ old('who_body', $content['who_body']) }}</textarea>
                    </div>
                </section>

                <section class="admin-edit-panel">
                    <h2>Who We Are Pictures</h2>
                    <div class="admin-image-row">
                        <div>
                            <span class="admin-preview-label">Current Primary</span>
                            <img src="{{ asset($content['who_image_primary']) }}" alt="Current primary Who We Are image" class="admin-image-preview">
                            <div class="field">
                                <label for="who_image_primary">Replace Primary Picture</label>
                                <input id="who_image_primary" name="who_image_primary" type="file" accept="image/*">
                            </div>
                        </div>
                        <div>
                            <span class="admin-preview-label">Current Secondary</span>
                            <img src="{{ asset($content['who_image_secondary']) }}" alt="Current secondary Who We Are image" class="admin-image-preview">
                            <div class="field">
                                <label for="who_image_secondary">Replace Secondary Picture</label>
                                <input id="who_image_secondary" name="who_image_secondary" type="file" accept="image/*">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="admin-edit-panel">
                    <h2>How It Works</h2>
                    <div class="field">
                        <label for="process_step_1_title">Step 1 Title</label>
                        <input id="process_step_1_title" name="process_step_1_title" type="text" value="{{ old('process_step_1_title', $content['process_step_1_title']) }}" required>
                    </div>
                    <div class="field">
                        <label for="process_step_1_body">Step 1 Text</label>
                        <textarea id="process_step_1_body" name="process_step_1_body" rows="4" required>{{ old('process_step_1_body', $content['process_step_1_body']) }}</textarea>
                    </div>
                </section>
            </div>

            <button class="btn btn-gold" type="submit">Save Changes</button>
        </form>
    </div>
</section>
@endsection
