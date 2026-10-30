@extends('adminlte::page')

@section('title', 'Create Social Link')

@section('content_header')

<h1 class="font-weight-bold">
    Create Social Link
</h1>

@stop

@section('content')

<div class="card shadow-sm border-0 rounded-lg">

    <form action="{{ route('admin.social-links.store') }}"
          method="POST">

        @csrf

        <div class="card-body">

            <div class="form-group">

                <label>Platform</label>

                <select name="platform"
                        class="form-control">

                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="linkedin">LinkedIn</option>
                    <option value="x">X (Twitter)</option>
                    <option value="youtube">YouTube</option>
                    <option value="whatsapp">WhatsApp</option>

                </select>

            </div>

            <div class="form-group">

                <label>Label</label>

                <input type="text"
                       name="label"
                       class="form-control"
                       placeholder="Enter label"
                       required>

            </div>

            <div class="form-group">

                <label>URL</label>

                <input type="url"
                       name="url"
                       class="form-control"
                       placeholder="https://"
                       required>

            </div>

            <div class="form-group">

                <label>Sort Order</label>

                <input type="number"
                       name="sort_order"
                       class="form-control"
                       value="0">

            </div>

            <div class="form-group">

                <div class="custom-control custom-switch">

                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           class="custom-control-input"
                           id="activeSwitch"
                           checked>

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

                Save Link

            </button>

        </div>

    </form>

</div>

@stop