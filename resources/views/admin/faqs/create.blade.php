@extends('adminlte::page')

@section('title', 'Create FAQ')

@section('content_header')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="font-weight-bold text-dark mb-0">Create FAQ</h1>
        <small class="text-muted">Add a question and answer for the mobile app</small>
    </div>

    <a href="{{ route('admin.faqs.index') }}" class="btn btn-light border shadow-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
</div>
@stop

@section('content')
<div class="card border-0 shadow-sm rounded-lg overflow-hidden">
    <form action="{{ route('admin.faqs.store') }}" method="POST">
        @csrf

        <div class="card-body p-4">
            @include('admin.faqs.form', ['faq' => null])
        </div>

        <div class="card-footer bg-white border-top d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">
                <i class="fas fa-save mr-1"></i> Create FAQ
            </button>
        </div>
    </form>
</div>
@stop

@section('css')
@include('admin.faqs.styles')
@stop
