<div class="row">
    <div class="col-md-8">
        <div class="form-group mb-4">
            <label class="font-weight-bold">Show On Screen</label>
            <select name="type" class="form-control rounded-lg">
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', optional($faq)->type) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('type')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group mb-4">
            <label class="font-weight-bold">Display Order</label>
            <input
                type="number"
                min="0"
                name="sort_order"
                class="form-control rounded-lg"
                value="{{ old('sort_order', optional($faq)->sort_order ?? 0) }}"
            >
            @error('sort_order')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
    </div>
</div>

<ul class="nav nav-pills language-tabs mb-4" role="tablist">
    @foreach($languages as $code => $label)
        @php
            $questionField = "question_{$code}";
            $answerField = "answer_{$code}";
            $hasTranslation = filled(optional($faq)->{$questionField}) || filled(optional($faq)->{$answerField});
        @endphp
        <li class="nav-item">
            <a
                class="nav-link {{ $code === 'en' ? 'active' : '' }}"
                data-toggle="pill"
                href="#faq-lang-{{ $code }}"
                role="tab"
            >
                {{ $label }} <span class="text-uppercase">({{ $code }})</span>
                @if($code === 'en')
                    <span class="badge badge-light ml-1">Default</span>
                @elseif($hasTranslation)
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
        @php
            $questionField = "question_{$code}";
            $answerField = "answer_{$code}";
        @endphp
        <div class="tab-pane fade {{ $code === 'en' ? 'show active' : '' }}" id="faq-lang-{{ $code }}" role="tabpanel">
            <div class="translation-panel {{ $code === 'ur' ? 'rtl-panel' : '' }}">
                <div class="form-group mb-4">
                    <label class="font-weight-bold">{{ $label }} Question {{ $code === 'en' ? '*' : '' }}</label>
                    <input
                        type="text"
                        name="question[{{ $code }}]"
                        class="form-control rounded-lg"
                        placeholder="{{ $label }} question"
                        value="{{ old('question.' . $code, optional($faq)->{$questionField}) }}"
                        dir="{{ $code === 'ur' ? 'rtl' : 'ltr' }}"
                    >
                    @error('question.' . $code)
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold">{{ $label }} Answer {{ $code === 'en' ? '*' : '' }}</label>
                    <textarea
                        name="answer[{{ $code }}]"
                        rows="7"
                        class="form-control rounded-lg"
                        placeholder="{{ $label }} answer shown in the app"
                        dir="{{ $code === 'ur' ? 'rtl' : 'ltr' }}"
                    >{{ old('answer.' . $code, optional($faq)->{$answerField}) }}</textarea>
                    @error('answer.' . $code)
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                    @if($code !== 'en')
                        <small class="text-muted">Leave empty to show English fallback.</small>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="custom-control custom-switch mt-4">
    <input
        type="checkbox"
        name="is_active"
        value="1"
        class="custom-control-input"
        id="isActive"
        @checked(old('is_active', optional($faq)->is_active ?? true))
    >
    <label class="custom-control-label font-weight-bold" for="isActive">
        Show this FAQ in mobile app
    </label>
</div>
