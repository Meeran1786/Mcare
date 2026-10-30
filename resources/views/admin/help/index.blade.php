{{-- resources/views/admin/help/index.blade.php --}}
@extends('adminlte::page')

@section('title', 'Help Requests')

@section('content_header')
    <h1>Help Requests</h1>
@stop

@section('content')

@if($requests->count() === 0)
    <div class="alert alert-info">No help requests found</div>
@else

<div class="card">
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr>
                    <td>{{ $req->user->name ?? 'N/A' }}</td>
                    <td>{{ $req->subject }}</td>
                    <td>{{ $req->message }}</td>
                    <td>{{ $req->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif

@stop