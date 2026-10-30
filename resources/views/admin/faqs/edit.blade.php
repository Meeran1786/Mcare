@extends('adminlte::page')

@section('title', 'Edit FAQ')

@section('content_header')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="font-weight-bold text-dark mb-0">Edit FAQ</h1>
        <small class="text-muted">{{ $faq->question }}</small>
    </div>

    <a href="{{ route('admin.faqs.index') }}" class="btn btn-light border shadow-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
</div>
@stop

@section('content')
<div class="card border-0 shadow-sm rounded-lg overflow-hidden">
    <form action="{{ route('admin.faqs.update', $faq->id) }}" method="POST">
        @csrf

        <div class="card-body p-4">
            @include('admin.faqs.form', ['faq' => $faq])
        </div>

        <div class="card-footer bg-white border-top d-flex justify-content-between">
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary px-4 rounded-pill">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
        </div>
    </form>
</div>
@stop

@section('css')
@include('admin.faqs.styles')
@stop
