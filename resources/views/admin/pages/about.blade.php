{{-- resources/views/admin/pages/about.blade.php --}}
@extends('adminlte::page')

@section('title', 'About mCare+')

@section('content_header')
<h1>About mCare+</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <h4>Welcome to mCare+</h4>
        <p>
            mCare+ is a healthcare platform designed to simplify medicine ordering,
            prescription management, and pharmacy connections.
        </p>

        <ul>
            <li>📦 Easy medicine ordering</li>
            <li>📄 Prescription uploads</li>
            <li>🔔 Real-time notifications</li>
        </ul>
    </div>
</div>
@stop