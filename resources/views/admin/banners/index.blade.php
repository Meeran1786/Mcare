@extends('adminlte::page')

@section('title', 'Banners')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 style="color:#4328B7;font-weight:600;">
        Banner Management
    </h1>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addBannerModal">
        <i class="fas fa-plus"></i> Add New Banner
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
                    <th>Image</th>
                    <th>Title & Eyebrow</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($banners as $banner)
                <tr>
                    <td class="align-middle">{{ $banner->id }}</td>
                    <td class="align-middle">
                        <img src="{{ asset('storage/' . $banner->image) }}" alt="Banner" style="width:120px;height:50px;object-fit:cover;border-radius:6px;">
                    </td>
                    <td class="align-middle">
                        <strong>{{ $banner->title }}</strong><br>
                        <small class="text-muted">{{ $banner->eyebrow }}</small>
                    </td>
                    <td class="align-middle">
                        @if($banner->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="align-middle">
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#editBannerModal{{ $banner->id }}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="openDeleteModal({{ $banner->id }})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editBannerModal{{ $banner->id }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content" style="border-radius:16px;">
                            <form action="/admin/banners/{{ $banner->id }}/update" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Banner</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Eyebrow Text</label>
                                        <input type="text" name="eyebrow" class="form-control" value="{{ $banner->eyebrow }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" required value="{{ $banner->title }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="3">{{ $banner->description }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Image (Leave blank to keep current)</label>
                                        <div class="alert alert-info py-2">
                                            <i class="fas fa-info-circle"></i> Required size: 1920x600 pixels. Max file size: 2MB.
                                        </div>
                                        <input type="file" name="image" class="form-control-file" accept="image/*">
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $banner->image) }}" style="max-height:100px;border-radius:6px;">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="isActiveSwitch{{ $banner->id }}" name="is_active" {{ $banner->is_active ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="isActiveSwitch{{ $banner->id }}">Active</label>
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
                    <td colspan="5" class="text-center py-4 text-muted">No banners found. Click "Add New Banner" to create one.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $banners->links() }}
</div>

<!-- Add Modal -->
<div class="modal fade" id="addBannerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:16px;">
            <form action="/admin/banners" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Banner</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Required size: 1920x600 pixels. Max file size: 2MB.
                    </div>
                    <div class="form-group">
                        <label>Eyebrow Text</label>
                        <input type="text" name="eyebrow" class="form-control" placeholder="E.g., Trusted care network">
                    </div>
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="Main heading text">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Supporting text"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image <span class="text-danger">*</span></label>
                        <input type="file" name="image" class="form-control-file" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="isActiveSwitchNew" name="is_active" checked>
                            <label class="custom-control-label" for="isActiveSwitchNew">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Banner</button>
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
                    <h5 class="modal-title text-danger">Delete Banner</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this banner? This action cannot be undone.</p>
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
        document.getElementById('deleteForm').action = "/admin/banners/" + id + "/delete";
        $('#deleteModal').modal('show');
    }
</script>
@stop
