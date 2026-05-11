@extends('admin.layouts.base')

@section('title', 'SEO & GEO — ' . $title . ' - ' . config('app.name'))

@php
    $localeFlags = [
        'de' => '🇩🇪', 'fr' => '🇫🇷', 'pl' => '🇵🇱', 'el' => '🇬🇷',
        'ru' => '🇷🇺', 'ar' => '🇸🇦', 'zh' => '🇨🇳', 'en' => '🇬🇧',
        'es' => '🇪🇸', 'it' => '🇮🇹', 'pt' => '🇵🇹', 'ja' => '🇯🇵',
    ];
@endphp

@section('panel')
    <div id="mainPanel" class="main-panel d-flex flex-wrap align-items-center px-3 px-sm-4 py-2 border-bottom border-dark border-opacity-25 shadow-sm bg-white gap-2 gap-md-3">
        <a href="{{ route('admin.seo-geo.index') }}" class="btn btn-sm btn-outline-secondary flex-shrink-0">
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#arrow-left"/></svg>
            Zurück
        </a>
        <div class="fs-5 lh-1 fw-semibold text-start text-truncate flex-grow-1" title="SEO &amp; GEO — {{ $title }}">
            SEO &amp; GEO — {{ $title }}
        </div>
        <div class="d-flex align-items-center flex-wrap justify-content-end gap-2">
            <a href="{{ $editUrl }}" class="btn btn-sm btn-outline-secondary flex-shrink-0" title="Eintrag bearbeiten">
                <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#pencil"/></svg>
                Bearbeiten
            </a>
            <button type="button" class="btn btn-sm btn-outline-info flex-shrink-0" id="btnLivePreview" title="Was steht aktuell auf der Live-Site?">
                <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#globe"/></svg>
                Live-Vorschau
            </button>
            @if(!empty($searchConsoleUrls))
                <div class="btn-group flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="In Google Search Console öffnen → 'Indexierung beantragen' klicken">
                        <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#google"/></svg>
                        Google reindex
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach($searchConsoleUrls as $locale => $scUrl)
                            <li>
                                <a class="dropdown-item" href="{{ $scUrl }}" target="_blank" rel="noopener">
                                    {{ $localeFlags[$locale] ?? '' }} {{ strtoupper($locale) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" id="btnGenerate" title="Alle Felder neu generieren">
                <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
                Alle generieren
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" id="btnTranslateFromEn" title="Alle Felder von EN übersetzen">
                <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#globe2"/></svg>
                Aus EN übersetzen
            </button>
        </div>
    </div>
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
        @endphp

        <div id="livePreviewCard" class="card mb-4 border-info" style="display:none;">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-info">
                        <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#globe"/></svg>
                    </span>
                    <h6 class="mb-0 fw-bold text-uppercase">Live-Vorschau</h6>
                    <span class="text-muted small ms-2" id="livePreviewMeta"></span>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLivePreviewRefresh" title="Cache leeren und neu laden">
                            <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#arrow-clockwise"/></svg>
                            Neu laden
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLivePreviewClose" title="Schließen">
                            <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#x-lg"/></svg>
                        </button>
                    </div>
                </div>
                <div class="text-muted small mb-2">Vergleich zwischen Backoffice-Wert und tatsächlich auf der Produktion ausgeliefertem HTML. Cache 60 Sek.</div>
                <div id="livePreviewBody"></div>
            </div>
        </div>

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
                            <div class="d-flex justify-content-center gap-2 flex-grow-1">
                                <button type="button"
                                        class="btn btn-sm btn-dark btn-regen-field"
                                        data-field="{{ $field }}"
                                        title="Diesen Block für alle Sprachen neu generieren">
                                    <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#arrow-clockwise"/></svg>
                                    Block neu generieren
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary btn-translate-field"
                                        data-field="{{ $field }}"
                                        title="Diesen Block auf Basis von EN übersetzen">
                                    <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#globe2"/></svg>
                                    Block von EN übersetzen
                                </button>
                            </div>
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

    // ── Block von EN übersetzen (one field, all locales) ──────────────────
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-translate-field');
        if (!btn) return;

        const field = btn.dataset.field;
        const enInput = document.querySelector(`.seo-field-input[data-field="${field}"][data-locale="en"]`);
        if (!enInput) {
            showToast('Fehler: EN-Quelle nicht gefunden', 'bg-danger');
            return;
        }

        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const saveRes = await fetch(SAVE_FIELD_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    type: TYPE,
                    id: ID,
                    field,
                    locale: 'en',
                    value: enInput.value,
                    translate: true
                }),
            });
            const saveData = await saveRes.json();
            if (saveData.error) throw new Error(saveData.error);

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

            if (translations['en']) {
                const headerCounter = document.getElementById(`counter-${field}-en`);
                if (headerCounter) headerCounter.textContent = translations['en'].length;
            }

            showToast(`${field.replace('_', ' ')} von EN übersetzt!`, 'bg-success');
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

        const allFields = ['seo_title', 'seo_description', 'seo_keywords', 'geo_text'];
        let step = 0;
        const updateLabel = (msg) => {
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${msg}`;
        };

        try {
            // Step 1: Generate EN via AI
            updateLabel('Generiere EN…');
            const genRes = await fetch(GENERATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ type: TYPE, id: ID, locale: 'en' }),
            });
            const genData = await genRes.json();
            if (genData.error) throw new Error(genData.error);

            // Step 2: Save each field with translate=true (EN + all locales via DeepL)
            for (const field of allFields) {
                const value = genData[field];
                if (!value) continue;

                step++;
                updateLabel(`Übersetze Feld ${step}/${allFields.length}…`);

                const saveRes = await fetch(SAVE_FIELD_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ type: TYPE, id: ID, field, locale: 'en', value, translate: true }),
                });
                const saveData = await saveRes.json();
                if (saveData.error) throw new Error(saveData.error);

                // Update all locale inputs for this field
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

                if (translations['en']) {
                    const headerCounter = document.getElementById(`counter-${field}-en`);
                    if (headerCounter) headerCounter.textContent = translations['en'].length;
                }
            }

            showToast('Alle Felder generiert und in alle Sprachen übersetzt!', 'bg-success');
        } catch (err) {
            showToast('Fehler: ' + err.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg> Alle Felder neu generieren';
        }
    });

    // ── Alle Felder von EN übersetzen ──────────────────────────────────────
    document.getElementById('btnTranslateFromEn')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnTranslateFromEn');
        btn.disabled = true;

        const allFields = ['seo_title', 'seo_description', 'seo_keywords', 'geo_text'];
        let step = 0;
        const updateLabel = (msg) => {
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${msg}`;
        };

        try {
            for (const field of allFields) {
                const enInput = document.querySelector(`.seo-field-input[data-field="${field}"][data-locale="en"]`);
                const value = enInput?.value ?? '';
                if (!value) continue;

                step++;
                updateLabel(`Übersetze Feld ${step}/${allFields.length}…`);

                const saveRes = await fetch(SAVE_FIELD_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ type: TYPE, id: ID, field, locale: 'en', value, translate: true }),
                });
                const saveData = await saveRes.json();
                if (saveData.error) throw new Error(saveData.error);

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

                if (translations['en']) {
                    const headerCounter = document.getElementById(`counter-${field}-en`);
                    if (headerCounter) headerCounter.textContent = translations['en'].length;
                }
            }

            showToast('Alle Felder von EN in alle Sprachen übersetzt!', 'bg-success');
        } catch (err) {
            showToast('Fehler: ' + err.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#globe2"/></svg> Alle Felder von EN übersetzen';
        }
    });

    // ── Live-Vorschau ─────────────────────────────────────────────────────
    const LIVE_PREVIEW_URL = @json(route('admin.seo-geo.live-preview', ['type' => $type, 'id' => $modelId]));
    const LOCALE_FLAGS = @json($localeFlags);

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
        }[c]));
    }

    function getBackofficeValue(field, locale) {
        const el = document.querySelector(`.seo-field-input[data-field="${field}"][data-locale="${locale}"]`);
        return el ? el.value : '';
    }

    function renderLivePreview(data) {
        const body = document.getElementById('livePreviewBody');
        if (!data.supported) {
            body.innerHTML = `<div class="alert alert-warning mb-0">${escapeHtml(data.message || 'Nicht unterstützt')}</div>`;
            return;
        }
        const rows = Object.entries(data.results).map(([locale, r]) => {
            const flag = LOCALE_FLAGS[locale] || '';
            const boTitle = getBackofficeValue('seo_title', locale).trim();
            const boDesc  = getBackofficeValue('seo_description', locale).trim();
            const liveTitle = (r.title || '').trim();
            const liveDesc  = (r.description || '').trim();

            const titleMatch = boTitle && liveTitle && boTitle === liveTitle;
            const descMatch  = boDesc && liveDesc && boDesc === liveDesc;
            const httpOk     = r.status >= 200 && r.status < 400;

            const badge = (ok, label) => ok
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle">✓ ${label}</span>`
                : `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">≠ ${label}</span>`;

            const errorRow = r.error
                ? `<div class="text-danger small mt-1">Fehler: ${escapeHtml(r.error)}</div>`
                : '';

            return `
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-light text-dark border" style="min-width:48px;">${flag} ${locale.toUpperCase()}</span>
                        <a href="${escapeHtml(r.url)}" target="_blank" rel="noopener" class="small text-decoration-none">${escapeHtml(r.url)}</a>
                        <span class="badge ${httpOk ? 'bg-success' : 'bg-danger'} ms-auto">HTTP ${r.status || '?'}</span>
                        ${badge(titleMatch, 'Title')}
                        ${badge(descMatch, 'Description')}
                    </div>
                    <div class="row g-2 small">
                        <div class="col-md-6">
                            <div class="text-muted text-uppercase" style="font-size:.7em;">Backoffice — Title</div>
                            <div class="font-monospace">${escapeHtml(boTitle) || '<em class="text-muted">leer</em>'}</div>
                            <div class="text-muted text-uppercase mt-2" style="font-size:.7em;">Backoffice — Description</div>
                            <div class="font-monospace">${escapeHtml(boDesc) || '<em class="text-muted">leer</em>'}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted text-uppercase" style="font-size:.7em;">Live — Title</div>
                            <div class="font-monospace">${escapeHtml(liveTitle) || '<em class="text-muted">leer</em>'}</div>
                            <div class="text-muted text-uppercase mt-2" style="font-size:.7em;">Live — Description</div>
                            <div class="font-monospace">${escapeHtml(liveDesc) || '<em class="text-muted">leer</em>'}</div>
                        </div>
                    </div>
                    ${errorRow}
                </div>
            `;
        }).join('');
        body.innerHTML = rows || '<div class="text-muted">Keine Sprachen konfiguriert.</div>';
    }

    async function loadLivePreview(refresh) {
        const card = document.getElementById('livePreviewCard');
        const body = document.getElementById('livePreviewBody');
        const meta = document.getElementById('livePreviewMeta');
        card.style.display = '';
        body.innerHTML = '<div class="text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Lädt Live-HTML…</div>';
        meta.textContent = '';

        try {
            const url = LIVE_PREVIEW_URL + (refresh ? '?refresh=1' : '');
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            renderLivePreview(data);
            meta.textContent = 'Geladen ' + new Date().toLocaleTimeString();
        } catch (err) {
            body.innerHTML = `<div class="alert alert-danger mb-0">Fehler: ${escapeHtml(err.message)}</div>`;
        }
    }

    document.getElementById('btnLivePreview')?.addEventListener('click', () => loadLivePreview(false));
    document.getElementById('btnLivePreviewRefresh')?.addEventListener('click', () => loadLivePreview(true));
    document.getElementById('btnLivePreviewClose')?.addEventListener('click', () => {
        document.getElementById('livePreviewCard').style.display = 'none';
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
