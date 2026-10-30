@extends('adminlte::page')

@section('title', 'FAQ Management')

@section('content_header')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="font-weight-bold text-dark mb-0">FAQ Management</h1>
        <small class="text-muted">Manage app FAQ questions shown on Need Help and Payment screens</small>
    </div>

    <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary px-4 rounded-pill shadow-sm">
        <i class="fas fa-plus mr-1"></i> Add FAQ
    </a>
</div>
@stop

@section('content')
<div class="card border-0 shadow-sm rounded-lg overflow-hidden">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold mb-2 mb-md-0">Manage FAQs</h3>

            <form method="GET" action="{{ route('admin.faqs.index') }}" class="d-flex align-items-center">
                <select name="type" class="form-control rounded-pill mr-2" onchange="this.form.submit()">
                    <option value="">All FAQ Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected($selectedType === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <span class="badge badge-primary px-3 py-2">{{ $faqs->count() }} FAQs</span>
            </form>
        </div>
    </div>

    <div class="card-body p-4">
        @if(session('success'))
            <div class="alert alert-success rounded-lg">{{ session('success') }}</div>
        @endif

        @if($faqs->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th width="70">Order</th>
                            <th>Question</th>
                            <th width="160">Screen</th>
                            <th width="120">Status</th>
                            <th width="170" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faqs as $faq)
                            <tr>
                                <td>
                                    <span class="font-weight-bold">#{{ $faq->sort_order }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-start">
                                        <div class="faq-icon mr-3">
                                            <i class="fas fa-question"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-dark">{{ $faq->question_en ?: $faq->question }}</div>
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($faq->answer_en ?: $faq->answer, 110) }}</small>
                                            <div class="mt-2">
                                                @foreach($languages as $code => $label)
                                                    @php
                                                        $questionField = "question_{$code}";
                                                        $answerField = "answer_{$code}";
                                                    @endphp
                                                    <span class="badge {{ filled($faq->{$questionField}) || filled($faq->{$answerField}) ? 'badge-success' : 'badge-light' }} mr-1">
                                                        {{ strtoupper($code) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-light px-3 py-2">
                                        {{ $types[$faq->type] ?? ucfirst($faq->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($faq->is_active)
                                        <span class="badge badge-success px-3 py-2">Active</span>
                                    @else
                                        <span class="badge badge-secondary px-3 py-2">Hidden</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.faqs.edit', $faq->id) }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>

                                    <form action="{{ route('admin.faqs.delete', $faq->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this FAQ?')">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-question-circle" style="font-size: 80px; color: #d6d9ff;"></i>
                </div>
                <h4 class="font-weight-bold text-dark">No FAQs Found</h4>
                <p class="text-muted mb-4">Create FAQ questions for the app help and payment screens.</p>
                <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary px-5 rounded-pill shadow-sm">
                    <i class="fas fa-plus mr-1"></i> Create First FAQ
                </a>
            </div>
        @endif
    </div>
</div>
@stop

@section('css')
<style>
.card { border-radius: 18px; }
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
.faq-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #4f7cff, #6f42ff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    flex: 0 0 46px;
}
.btn-primary {
    background: linear-gradient(135deg, #4f7cff, #6f42ff);
    border: none;
}
.btn-primary:hover { opacity: 0.95; }
.badge-light {
    background: #f1f3ff;
    color: #5b5ff8;
    font-weight: 600;
}
.rounded-lg { border-radius: 18px; }
.alert { border: none; }
</style>
@stop
