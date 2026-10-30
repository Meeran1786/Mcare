@extends('adminlte::page')

@section('title', 'App Versions')

@section('content_header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
    <div>
        <h1 class="mb-1">App Versions</h1>
        <p class="text-muted mb-0">Control mobile app update popups for user and vendor apps.</p>
    </div>
</div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger shadow-sm">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.app-versions.update') }}" method="POST">
    @csrf

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card app-version-card h-100">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-user mr-2 text-primary"></i>User App
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latest Version</label>
                                <input
                                    type="text"
                                    name="user_latest_version"
                                    class="form-control"
                                    placeholder="1.0.1"
                                    value="{{ old('user_latest_version', $settings->user_latest_version) }}"
                                >
                                <small class="text-muted">Popup appears when installed version is lower.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Version</label>
                                <input
                                    type="text"
                                    name="user_minimum_version"
                                    class="form-control"
                                    placeholder="1.0.0"
                                    value="{{ old('user_minimum_version', $settings->user_minimum_version) }}"
                                >
                                <small class="text-muted">Users below this version can be forced to update.</small>
                            </div>
                        </div>
                    </div>

                    <div class="custom-control custom-switch mb-3">
                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="userForceUpdate"
                            name="user_force_update"
                            value="1"
                            {{ old('user_force_update', $settings->user_force_update) ? 'checked' : '' }}
                        >
                        <label class="custom-control-label font-weight-bold" for="userForceUpdate">
                            Force update when below minimum version
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Play Store Link</label>
                        <input
                            type="url"
                            name="user_play_store"
                            class="form-control"
                            placeholder="https://play.google.com/store/apps/details?id=..."
                            value="{{ old('user_play_store', $settings->user_play_store) }}"
                        >
                    </div>

                    <div class="form-group mb-0">
                        <label>App Store Link</label>
                        <input
                            type="url"
                            name="user_app_store"
                            class="form-control"
                            placeholder="https://apps.apple.com/app/..."
                            value="{{ old('user_app_store', $settings->user_app_store) }}"
                        >
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card app-version-card h-100">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-store mr-2 text-success"></i>Vendor App
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latest Version</label>
                                <input
                                    type="text"
                                    name="vendor_latest_version"
                                    class="form-control"
                                    placeholder="1.0.1"
                                    value="{{ old('vendor_latest_version', $settings->vendor_latest_version) }}"
                                >
                                <small class="text-muted">Popup appears when installed version is lower.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Version</label>
                                <input
                                    type="text"
                                    name="vendor_minimum_version"
                                    class="form-control"
                                    placeholder="1.0.0"
                                    value="{{ old('vendor_minimum_version', $settings->vendor_minimum_version) }}"
                                >
                                <small class="text-muted">Vendors below this version can be forced to update.</small>
                            </div>
                        </div>
                    </div>

                    <div class="custom-control custom-switch mb-3">
                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="vendorForceUpdate"
                            name="vendor_force_update"
                            value="1"
                            {{ old('vendor_force_update', $settings->vendor_force_update) ? 'checked' : '' }}
                        >
                        <label class="custom-control-label font-weight-bold" for="vendorForceUpdate">
                            Force update when below minimum version
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Play Store Link</label>
                        <input
                            type="url"
                            name="vendor_play_store"
                            class="form-control"
                            placeholder="https://play.google.com/store/apps/details?id=..."
                            value="{{ old('vendor_play_store', $settings->vendor_play_store) }}"
                        >
                    </div>

                    <div class="form-group mb-0">
                        <label>App Store Link</label>
                        <input
                            type="url"
                            name="vendor_app_store"
                            class="form-control"
                            placeholder="https://apps.apple.com/app/..."
                            value="{{ old('vendor_app_store', $settings->vendor_app_store) }}"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="mb-3 mb-md-0">
                <strong>How it works</strong>
                <p class="text-muted mb-0">
                    Latest version shows an optional update. Minimum version with force update enabled blocks old apps until they update.
                </p>
            </div>
            <button type="submit" class="btn btn-primary rounded-pill px-5">
                <i class="fas fa-save mr-1"></i> Save Version Settings
            </button>
        </div>
    </div>
</form>
@stop

@section('css')
<style>
    .app-version-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .app-version-card .card-header {
        border-bottom: 1px solid #eef1f5;
        padding: 18px 20px;
    }

    .app-version-card .card-body {
        padding: 22px;
    }

    .app-version-card label {
        color: #2f3a4a;
        font-weight: 700;
    }

    .app-version-card .form-control {
        min-height: 44px;
        border-radius: 10px;
    }
</style>
@stop
