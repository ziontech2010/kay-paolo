@extends('layouts.site')

@section('title', 'Complete Pickup | Kay Paolo Shipping')

@section('banner')
<div class="page-banner">
    <div class="wrap">
        <h1>Complete Pickup</h1>
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a><span class="sep">/</span>
            <a href="{{ route('pickup-list') }}">Pickup List</a><span class="sep">/</span>
            <span>Complete Pickup</span>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="page-follows-banner">
    <div class="wrap">
        <div class="pickup-complete-page" id="pickupCompletePage" data-pickup-id="{{ (int) $pickupId }}">
            <div class="api-alert error" id="authNotice">Login first to complete this pickup.</div>
            <div class="api-loader" id="pickupCompleteLoader" hidden>
                <img src="{{ asset('kay-paolo/assets/processing-shipping.gif') }}" alt="Loading pickup">
            </div>

            <div id="pickupCompleteNotice" class="api-alert error" hidden></div>

            <div id="pickupCompleteContent" hidden>
                <div class="pickup-complete-header">
                    <div>
                        <h2 id="pickupCompleteTitle">Pickup</h2>
                        <p id="pickupCompleteClient" class="pickup-complete-subtitle"></p>
                    </div>
                    <a href="{{ route('pickup-list') }}" class="btn btn-outline">Back To Pickup List</a>
                </div>

                <div class="pickup-complete-meta">
                    <div class="pickup-complete-meta-card">
                        <span class="meta-label">Pickup Date</span>
                        <span class="meta-val" id="pickupCompleteDate">-</span>
                    </div>
                    <div class="pickup-complete-meta-card">
                        <span class="meta-label">Phone</span>
                        <span class="meta-val" id="pickupCompletePhone">-</span>
                    </div>
                    <div class="pickup-complete-meta-card">
                        <span class="meta-label">Packages</span>
                        <span class="meta-val" id="pickupCompletePackages">-</span>
                    </div>
                    <div class="pickup-complete-meta-card">
                        <span class="meta-label">Weight / Volume / CF</span>
                        <span class="meta-val" id="pickupCompleteSpecs">-</span>
                    </div>
                </div>

                <div class="pickup-complete-address">
                    <span class="meta-label">Address</span>
                    <p id="pickupCompleteAddress" class="meta-val">-</p>
                </div>

                <form id="pickup-complete-form" class="pickup-complete-form" enctype="multipart/form-data">
                    <div class="pickup-complete-grid">
                        <div class="pickup-complete-panel">
                            <div class="pickup-complete-panel-head">
                                <h3>Pickup Photos</h3>
                                <span>Add 1 to 6 images</span>
                            </div>

                            <label class="pickup-complete-label" for="pickup-photos">Upload From Device</label>
                            <input id="pickup-photos" type="file" name="photos[]" accept="image/*" multiple>
                            <p class="pickup-complete-help">You can upload extra pickup pictures from your phone or computer here.</p>

                            <label class="pickup-complete-label">Uploaded Files Preview</label>
                            <div id="pickup-file-preview" class="pickup-complete-preview-grid"></div>
                            <p id="pickup-file-preview-empty" class="pickup-complete-help">No extra files selected yet.</p>

                            <label class="pickup-complete-label">Camera Snapshots</label>
                            <div id="pickup-snapshot-preview" class="pickup-complete-preview-grid"></div>
                            <p id="pickup-snapshot-preview-empty" class="pickup-complete-help">No camera snapshots added yet.</p>
                        </div>

                        <div class="pickup-complete-panel">
                            <div class="pickup-complete-panel-head">
                                <h3>Camera</h3>
                                <span class="pickup-complete-required">Snapshot Required</span>
                            </div>
                            <p class="pickup-complete-help">Use Take Snapshot to open the rear camera and attach pickup pictures before submission.</p>

                            <div class="pickup-complete-camera">
                                <div id="pickup_camera"></div>
                            </div>

                            <div class="pickup-complete-camera-actions">
                                <button type="button" id="btn-pickup-snapshot" class="btn btn-danger">Take Snapshot</button>
                                <a id="pickupCompleteDirections" href="#" target="_blank" rel="noopener" class="btn btn-outline">Directions</a>
                            </div>
                        </div>
                    </div>

                    <div class="pickup-complete-footer">
                        <p>Submitting this will complete the pickup, save the pictures, and create the driver bill automatically.</p>
                        <button type="submit" class="btn btn-gold" id="pickupCompleteSubmit">Submit Pickup Completion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
