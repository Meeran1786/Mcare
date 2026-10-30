@extends('adminlte::page')

@section('title', 'Edit Social Link')

@section('content_header')

<h1 class="font-weight-bold">
    Edit Social Link
</h1>

@stop

@section('content')

<div class="card shadow-sm border-0 rounded-lg">

    <form action="{{ route('admin.social-links.update', $link->id) }}"
          method="POST">

        @csrf

        <div class="card-body">

            <div class="form-group">

                <label>Platform</label>

                <select name="platform"
                        class="form-control">

                    @foreach(['facebook','instagram','linkedin','x','youtube','whatsapp'] as $platform)

                        <option value="{{ $platform }}"
                            {{ $link->platform == $platform ? 'selected' : '' }}>

                            {{ ucfirst($platform) }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="form-group">

                <label>Label</label>

                <input type="text"
                       name="label"
                       class="form-control"
                       value="{{ $link->label }}"
                       required>

            </div>

            <div class="form-group">

                <label>URL</label>

                <input type="url"
                       name="url"
                       class="form-control"
                       value="{{ $link->url }}"
                       required>

            </div>

            <div class="form-group">

                <label>Sort Order</label>

                <input type="number"
                       name="sort_order"
                       class="form-control"
                       value="{{ $link->sort_order }}">

            </div>

            <div class="form-group">

                <div class="custom-control custom-switch">

                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           class="custom-control-input"
                           id="activeSwitch"
                           {{ $link->is_active ? 'checked' : '' }}>

                    <label class="custom-control-label"
                           for="activeSwitch">

                        Active

                    </label>

                </div>

            </div>

        </div>

        <div class="card-footer bg-white">

            <button type="submit"
                    class="btn btn-primary px-5 rounded-pill">

                Update Link

            </button>

        </div>

    </form>

</div>

@stop