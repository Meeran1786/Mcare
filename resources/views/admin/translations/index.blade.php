@extends('adminlte::page')

@section('title', 'Translations')

@section('content_header')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
    <div>
        <h1 class="mb-1">Translations</h1>
        <p class="text-muted mb-0">Edit app text for English, Hindi, Telugu, and Urdu.</p>
    </div>
    <form method="GET" class="translation-search mt-3 mt-lg-0">
        <input
            type="search"
            name="search"
            class="form-control"
            placeholder="Search key or text"
            value="{{ $search }}"
        >
    </form>
</div>
@stop

@section('content')
<div class="alert alert-info translation-note">
    <i class="fas fa-info-circle mr-1"></i>
    Click the edit icon, change text, then press <strong>Enter</strong> to save without reloading the page.
    Use <strong>Create New Key</strong> for new app text; it saves to database and updates JSON files.
    Dynamic data like medicine names, pharmacy names, addresses, and user-entered text should not be added here.
</div>

<div class="card translation-create-card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <strong>Create New Key</strong>
            <div class="text-muted small">Example: dashboard.newTitle or profile.helpMessage</div>
        </div>
        <span id="createTranslationState" class="create-state"></span>
    </div>
    <div class="card-body">
        <form id="createTranslationForm">
            <div class="form-row">
                <div class="form-group col-lg-4">
                    <label>Translation Key</label>
                    <input type="text" name="key" class="form-control" placeholder="example.keyName" required>
                </div>
                <div class="form-group col-lg-4">
                    <label>English</label>
                    <input type="text" name="en" class="form-control" placeholder="English text">
                </div>
                <div class="form-group col-lg-4">
                    <label>Hindi</label>
                    <input type="text" name="hi" class="form-control" placeholder="Hindi text">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-lg-5">
                    <label>Telugu</label>
                    <input type="text" name="te" class="form-control" placeholder="Telugu text">
                </div>
                <div class="form-group col-lg-5">
                    <label>Urdu</label>
                    <input type="text" name="ur" class="form-control rtl-input" placeholder="Urdu text">
                </div>
                <div class="form-group col-lg-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block rounded-pill">
                        <i class="fas fa-plus mr-1"></i> Create
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card translation-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 translation-table">
                <thead>
                    <tr>
                        <th class="serial-col">S.No</th>
                        <th class="key-col">Key</th>
                        <th>English</th>
                        <th>Hindi</th>
                        <th>Telugu</th>
                        <th>Urdu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($translations as $translation)
                        <tr>
                            <td class="serial-cell">
                                {{ ($translations->currentPage() - 1) * $translations->perPage() + $loop->iteration }}
                            </td>
                            <td class="key-cell">
                                <code>{{ $translation->key }}</code>
                            </td>

                            @foreach($languages as $language)
                                <td>
                                    <div class="translation-editor" data-id="{{ $translation->id }}" data-language="{{ $language }}">
                                        <textarea
                                            class="translation-input {{ $language === 'ur' ? 'rtl-input' : '' }}"
                                            readonly
                                            rows="2"
                                        >{{ $translation->{$language} }}</textarea>
                                        <button type="button" class="edit-btn" title="Edit {{ strtoupper($language) }}">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <span class="save-state"></span>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                No translations found. Import your JSON files first.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $translations->links() }}
</div>
@stop

@section('css')
<style>
    .translation-search {
        width: min(100%, 360px);
    }

    .translation-note {
        border: 0;
        border-radius: 14px;
        background: #eef6ff;
        color: #274060;
    }

    .translation-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    .translation-create-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    .translation-create-card .form-control {
        min-height: 42px;
        border-radius: 10px;
    }

    .create-state {
        color: #64748b;
        font-size: 13px;
    }

    .create-state.saved {
        color: #059669;
    }

    .create-state.error {
        color: #dc2626;
    }

    .translation-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        border-top: 0;
        background: #f8fafc;
        color: #334155;
        font-size: 13px;
        white-space: nowrap;
    }

    .translation-table td {
        min-width: 230px;
        vertical-align: top;
    }

    .translation-table .key-col,
    .translation-table .key-cell {
        min-width: 210px;
        max-width: 260px;
        background: #fbfdff;
    }

    .translation-table .serial-col,
    .translation-table .serial-cell {
        min-width: 72px;
        max-width: 82px;
        text-align: center;
        color: #64748b;
        background: #fbfdff;
        font-weight: 700;
    }

    .translation-table code {
        color: #4338ca;
        white-space: normal;
        word-break: break-word;
    }

    .translation-editor {
        position: relative;
    }

    .translation-input {
        width: 100%;
        min-height: 54px;
        padding: 10px 40px 10px 12px;
        resize: vertical;
        border: 1px solid transparent;
        border-radius: 12px;
        color: #1f2937;
        background: #f8fafc;
        outline: none;
        line-height: 1.45;
        transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
    }

    .translation-input:not([readonly]) {
        border-color: #4f46e5;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
    }

    .rtl-input {
        direction: rtl;
        text-align: right;
    }

    .edit-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 26px;
        height: 26px;
        border: 0;
        border-radius: 13px;
        color: #4f46e5;
        background: #eef2ff;
    }

    .save-state {
        display: block;
        min-height: 17px;
        margin-top: 4px;
        color: #64748b;
        font-size: 12px;
    }

    .save-state.saved {
        color: #059669;
    }

    .save-state.error {
        color: #dc2626;
    }

    .pagination {
        align-items: center;
        gap: 6px;
    }

    .pagination .page-link {
        min-width: 34px;
        height: 34px;
        padding: 6px 10px;
        border-radius: 10px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        line-height: 1;
    }

    .pagination svg,
    nav[role="navigation"] svg {
        width: 16px !important;
        height: 16px !important;
        max-width: 16px !important;
        max-height: 16px !important;
    }

    nav[role="navigation"] > div:first-child {
        display: none;
    }
</style>
@stop

@section('js')
<script>
var createTranslationForm = document.getElementById('createTranslationForm');
var createTranslationState = document.getElementById('createTranslationState');

function setCreateState(text, className) {
    createTranslationState.textContent = text || '';
    createTranslationState.className = 'create-state' + (className ? ' ' + className : '');
}

if (createTranslationForm) {
    createTranslationForm.addEventListener('submit', function(event) {
        event.preventDefault();
        setCreateState('Saving...');

        var payload = {};
        new FormData(createTranslationForm).forEach(function(value, key) {
            payload[key] = value;
        });

        fetch('{{ route('admin.translations.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(function(response) {
            return response.json().then(function(data) {
                if (!response.ok || !data.success) {
                    var message = data.message || 'Unable to create key';
                    if (data.errors) {
                        message = Object.values(data.errors).flat().join(' ');
                    }
                    throw new Error(message);
                }
                return data;
            });
        })
        .then(function() {
            setCreateState('Created and JSON files updated', 'saved');
            setTimeout(function() {
                window.location.href = '{{ route('admin.translations.index') }}?search=' + encodeURIComponent(payload.key);
            }, 700);
        })
        .catch(function(error) {
            setCreateState(error.message || 'Create failed', 'error');
        });
    });
}

document.querySelectorAll('.translation-editor').forEach(function(editor) {
    var input = editor.querySelector('.translation-input');
    var button = editor.querySelector('.edit-btn');
    var state = editor.querySelector('.save-state');

    function setState(text, className) {
        state.textContent = text || '';
        state.className = 'save-state' + (className ? ' ' + className : '');
    }

    function enableEdit() {
        input.removeAttribute('readonly');
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
        setState('Press Enter to save');
    }

    function disableEdit() {
        input.setAttribute('readonly', 'readonly');
    }

    function saveValue() {
        setState('Saving...');

        fetch('{{ url('/admin/translations') }}/' + editor.dataset.id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                language: editor.dataset.language,
                value: input.value
            })
        })
        .then(function(response) {
            return response.json().then(function(data) {
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Unable to save');
                }
                return data;
            });
        })
        .then(function() {
            disableEdit();
            setState('Saved', 'saved');
            setTimeout(function() { setState(''); }, 1400);
        })
        .catch(function(error) {
            setState(error.message || 'Save failed', 'error');
        });
    }

    button.addEventListener('click', enableEdit);

    input.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            saveValue();
        }
    });

    input.addEventListener('blur', function() {
        if (!input.hasAttribute('readonly')) {
            disableEdit();
            setState('');
        }
    });
});
</script>
@stop
