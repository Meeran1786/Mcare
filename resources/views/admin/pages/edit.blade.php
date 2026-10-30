@extends('adminlte::page')

@section('title', 'Edit Page')

@section('content_header')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="font-weight-bold text-dark mb-0">
            {{ optional($translations->get('en'))->title ?? $page->title }}
        </h1>
        <small class="text-muted">Update all language versions for this app page</small>
    </div>

    <a href="{{ route('admin.pages.index') }}" class="btn btn-light border shadow-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
</div>
@stop

@section('content')
<form action="{{ route('admin.pages.update', $page->id) }}" method="POST">
    @csrf

    <div class="card border-0 shadow-sm page-card">
        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success rounded-lg">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger rounded-lg">
                    Please check the highlighted fields and try again.
                </div>
            @endif

            <div class="form-group mb-4">
                <label class="font-weight-bold">Page Slug</label>
                <input
                    type="text"
                    name="slug"
                    class="form-control rounded-lg @error('slug') is-invalid @enderror"
                    value="{{ old('slug', $page->slug) }}"
                >
                <small class="text-muted">
                    Keep this slug same for all translations. The app asks for this slug plus selected language.
                </small>
                @error('slug')
                    <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </div>

            <ul class="nav nav-pills language-tabs mb-4" role="tablist">
                @foreach($languages as $code => $label)
                    @php($translation = $translations->get($code))
                    <li class="nav-item">
                        <a
                            class="nav-link {{ $code === 'en' ? 'active' : '' }}"
                            data-toggle="pill"
                            href="#lang-{{ $code }}"
                            role="tab"
                        >
                            {{ $label }} <span class="text-uppercase">({{ $code }})</span>
                            @if($code === 'en')
                                <span class="badge badge-light ml-1">Default</span>
                            @elseif($translation)
                                <span class="badge badge-success ml-1">Added</span>
                            @else
                                <span class="badge badge-secondary ml-1">Fallback</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach($languages as $code => $label)
                    @php($translation = $translations->get($code))
                    <div class="tab-pane fade {{ $code === 'en' ? 'show active' : '' }}" id="lang-{{ $code }}" role="tabpanel">
                        <div class="translation-panel {{ $code === 'ur' ? 'rtl-panel' : '' }}">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ $label }} Title {{ $code === 'en' ? '*' : '' }}</label>
                                <input
                                    type="text"
                                    name="title[{{ $code }}]"
                                    class="form-control rounded-lg @error('title.' . $code) is-invalid @enderror"
                                    value="{{ old('title.' . $code, $translation->title ?? '') }}"
                                    placeholder="{{ $label }} page title"
                                    dir="{{ $code === 'ur' ? 'rtl' : 'ltr' }}"
                                >
                                @error('title.' . $code)
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label class="font-weight-bold">{{ $label }} Content {{ $code === 'en' ? '*' : '' }}</label>
                                <textarea
                                    name="content[{{ $code }}]"
                                    id="summernote-{{ $code }}"
                                    class="form-control summernote-editor @error('content.' . $code) is-invalid @enderror"
                                    dir="{{ $code === 'ur' ? 'rtl' : 'ltr' }}"
                                >{{ old('content.' . $code, $translation->content ?? '') }}</textarea>
                                @error('content.' . $code)
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                @if($code !== 'en')
                                    <small class="text-muted">
                                        Clear both title and content to remove this translation and use English fallback.
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card-footer bg-white border-top d-flex justify-content-between">
            <a href="{{ route('admin.pages.index') }}" class="btn btn-light border px-4 rounded-pill">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">
                <i class="fas fa-save mr-1"></i> Save All Languages
            </button>
        </div>
    </div>
</form>
@stop

@section('plugins.Summernote', true)

@section('css')
<style>
.page-card { border-radius: 18px; overflow: hidden; }
.rounded-lg { border-radius: 14px; }
.language-tabs .nav-link { border-radius: 999px; color: #4A5568; font-weight: 700; margin-right: 8px; }
.language-tabs .nav-link.active { background: linear-gradient(135deg, #4f7cff, #6f42ff); color: #fff; }
.translation-panel { background: #fbfcff; border: 1px solid #e7ebf5; border-radius: 16px; padding: 18px; }
.rtl-panel { direction: rtl; text-align: right; }
.note-editor.note-frame { border-radius: 14px !important; overflow: hidden; border: 1px solid #dee2e6 !important; }
.note-toolbar { background: #fff !important; border-bottom: 1px solid #eee !important; }
.note-editing-area { min-height: 450px; }
.note-editable { font-size: 15px; line-height: 1.9; padding: 20px; font-family: Arial, sans-serif; }
.btn-primary { background: linear-gradient(135deg, #4f7cff, #6f42ff); border: none; }
.btn-primary:hover { opacity: 0.95; }
</style>
@stop

@section('js')
<script>
$(document).ready(function () {
    $('.summernote-editor').each(function () {
        const isRtl = $(this).attr('dir') === 'rtl';

        $(this).summernote({
            height: 500,
            placeholder: 'Write page content here...',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']],
                ['insert', ['link', 'picture', 'table', 'hr']],
                ['view', ['fullscreen', 'codeview']]
            ],
            callbacks: {
                onInit: function () {
                    const editable = $(this).siblings('.note-editor').find('.note-editable');
                    editable.attr('dir', isRtl ? 'rtl' : 'ltr');
                    editable.css('text-align', isRtl ? 'right' : 'left');
                }
            }
        });
    });
});
</script>
@stop
