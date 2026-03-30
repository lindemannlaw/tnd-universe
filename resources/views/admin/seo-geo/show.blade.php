@extends('admin.layouts.base')

@section('title', 'SEO & GEO — ' . $title . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel :title="'SEO & GEO — ' . $title">
        <a href="{{ route('admin.seo-geo.index') }}" class="btn btn-sm btn-outline-secondary me-2">
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#arrow-left"/></svg>
            Zurück
        </a>
        <button type="button" class="btn btn-sm btn-outline-primary me-2" id="btnGenerate">
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
            Neu generieren
        </button>
        <button type="button" class="btn btn-sm btn-primary" id="btnApply">
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check2-all"/></svg>
            Felder übernehmen + übersetzen
        </button>
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container>
        <div class="small text-muted mb-3">
            {{ $typeLabel }} &rarr; {{ $title }}
        </div>

        <div id="seoGeoFields">
            @php
                $fieldLabels = [
                    'seo_title' => ['label' => 'META TITLE', 'maxLen' => 70, 'type' => 'input'],
                    'seo_description' => ['label' => 'META DESCRIPTION', 'maxLen' => 160, 'type' => 'textarea'],
                    'seo_keywords' => ['label' => 'META KEYWORDS', 'maxLen' => null, 'type' => 'input'],
                    'geo_text' => ['label' => 'GEO TEXT (AI-ZITIERBARKEIT)', 'maxLen' => null, 'type' => 'textarea'],
                ];
                $defaultLocale = config('app.fallback_locale', 'en');
            @endphp

            @foreach($fieldLabels as $field => $config)
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-success">
                                <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check-lg"/></svg>
                            </span>
                            <h6 class="mb-0 fw-bold text-uppercase">{{ $config['label'] }}</h6>
                            @if($config['maxLen'])
                                <span class="badge bg-secondary bg-opacity-25 text-body ms-2" data-char-counter="{{ $field }}">
                                    <span data-char-count="{{ $field }}">{{ mb_strlen($fields[$field][$defaultLocale] ?? '') }}</span>/{{ $config['maxLen'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Current value per locale --}}
                        <div class="mb-3">
                            @foreach($locales as $locale)
                                @if(filled($fields[$field][$locale] ?? ''))
                                    <div class="d-flex align-items-start gap-2 mb-1">
                                        <span class="badge bg-light text-dark border" style="min-width: 40px;">{{ strtoupper($locale) }}</span>
                                        <div class="small text-muted">{{ $fields[$field][$locale] }}</div>
                                    </div>
                                @endif
                            @endforeach
                            @if(collect($fields[$field])->filter(fn($v) => filled($v))->isEmpty())
                                <span class="small text-muted fst-italic">— leer —</span>
                            @endif
                        </div>

                        {{-- Editable suggestion --}}
                        <div>
                            <label class="form-label small text-primary">Vorschlag (editierbar)</label>
                            @if($config['type'] === 'textarea')
                                <textarea
                                    class="form-control form-control-sm"
                                    name="suggestion[{{ $field }}]"
                                    rows="3"
                                    data-field="{{ $field }}"
                                    @if($config['maxLen']) maxlength="{{ $config['maxLen'] }}" @endif
                                >{{ $fields[$field][$defaultLocale] ?? '' }}</textarea>
                            @else
                                <input
                                    type="text"
                                    class="form-control form-control-sm"
                                    name="suggestion[{{ $field }}]"
                                    value="{{ $fields[$field][$defaultLocale] ?? '' }}"
                                    data-field="{{ $field }}"
                                    @if($config['maxLen']) maxlength="{{ $config['maxLen'] }}" @endif
                                >
                            @endif
                            @if($config['maxLen'])
                                <div class="form-text"><span data-char-count-input="{{ $field }}">{{ mb_strlen($fields[$field][$defaultLocale] ?? '') }}</span>/{{ $config['maxLen'] }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Toast --}}
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
            <div id="seoGeoToast" class="toast align-items-center text-white bg-success border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body" id="seoGeoToastMsg">Gespeichert!</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    </x-admin.container>
@endsection

@push('footer-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const TYPE   = @json($type);
    const ID     = @json($modelId);
    const LOCALE = @json($defaultLocale);
    const GENERATE_URL = @json(route('admin.seo-geo.generate'));
    const APPLY_URL    = @json(route('admin.seo-geo.apply'));
    const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content;

    // Character counters
    document.querySelectorAll('[data-field]').forEach(el => {
        el.addEventListener('input', () => {
            const field = el.dataset.field;
            const counter = document.querySelector(`[data-char-count-input="${field}"]`);
            if (counter) counter.textContent = el.value.length;
        });
    });

    // Generate
    document.getElementById('btnGenerate')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnGenerate');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generiere...';

        try {
            const res = await fetch(GENERATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ type: TYPE, id: ID, locale: LOCALE }),
            });
            const data = await res.json();

            if (data.error) throw new Error(data.error);

            ['seo_title', 'seo_description', 'seo_keywords', 'geo_text'].forEach(field => {
                const el = document.querySelector(`[data-field="${field}"]`);
                if (el && data[field]) {
                    el.value = data[field];
                    el.dispatchEvent(new Event('input'));
                }
            });

            showToast('Vorschläge generiert!', 'bg-success');
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg> Neu generieren';
        }
    });

    // Apply + Translate
    document.getElementById('btnApply')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnApply');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Speichere + übersetze...';

        const fields = {};
        document.querySelectorAll('[data-field]').forEach(el => {
            fields[el.dataset.field] = el.value;
        });

        try {
            const res = await fetch(APPLY_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ type: TYPE, id: ID, locale: LOCALE, fields }),
            });
            const data = await res.json();

            if (data.error) throw new Error(data.error);

            showToast('Gespeichert und in alle Sprachen übersetzt!', 'bg-success');

            // Reload after short delay to show updated values
            setTimeout(() => location.reload(), 1500);
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check2-all"/></svg> Felder übernehmen + übersetzen';
        }
    });

    function showToast(msg, bgClass) {
        const toast = document.getElementById('seoGeoToast');
        const msgEl = document.getElementById('seoGeoToastMsg');
        msgEl.textContent = msg;
        toast.className = 'toast align-items-center text-white border-0 ' + bgClass;
        new bootstrap.Toast(toast, { delay: 3000 }).show();
    }
});
</script>
@endpush
