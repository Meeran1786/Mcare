@extends('adminlte::page')

@section('title', 'Social Links')

@section('content_header')

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h1 class="font-weight-bold mb-0">
            Social Links
        </h1>

        <small class="text-muted">
            Manage app social media links
        </small>
    </div>

    <a href="{{ route('admin.social-links.create') }}"
       class="btn btn-primary rounded-pill px-4">

        <i class="fas fa-plus mr-1"></i>
        Add Link

    </a>

</div>

@stop

@section('content')

<div class="card border-0 shadow-sm rounded-lg">

    <div class="card-body">

        @if(session('success'))

            <div class="alert alert-success">

                {{ session('success') }}

            </div>

        @endif

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Platform</th>
                        <th>Label</th>
                        <th>URL</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($links as $link)

                    <tr>

                        <td>{{ $link->id }}</td>

                        <td>

                            <span class="badge badge-primary px-3 py-2">

                                {{ ucfirst($link->platform) }}

                            </span>

                        </td>

                        <td>{{ $link->label }}</td>

                        <td>

                            <a href="{{ $link->url }}"
                               target="_blank">

                                {{ $link->url }}

                            </a>

                        </td>

                        <td>

                            @if($link->is_active)

                                <span class="badge badge-success">
                                    Active
                                </span>

                            @else

                                <span class="badge badge-danger">
                                    Inactive
                                </span>

                            @endif

                        </td>

                        <td class="d-flex">

                            <a href="{{ route('admin.social-links.edit', $link->id) }}"
                               class="btn btn-sm btn-primary mr-2">

                                <i class="fas fa-edit"></i>

                            </a>

                            <form action="{{ route('admin.social-links.delete', $link->id) }}"
                                  method="POST">

                                @csrf

                                <button class="btn btn-sm btn-danger">

                                    <i class="fas fa-trash"></i>

                                </button>

                            </form>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="6"
                            class="text-center py-5">

                            <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>

                            <h5>No Social Links Found</h5>

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@stop