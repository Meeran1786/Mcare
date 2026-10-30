@extends('adminlte::page')

@section('title', 'Services')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 style="color:#4328B7;font-weight:600;">
        Service Management
    </h1>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addServiceModal">
        <i class="fas fa-plus"></i> Add New Service
    </button>
</div>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

<div class="card shadow-sm" style="border-radius:14px;overflow:hidden;">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>ID</th>
                    <th>Icon/Image</th>
                    <th>Title & Description</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $service)
                <tr>
                    <td class="align-middle">{{ $service->id }}</td>
                    <td class="align-middle">
                        <img src="{{ asset('storage/' . $service->image) }}" alt="Service Icon" style="width:60px;height:60px;object-fit:contain;border-radius:6px;background-color:#f8f9fa;padding:5px;">
                    </td>
                    <td class="align-middle" style="max-width: 400px;">
                        <strong>{{ $service->title }}</strong><br>
                        <small class="text-muted d-inline-block text-truncate" style="max-width: 100%;">{{ $service->description }}</small>
                    </td>
                    <td class="align-middle">
                        @if($service->type == 'home')
                            <span class="badge badge-primary">
                                Home Page
                            </span>
                        @else
                            <span class="badge badge-success">
                                Services Page
                            </span>
                        @endif
                    </td>
                    <td class="align-middle">
                        @if($service->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="align-middle">
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#editServiceModal{{ $service->id }}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="openDeleteModal({{ $service->id }})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content" style="border-radius:16px;">
                            <form action="/admin/services/{{ $service->id }}/update" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Service</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                <div class="form-group">
                                    <label>
                                        Service Title
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="title"
                                        class="form-control"
                                        required
                                        value="{{ $service->title }}"
                                    >
                                </div>

                                <div class="form-group">
                                    <label>Description</label>

                                    <textarea
                                        name="description"
                                        class="form-control"
                                        rows="3"
                                    >{{ $service->description }}</textarea>
                                </div>

                                {{-- ADD THIS --}}
                                <div class="form-group">
                                    <label>
                                        Service Type
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        name="type"
                                        class="form-control"
                                        required
                                    >
                                        <option
                                            value="home"
                                            {{ $service->type == 'home' ? 'selected' : '' }}
                                        >
                                            Home Page
                                        </option>

                                        <option
                                            value="service_page"
                                            {{ $service->type == 'service_page' ? 'selected' : '' }}
                                        >
                                            Services Page
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>
                                        Icon/Image
                                        (Leave blank to keep current)
                                    </label>

                                    <input
                                        type="file"
                                        name="image"
                                        class="form-control-file"
                                        accept="image/*"
                                    >

                                    <div class="mt-2">
                                        <img
                                            src="{{ asset('storage/' . $service->image) }}"
                                            style="
                                                max-height:80px;
                                                background-color:#f8f9fa;
                                                padding:5px;
                                                border-radius:6px;
                                            "
                                        >
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">

                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="isActiveSwitch{{ $service->id }}"
                                            name="is_active"
                                            {{ $service->is_active ? 'checked' : '' }}
                                        >

                                        <label
                                            class="custom-control-label"
                                            for="isActiveSwitch{{ $service->id }}"
                                        >
                                            Active
                                        </label>

                                    </div>
                                </div>

                            </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No services found. Click "Add New Service" to create one.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $services->links() }}
</div>

<!-- Add Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:16px;">
            <form action="/admin/services" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Service</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Recommended to upload SVG or PNG icons with transparent backgrounds.
                        Max file size: 2MB.
                    </div>

                    <div class="form-group">
                        <label>
                            Service Title
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            required
                            placeholder="E.g., Prescription Refill"
                        >
                    </div>

                    <div class="form-group">
                        <label>Description</label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="3"
                            placeholder="Brief description of the service..."
                        ></textarea>
                    </div>

                    {{-- ADD THIS --}}
                    <div class="form-group">
                        <label>
                            Service Type
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="type"
                            class="form-control"
                            required
                        >
                            <option value="home">
                                Home Page
                            </option>

                            <option value="service_page">
                                Services Page
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            Icon/Image
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="file"
                            name="image"
                            class="form-control-file"
                            accept="image/*"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">

                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="isActiveSwitchNew"
                                name="is_active"
                                checked
                            >

                            <label
                                class="custom-control-label"
                                for="isActiveSwitchNew"
                            >
                                Active
                            </label>

                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="deleteForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Service</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this service? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
    function openDeleteModal(id) {
        document.getElementById('deleteForm').action = "/admin/services/" + id + "/delete";
        $('#deleteModal').modal('show');
    }
</script>
@stop
