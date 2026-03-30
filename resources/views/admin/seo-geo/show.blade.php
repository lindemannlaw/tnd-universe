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
            Alle Felder neu generieren (EN)
        </button>
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container>
        <div class="small text-muted mb-3">
            {{ $typeLabel }} &rarr; {{ $title }}
        </div>

        @php
            $fieldLabels = [
                'seo_title'       => ['label' => 'META TITLE',                   'maxLen' => 70,  'type' => 'input'],
                'seo_description' => ['label' => 'META DESCRIPTION',             'maxLen' => 160, 'type' => 'textarea'],
                'seo_keywords'    => ['label' => 'META KEYWORDS',                'maxLen' => null,'type' => 'input'],
                'geo_text'        => ['label' => 'GEO TEXT (AI-ZITIERBARKEIT)', 'maxLen' => null,'type' => 'textarea'],
            ];
            $localeFlags = [
                'de' => '🇩🇪', 'fr' => '🇫🇷', 'pl' => '🇵🇱', 'el' => '🇬🇷',
                'ru' => '🇷🇺', 'ar' => '🇸🇦', 'zh' => '🇨🇳', 'en' => '🇬🇧',
                'es' => '🇪🇸', 'it' => '🇮🇹', 'pt' => '🇵🇹', 'ja' => '🇯🇵',
            ];
        @endphp

        <div id="seoGeoFields">
            @foreach($fieldLabels as $field => $config)
                @php
                    $enVal = $fields[$field]['en'] ?? '';
                    $enLen = mb_strlen($enVal);
                @endphp
                <div class="card mb-4" data-field-card="{{ $field }}">
                    <div class="card-body">
                        {{-- Card header --}}
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-success">
                                <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check-lg"/></svg>
                            </span>
                            <h6 class="mb-0 fw-bold text-uppercase">{{ $config['label'] }}</h6>
                            @if($config['maxLen'])
                                <span class="badge bg-secondary bg-opacity-25 text-body ms-2">
                                    <span id="counter-{{ $field }}-en">{{ $enLen }}</span>/{{ $config['maxLen'] }}
                                </span>
                            @endif
                            <button type="button"
                                    class="btn btn-sm btn-outline-warning ms-auto btn-regen-field"
                                    data-field="{{ $field }}"
                                    title="Diesen Block für alle Sprachen neu generieren">
                                <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#arrow-clockwise"/></svg>
                                Block neu generieren
                            </button>
                        </div>

                        {{-- Per-locale rows --}}
                        @foreach($locales as $locale)
                            @php $val = $fields[$field][$locale] ?? ''; @endphp
                            <div class="d-flex align-items-start gap-2 mb-2" id="row-{{ $field }}-{{ $locale }}">
                                {{-- Flag + locale badge --}}
                                <span class="badge bg-light text-dark border mt-1" style="min-width: 48px; font-size: .75em;">
                                    {{ $localeFlags[$locale] ?? '' }} {{ strtoupper($locale) }}
                                </span>

                                {{-- Input --}}
                                <div class="flex-grow-1">
                                    @if($config['type'] === 'textarea')
                                        <textarea
                                            class="form-control form-control-sm seo-field-input"
                                            data-field="{{ $field }}"
                                            data-locale="{{ $locale }}"
                                            data-original="{{ $val }}"
                                            style="overflow:hidden;"
                                            @if($config['maxLen']) maxlength="{{ $config['maxLen'] }}" @endif
                                        >{{ $val }}</textarea>
                                    @else
                                        <input
                                            type="text"
                                            class="form-control form-control-sm seo-field-input"
                                            value="{{ $val }}"
                                            data-field="{{ $field }}"
                                            data-locale="{{ $locale }}"
                                            data-original="{{ $val }}"
                                            @if($config['maxLen']) maxlength="{{ $config['maxLen'] }}" @endif
                                        >
                                    @endif
                                    @if($config['maxLen'])
                                        <div class="form-text text-end" style="font-size:.7em;">
                                            <span class="counter-input" data-field="{{ $field }}" data-locale="{{ $locale }}">{{ mb_strlen($val) }}</span>/{{ $config['maxLen'] }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Action buttons (hidden until dirty) --}}
                                <div class="field-actions gap-1 align-items-start mt-1"
                                     id="actions-{{ $field }}-{{ $locale }}"
                                     style="display:none;">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success btn-save-field"
                                            data-field="{{ $field }}"
                                            data-locale="{{ $locale }}"
                                            title="Speichern">
                                        <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#floppy"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
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
    const TYPE          = @json($type);
    const ID            = @json($modelId);
    const GENERATE_URL  = @json(route('admin.seo-geo.generate'));
    const SAVE_FIELD_URL= @json(route('admin.seo-geo.save-field'));
    const CSRF          = document.querySelector('meta[name="csrf-token"]')?.content;

    // ── Auto-resize all textareas ─────────────────────────────────────────
    function autoResize(ta) {
        ta.style.height = 'auto';
        ta.style.height = ta.scrollHeight + 'px';
    }
    document.querySelectorAll('.seo-field-input').forEach(el => {
        if (el.tagName === 'TEXTAREA') autoResize(el);
    });

    // ── Dirty detection + action button reveal ────────────────────────────
    document.querySelectorAll('.seo-field-input').forEach(el => {
        el.addEventListener('input', () => {
            const field  = el.dataset.field;
            const locale = el.dataset.locale;
            const dirty  = el.value !== el.dataset.original;

            if (el.tagName === 'TEXTAREA') autoResize(el);

            // Per-row char counter
            const rowCounter = document.querySelector(`.counter-input[data-field="${field}"][data-locale="${locale}"]`);
            if (rowCounter) rowCounter.textContent = el.value.length;

            // Header counter (EN only)
            if (locale === 'en') {
                const headerCounter = document.getElementById(`counter-${field}-en`);
                if (headerCounter) headerCounter.textContent = el.value.length;
            }

            // Show/hide action buttons
            const actions = document.getElementById(`actions-${field}-${locale}`);
            if (actions) actions.style.display = dirty ? 'flex' : 'none';
        });
    });

    // ── Helper: mark field+locale as clean ───────────────────────────────
    function markClean(field, locale, value) {
        const input = document.querySelector(`.seo-field-input[data-field="${field}"][data-locale="${locale}"]`);
        if (input) {
            input.dataset.original = value;
            if (input.tagName === 'TEXTAREA') autoResize(input);
        }
        const actions = document.getElementById(`actions-${field}-${locale}`);
        if (actions) actions.style.display = 'none';
    }

    // ── Save single field+locale ──────────────────────────────────────────
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-save-field');
        if (!btn) return;

        const field  = btn.dataset.field;
        const locale = btn.dataset.locale;
        const input  = document.querySelector(`.seo-field-input[data-field="${field}"][data-locale="${locale}"]`);
        if (!input) return;

        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch(SAVE_FIELD_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ type: TYPE, id: ID, field, locale, value: input.value, translate: false }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            markClean(field, locale, input.value);
            showToast('Gespeichert!', 'bg-success');
        } catch (err) {
            showToast('Fehler: ' + err.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    });

    // ── Block neu generieren (one field, all locales) ─────────────────────
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-regen-field');
        if (!btn) return;

        const field = btn.dataset.field;
        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            // 1. Generate EN value via AI
            const genRes = await fetch(GENERATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ type: TYPE, id: ID, locale: 'en' }),
            });
            const genData = await genRes.json();
            if (genData.error) throw new Error(genData.error);
            const newEnValue = genData[field];
            if (!newEnValue) throw new Error(`Kein Wert für ${field} erhalten`);

            // 2. Save EN + translate to all locales
            const saveRes = await fetch(SAVE_FIELD_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ type: TYPE, id: ID, field, locale: 'en', value: newEnValue, translate: true }),
            });
            const saveData = await saveRes.json();
            if (saveData.error) throw new Error(saveData.error);

            // 3. Update all locale inputs from response
            const translations = saveData.translations || {};
            Object.entries(translations).forEach(([loc, val]) => {
                const input = document.querySelector(`.seo-field-input[data-field="${field}"][data-locale="${loc}"]`);
                if (input) {
                    input.value = val;
                    if (input.tagName === 'TEXTAREA') autoResize(input);
                    const rowCounter = document.querySelector(`.counter-input[data-field="${field}"][data-locale="${loc}"]`);
                    if (rowCounter) rowCounter.textContent = val.length;
                }
                markClean(field, loc, val);
            });

            // Update header counter from EN
            if (translations['en']) {
                const headerCounter = document.getElementById(`counter-${field}-en`);
                if (headerCounter) headerCounter.textContent = translations['en'].length;
            }

            showToast(`${field.replace('_', ' ')} neu generiert und übersetzt!`, 'bg-success');
        } catch (err) {
            showToast('Fehler: ' + err.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    });

    // ── Alle Felder neu generieren (EN only, no auto-save) ────────────────
    document.getElementById('btnGenerate')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnGenerate');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generiere...';

        try {
            const res = await fetch(GENERATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ type: TYPE, id: ID, locale: 'en' }),
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            // Fill only EN inputs, mark dirty so user can save/translate
            ['seo_title', 'seo_description', 'seo_keywords', 'geo_text'].forEach(field => {
                if (data[field] === undefined) return;
                const el = document.querySelector(`.seo-field-input[data-field="${field}"][data-locale="en"]`);
                if (el) {
                    el.value = data[field];
                    el.dispatchEvent(new Event('input'));
                }
            });

            showToast('EN-Vorschläge generiert — bitte speichern oder Block-Regenerierung nutzen.', 'bg-info');
        } catch (err) {
            showToast('Fehler: ' + err.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg> Alle Felder neu generieren (EN)';
        }
    });

    // ── Toast helper ──────────────────────────────────────────────────────
    function showToast(msg, bgClass) {
        const toast = document.getElementById('seoGeoToast');
        const msgEl = document.getElementById('seoGeoToastMsg');
        msgEl.textContent = msg;
        toast.className = 'toast align-items-center text-white border-0 ' + bgClass;
        new bootstrap.Toast(toast, { delay: 3500 }).show();
    }
});
</script>
@endpush
