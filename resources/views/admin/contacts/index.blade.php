@extends('adminlte::page')

@section('title', 'Contact Queries')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 style="color:#4328B7;font-weight:600;">
        Contact Queries
    </h1>
    @if($queries->count())
        <div class="d-flex align-items-center">
            <form action="/admin/contact-queries/delete-selected" method="POST" id="bulkDeleteForm" class="mr-2">
                @csrf
                <button type="submit" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </form>
            <form action="/admin/contact-queries/delete-all" method="POST" onsubmit="return confirm('Delete all contact queries? This action cannot be undone.');">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-trash-alt"></i> Delete All
                </button>
            </form>
        </div>
    @endif
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

<div class="card shadow-sm" style="border-radius:14px;overflow:hidden;">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width:42px;">
                        <input type="checkbox" id="selectAllQueries" aria-label="Select all contact queries">
                    </th>
                    <th>ID</th>
                    <th>User Info</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($queries as $query)
                <tr style="{{ $query->is_read ? 'background-color: #f9f9f9;' : 'font-weight: bold;' }}">
                    <td class="align-middle">
                        <input type="checkbox" class="query-checkbox" value="{{ $query->id }}" aria-label="Select contact query {{ $query->id }}">
                    </td>
                    <td class="align-middle">{{ $query->id }}</td>
                    <td class="align-middle">
                        <strong>{{ $query->name }}</strong><br>
                        <small class="text-muted"><i class="fas fa-envelope"></i> {{ $query->email }}</small><br>
                        @if($query->phone)
                            <small class="text-muted"><i class="fas fa-phone"></i> {{ $query->phone }}</small>
                        @endif
                    </td>
                    <td class="align-middle" style="max-width: 400px;">
                        @if($query->service)
                            <span class="badge badge-info mb-1">{{ $query->service }}</span><br>
                        @endif
                        <span class="d-inline-block text-truncate" style="max-width: 100%;" title="{{ $query->message }}">
                            {{ $query->message }}
                        </span>
                    </td>
                    <td class="align-middle">
                        @if($query->is_read)
                            <span class="badge badge-secondary">Read</span>
                        @else
                            <span class="badge badge-primary">New</span>
                        @endif
                    </td>
                    <td class="align-middle">
                        {{ $query->created_at->format('M d, Y h:i A') }}
                    </td>
                    <td class="align-middle">
                        @if(!$query->is_read)
                            <form action="/admin/contact-queries/{{ $query->id }}/read" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Mark as Read">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        @endif
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewModal{{ $query->id }}" title="View Message">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="openDeleteModal({{ $query->id }})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>

                <!-- View Modal -->
                <div class="modal fade" id="viewModal{{ $query->id }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content" style="border-radius:16px;">
                            <div class="modal-header">
                                <h5 class="modal-title">View Message</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p><strong>From:</strong> {{ $query->name }}</p>
                                <p><strong>Email:</strong> <a href="mailto:{{ $query->email }}">{{ $query->email }}</a></p>
                                @if($query->phone)
                                    <p><strong>Phone:</strong> <a href="tel:{{ $query->phone }}">{{ $query->phone }}</a></p>
                                @endif
                                @if($query->service)
                                    <p><strong>Service Requested:</strong> {{ $query->service }}</p>
                                @endif
                                <hr>
                                <p><strong>Message:</strong></p>
                                <div class="p-3 bg-light" style="border-radius: 8px;">
                                    {!! nl2br(e($query->message)) !!}
                                </div>
                            </div>
                            <div class="modal-footer">
                                @if(!$query->is_read)
                                    <form action="/admin/contact-queries/{{ $query->id }}/read" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">Mark as Read</button>
                                    </form>
                                @endif
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No contact queries found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">
    {{ $queries->links() }}
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="deleteForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Query</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this contact query? This action cannot be undone.</p>
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
        document.getElementById('deleteForm').action = "/admin/contact-queries/" + id + "/delete";
        $('#deleteModal').modal('show');
    }

    const selectAll = document.getElementById('selectAllQueries');
    const checkboxes = Array.from(document.querySelectorAll('.query-checkbox'));
    const bulkForm = document.getElementById('bulkDeleteForm');
    const bulkButton = document.getElementById('bulkDeleteBtn');

    function syncBulkDeleteState() {
        const selected = checkboxes.filter((checkbox) => checkbox.checked);
        if (bulkButton) {
            bulkButton.disabled = selected.length === 0;
            bulkButton.innerHTML = selected.length
                ? `<i class="fas fa-trash"></i> Delete Selected (${selected.length})`
                : '<i class="fas fa-trash"></i> Delete Selected';
        }
        if (selectAll) {
            selectAll.checked = selected.length > 0 && selected.length === checkboxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            syncBulkDeleteState();
        });
    }

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncBulkDeleteState);
    });

    if (bulkForm) {
        bulkForm.addEventListener('submit', (event) => {
            const selected = checkboxes.filter((checkbox) => checkbox.checked);
            if (!selected.length) {
                event.preventDefault();
                return;
            }
            if (!confirm(`Delete ${selected.length} selected contact quer${selected.length === 1 ? 'y' : 'ies'}?`)) {
                event.preventDefault();
                return;
            }
            bulkForm.querySelectorAll('input[name="query_ids[]"]').forEach((input) => input.remove());
            selected.forEach((checkbox) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'query_ids[]';
                input.value = checkbox.value;
                bulkForm.appendChild(input);
            });
        });
    }
</script>
@stop
