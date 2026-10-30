@extends('adminlte::page')

@section('title', 'Pages Management')

@section('content_header')

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h1 class="font-weight-bold text-dark mb-0">
            App Pages (Terms, Privacy)
        </h1>

        <small class="text-muted">
            Manage app legal and policy pages
        </small>
    </div>

    <a href="{{ route('admin.pages.create') }}"
       class="btn btn-primary px-4 rounded-pill shadow-sm">

        <i class="fas fa-plus mr-1"></i>
        Add Page

    </a>

</div>

@stop

@section('content')

<div class="card border-0 shadow-sm rounded-lg overflow-hidden">

    {{-- HEADER --}}
    <div class="card-header bg-white border-bottom py-3">

        <div class="d-flex justify-content-between align-items-center">

            <h3 class="card-title font-weight-bold mb-0">
                Manage Pages
            </h3>

            <span class="badge badge-primary px-3 py-2">
                {{ $pageGroups->count() }} Pages
            </span>

        </div>

    </div>

    {{-- BODY --}}
    <div class="card-body p-4">

        {{-- SUCCESS MESSAGE --}}
        @if(session('success'))

            <div class="alert alert-success rounded-lg">
                {{ session('success') }}
            </div>

        @endif

        {{-- TABLE --}}
        @if($pageGroups->count() > 0)

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>

                    <tr>

                        <th>Page</th>

                        <th>Slug</th>

                        <th>Languages</th>

                        <th>Updated</th>

                        <th class="text-center">Action</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($pageGroups as $page)

                    <tr>

                        <td>

                            <div class="d-flex align-items-center">

                                <div class="page-icon mr-3">

                                    @if(str_contains(strtolower($page['title']), 'privacy'))

                                        <i class="fas fa-user-shield"></i>

                                    @elseif(str_contains(strtolower($page['title']), 'terms'))

                                        <i class="fas fa-file-contract"></i>

                                    @else

                                        <i class="fas fa-file-alt"></i>

                                    @endif

                                </div>

                                <div>

                                    <div class="font-weight-bold text-dark">
                                        {{ $page['title'] }}
                                    </div>

                                    <small class="text-muted">
                                        App content page
                                    </small>

                                </div>

                            </div>

                        </td>

                        <td>

                            <span class="badge badge-light px-3 py-2">
                                {{ $page['slug'] }}
                            </span>

                        </td>

                        <td>

                            @foreach($languages as $code => $label)
                                <span class="badge {{ in_array($code, $page['languages']) ? 'badge-success' : 'badge-light' }} px-3 py-2 mr-1 mb-1">
                                    {{ strtoupper($code) }}
                                </span>
                            @endforeach

                        </td>

                        <td>

                            <div class="text-dark font-weight-bold">
                                {{ \Carbon\Carbon::parse($page['updated_at'])->format('M d, Y') }}
                            </div>

                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($page['updated_at'])->format('h:i A') }}
                            </small>

                        </td>

                        <td class="text-center">

                            <a href="{{ route('admin.pages.edit', $page['id']) }}"
                               class="btn btn-primary rounded-pill px-4 shadow-sm">

                                <i class="fas fa-edit mr-1"></i>
                                Edit

                            </a>

                            <form action="{{ route('admin.pages.delete', $page['id']) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete this page and all language versions?');">
                                @csrf
                                <button type="submit"
                                        class="btn btn-outline-danger rounded-pill px-3 ml-2">
                                    <i class="fas fa-trash mr-1"></i>
                                    Delete
                                </button>
                            </form>

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @else

        {{-- EMPTY STATE --}}

        <div class="text-center py-5">

            <div class="mb-4">

                <i class="fas fa-file-alt"
                   style="font-size: 80px; color: #d6d9ff;"></i>

            </div>

            <h4 class="font-weight-bold text-dark">
                No Pages Found
            </h4>

            <p class="text-muted mb-4">
                Create Terms & Conditions,
                Privacy Policy,
                Refund Policy and other app pages.
            </p>

            <a href="{{ route('admin.pages.create') }}"
               class="btn btn-primary px-5 rounded-pill shadow-sm">

                <i class="fas fa-plus mr-1"></i>
                Create First Page

            </a>

        </div>

        @endif

    </div>

</div>

@stop

@section('css')

<style>

.card {
    border-radius: 18px;
}

.table th {

    border-top: none !important;

    font-size: 13px;

    text-transform: uppercase;

    color: #777;

    letter-spacing: 0.5px;

    padding-bottom: 18px;
}

.table td {

    vertical-align: middle !important;

    padding-top: 18px;

    padding-bottom: 18px;
}

.page-icon {

    width: 50px;

    height: 50px;

    border-radius: 14px;

    background: linear-gradient(135deg, #4f7cff, #6f42ff);

    display: flex;

    align-items: center;

    justify-content: center;

    color: white;

    font-size: 20px;
}

.btn-primary {

    background: linear-gradient(135deg, #4f7cff, #6f42ff);

    border: none;
}

.btn-primary:hover {

    opacity: 0.95;
}

.badge-light {

    background: #f1f3ff;

    color: #5b5ff8;

    font-weight: 600;
}

.rounded-lg {

    border-radius: 18px;
}

.alert {

    border: none;
}

</style>

@stop
