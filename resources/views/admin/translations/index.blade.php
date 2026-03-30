@extends('admin.layouts.base')

@section('title', __('admin.translation_check') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel :title="__('admin.translation_check')">
        <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="btnSelectAll">
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check2-all"/></svg>
            Alles auswählen
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary me-2" id="btnTranslateSelected" disabled>
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#translate"/></svg>
            Mit DeepL übersetzen (<span id="selectedCount">0</span>)
        </button>
        <button type="button" class="btn btn-sm btn-primary" id="btnApplyAll" disabled>
            <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check2-all"/></svg>
            Übernehmen
        </button>
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container>
        <div class="row g-4">

            {{-- Sidebar nav --}}
            <div class="col-md-3 col-xl-2">
                @include('admin.partials.content-nav', [
                    'dashboard'   => 'translations',
                    'typeFilter'  => $typeFilter,
                    'idFilter'    => $idFilter,
                    'navPages'    => $navPages,
                    'navSections' => $navSections,
                    'extraParams' => ['lang' => $targetLang, 'status' => $statusFilter],
                ])
            </div>

            {{-- Main content --}}
            <div class="col-md-9 col-xl-10">

                {{-- Filters row --}}
                <div class="d-flex gap-3 mb-4 flex-wrap align-items-center">

                    {{-- Record sub-filter (only when a specific type is selected) --}}
                    @if($typeFilter !== 'all' && count($typeRecords) > 0)
                        <form method="GET" action="{{ route('admin.translations.index') }}" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="type" value="{{ $typeFilter }}">
                            <input type="hidden" name="lang" value="{{ $targetLang }}">
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                            <select name="id" class="form-select form-select-sm" style="width: auto; max-width: 240px;" onchange="this.form.submit()">
                                <option value="">— Alle {{ collect($types)->firstWhere('key', $typeFilter)['label'] ?? '' }} —</option>
                                @foreach($typeRecords as $rec)
                                    <option value="{{ $rec['id'] }}" {{ (string)$idFilter === (string)$rec['id'] ? 'selected' : '' }}>
                                        {{ \Illuminate\Support\Str::limit($rec['title'], 35) }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    {{-- Target language --}}
                    @php
                        $localeFlags = [
                            'de' => '🇩🇪', 'fr' => '🇫🇷', 'pl' => '🇵🇱', 'el' => '🇬🇷',
                            'ru' => '🇷🇺', 'ar' => '🇸🇦', 'zh' => '🇨🇳', 'en' => '🇬🇧',
                            'es' => '🇪🇸', 'it' => '🇮🇹', 'pt' => '🇵🇹', 'ja' => '🇯🇵',
                            'ko' => '🇰🇷', 'nl' => '🇳🇱', 'tr' => '🇹🇷',
                        ];
                    @endphp
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" id="langDropdownBtn">
                            {{ $localeFlags[$targetLang] ?? '' }} {{ strtoupper($targetLang) }}
                            @if(isset($langSettings[$targetLang]))
                                <span class="badge ms-1 {{ $langSettings[$targetLang] ? 'bg-success' : 'bg-secondary' }}" style="font-size:.65em;">
                                    {{ $langSettings[$targetLang] ? 'Live' : 'Draft' }}
                                </span>
                            @endif
                            <span id="extraLangsIndicator" class="badge bg-primary ms-1" style="display:none; font-size:.65em;"></span>
                        </button>
                        <ul class="dropdown-menu pt-1 pb-1" style="min-width: 210px;">
                            {{-- Select all / none --}}
                            <li class="d-flex justify-content-between align-items-center px-3 py-1">
                                <small class="text-muted">Für DeepL:</small>
                                <div class="d-flex gap-2">
                                    <a href="#" class="small text-primary text-decoration-none" id="selectAllLangs">Alle</a>
                                    <span class="text-muted small">/</span>
                                    <a href="#" class="small text-secondary text-decoration-none" id="deselectAllLangs">Keine</a>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            @foreach($locales as $locale)
                                @if($locale !== $sourceLang)
                                    @php $isPublished = $langSettings[$locale] ?? true; @endphp
                                    <li class="d-flex align-items-center px-2 py-1 gap-2 {{ $targetLang === $locale ? 'bg-light rounded mx-1' : '' }}">
                                        <input type="checkbox"
                                               class="form-check-input flex-shrink-0 lang-translate-check"
                                               id="lang-check-{{ $locale }}"
                                               data-locale="{{ $locale }}"
                                               style="cursor:pointer; margin-top:0;"
                                               {{ $targetLang === $locale ? 'checked' : '' }}>
                                        <a class="dropdown-item flex-grow-1 py-0 px-1 {{ $targetLang === $locale ? 'fw-semibold' : '' }}"
                                           href="{{ route('admin.translations.index', array_merge(request()->only(['type', 'status', 'id']), ['lang' => $locale])) }}">
                                            {{ $localeFlags[$locale] ?? '' }} {{ strtoupper($locale) }}
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm p-0 border-0 bg-transparent lang-publish-toggle flex-shrink-0"
                                            data-locale="{{ $locale }}"
                                            data-published="{{ $isPublished ? '1' : '0' }}"
                                            title="{{ $isPublished ? 'Live – klicken für Draft' : 'Draft – klicken für Live' }}">
                                            <span class="badge {{ $isPublished ? 'bg-success' : 'bg-secondary' }}" style="font-size:.7em; cursor:pointer;">
                                                {{ $isPublished ? 'Live' : 'Draft' }}
                                            </span>
                                        </button>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    <input type="hidden" name="lang" value="{{ $targetLang }}">

                    {{-- Status filter --}}
                    @php
                    $statusConfig = [
                        'all'          => ['label' => 'Alle',             'active' => 'btn-primary',   'inactive' => 'btn-outline-secondary', 'badge' => 'bg-secondary',         'badgeText' => 'text-secondary'],
                        'untranslated' => ['label' => 'Nicht übersetzt',  'active' => 'btn-danger',    'inactive' => 'btn-outline-secondary', 'badge' => 'bg-danger',            'badgeText' => 'text-danger'],
                        'inherited'    => ['label' => 'Geerbt',           'active' => 'btn-secondary', 'inactive' => 'btn-outline-secondary', 'badge' => 'bg-secondary',         'badgeText' => 'text-secondary'],
                        'ok'           => ['label' => 'OK',               'active' => 'btn-success',   'inactive' => 'btn-outline-secondary', 'badge' => 'bg-success',           'badgeText' => 'text-success'],
                        'missing'      => ['label' => 'Fehlend',          'active' => 'btn-warning',   'inactive' => 'btn-outline-secondary', 'badge' => 'bg-warning text-dark', 'badgeText' => 'text-warning'],
                    ];
                    @endphp
                    <div class="btn-group btn-group-sm">
                        @foreach($statusConfig as $key => $cfg)
                            <a href="{{ route('admin.translations.index', array_merge(request()->only(['type', 'lang', 'id']), ['status' => $key])) }}"
                               class="btn {{ $statusFilter === $key ? $cfg['active'] : $cfg['inactive'] }}">
                                {{ $cfg['label'] }}
                                @if($key !== 'all' && isset($counts[$key]))
                                    @if($counts[$key] === 0)
                                        <span class="ms-1 {{ $cfg['badgeText'] }}" style="font-size:.75em; font-weight:600;">0</span>
                                    @else
                                        <span class="badge {{ $cfg['badge'] }} ms-1">{{ $counts[$key] }}</span>
                                    @endif
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Select all --}}
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label small" for="selectAll">Alle auswählen</label>
                    </div>
                </div>

                {{-- Items --}}
                <div id="translationItems">
                    @forelse($items as $i => $item)
                        <div class="card mb-3 translation-item" data-index="{{ $i }}" data-type="{{ $item['type'] }}" data-id="{{ $item['id'] }}" data-field="{{ $item['field'] }}">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="pt-1">
                                        <input class="form-check-input item-checkbox" type="checkbox" data-index="{{ $i }}">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge {{ $item['statusClass'] }}">{{ $item['statusLabel'] }}</span>
                                            <span class="badge bg-secondary bg-opacity-25 text-body">{{ $item['typeLabel'] }}</span>
                                            <span class="small fw-semibold">{{ $item['title'] }}</span>
                                            <span class="small text-muted">&middot; {{ $item['fieldLabel'] }}</span>
                                        </div>

                                        <div class="row g-2 translation-row">
                                            {{-- Source --}}
                                            <div class="col-source" data-index="{{ $i }}">
                                                <label class="form-label small text-muted">
                                                    <span class="badge bg-light text-dark border">{{ strtoupper($sourceLang) }}</span>
                                                    Quelltext
                                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2 btn-save-source" data-index="{{ $i }}" title="Quelltext speichern" style="display:none;">
                                                        <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#floppy"/></svg>
                                                    </button>
                                                </label>
                                                <textarea class="form-control form-control-sm source-input" data-index="{{ $i }}" style="overflow:hidden; background: var(--bs-light);">{{ $item['source'] }}</textarea>
                                            </div>
                                            {{-- Translation columns inserted dynamically by JS --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            Keine Einträge für den gewählten Filter.
                        </div>
                    @endforelse
                </div>

            </div>{{-- /col --}}
        </div>{{-- /row --}}
    </x-admin.container>
@endsection

@push('modals')
    {{-- Toast --}}
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
        <div id="transToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive">
            <div class="d-flex">
                <div class="toast-body fw-semibold" id="transToastMsg"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    {{-- Language publish confirm modal --}}
    <div class="modal fade" id="langPublishModal" tabindex="-1" aria-labelledby="langPublishModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-semibold" id="langPublishModalLabel">Sprache umschalten</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="langPublishModalText" class="mb-0"></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-sm btn-primary" id="langPublishModalConfirm">Ja, umschalten</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('footer-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const TRANSLATE_URL  = @json(route('admin.translations.translate'));
    const APPLY_URL      = @json(route('admin.translations.apply'));
    const SOURCE_LANG    = @json($sourceLang);
    const TARGET_LANG    = @json($targetLang);
    const CSRF           = document.querySelector('meta[name="csrf-token"]')?.content;
    const LOCALE_FLAGS   = @json($localeFlags);
    const ITEMS          = @json($items);

    // ── Helpers ───────────────────────────────────────────────────────────
    function autoResize(ta) {
        ta.style.height = 'auto';
        ta.style.height = ta.scrollHeight + 'px';
    }
    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function showToast(msg, bgClass) {
        const toast = document.getElementById('transToast');
        const msgEl = document.getElementById('transToastMsg');
        msgEl.textContent = msg;
        toast.className = 'toast align-items-center text-white border-0 ' + bgClass;
        new bootstrap.Toast(toast, { delay: 3500 }).show();
    }
    function getCheckedItems() {
        const items = [];
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            const idx = cb.dataset.index;
            const item = ITEMS[idx];
            if (item) items.push({ type: item.type, id: item.id, field: item.field });
        });
        return items;
    }

    // ── Multi-language column rendering ───────────────────────────────────
    function getCheckedLangs() {
        return Array.from(document.querySelectorAll('.lang-translate-check:checked'))
            .map(cb => cb.dataset.locale);
    }

    function colClass(total) {
        if (total >= 5) return 'col-md-2';
        if (total === 4) return 'col-md-3';
        if (total === 3) return 'col-md-4';
        return 'col-md-6';
    }

    function buildTranslationCol(idx, lang, val) {
        const flag = LOCALE_FLAGS[lang] || '';
        return `<label class="form-label small text-muted">
            <span class="badge bg-light text-dark border">${flag} ${lang.toUpperCase()}</span>
            Vorschlag (editierbar)
            <button type="button" class="btn btn-sm btn-link p-0 ms-2 btn-translate-single"
                    data-index="${idx}" data-lang="${lang}" title="Mit DeepL übersetzen">
                <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#translate"></use></svg>
            </button>
        </label>
        <textarea class="form-control form-control-sm translation-input"
                  data-index="${idx}" data-lang="${lang}"
                  style="overflow:hidden;">${escHtml(val)}</textarea>`;
    }

    function updateColumns() {
        const langs = getCheckedLangs();
        const total = 1 + langs.length; // 1 for source
        const cc = colClass(total);

        document.querySelectorAll('.translation-row').forEach(row => {
            const srcCol = row.querySelector('.col-source');
            const idx = srcCol?.dataset.index;
            const item = ITEMS[idx];
            if (!srcCol || !item) return;

            // Preserve any edited values
            const currentVals = {};
            row.querySelectorAll('.translation-input').forEach(ta => {
                currentVals[ta.dataset.lang] = ta.value;
            });

            // Update source column width
            srcCol.className = 'col-source ' + cc;

            // Remove old translation cols
            row.querySelectorAll('.translation-col').forEach(el => el.remove());

            // Add one column per lang
            langs.forEach(lang => {
                const val = currentVals[lang] ?? item.translations?.[lang] ?? '';
                const div = document.createElement('div');
                div.className = 'translation-col ' + cc;
                div.dataset.lang = lang;
                div.innerHTML = buildTranslationCol(idx, lang, val);
                row.appendChild(div);

                const ta = div.querySelector('textarea');
                if (ta) {
                    autoResize(ta);
                    ta.addEventListener('input', () => { autoResize(ta); autoCheckItem(idx); });
                }
            });
        });

        // Auto-resize source textareas
        document.querySelectorAll('.source-input').forEach(ta => autoResize(ta));
    }

    // Initial render
    updateColumns();

    // ── Source textarea auto-resize + save button ────────────────────────
    document.querySelectorAll('.source-input').forEach(ta => {
        const idx = ta.dataset.index;
        const saveBtn = document.querySelector(`.btn-save-source[data-index="${idx}"]`);
        const originalValue = ta.value;
        ta.addEventListener('input', () => {
            autoResize(ta);
            if (saveBtn) saveBtn.style.display = ta.value !== originalValue ? '' : 'none';
            autoCheckItem(idx);
        });
    });

    function autoCheckItem(idx) {
        const cb = document.querySelector(`.item-checkbox[data-index="${idx}"]`);
        if (cb && !cb.checked) { cb.checked = true; updateSelectedCount(); }
    }

    document.querySelectorAll('.btn-save-source').forEach(btn => {
        btn.addEventListener('click', async () => {
            const idx = btn.dataset.index;
            const item = ITEMS[idx];
            const ta = document.querySelector(`.source-input[data-index="${idx}"]`);
            if (!item || !ta) return;

            btn.disabled = true;
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            try {
                const res = await fetch(APPLY_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({
                        items: [{ type: item.type, id: item.id, field: item.field, text: ta.value }],
                        target_lang: SOURCE_LANG,
                    }),
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                btn.style.display = 'none';
                showToast('Quelltext gespeichert', 'bg-success');
            } catch (e) {
                showToast('Fehler: ' + e.message, 'bg-danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        });
    });

    // ── Item checkboxes ───────────────────────────────────────────────────
    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.addEventListener('change', updateSelectedCount));

    function updateSelectedCount() {
        const count = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('btnTranslateSelected').disabled = count === 0;
        document.getElementById('btnApplyAll').disabled = count === 0;
    }

    // ── Language multi-select ─────────────────────────────────────────────
    document.getElementById('selectAllLangs')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.lang-translate-check').forEach(cb => cb.checked = true);
        updateExtraLangsIndicator();
        updateColumns();
    });
    document.getElementById('deselectAllLangs')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.lang-translate-check').forEach(cb => cb.checked = false);
        updateExtraLangsIndicator();
        updateColumns();
    });
    document.querySelectorAll('.lang-translate-check').forEach(cb => {
        cb.addEventListener('change', () => {
            updateExtraLangsIndicator();
            updateColumns();
        });
    });

    function updateExtraLangsIndicator() {
        const extra = Array.from(document.querySelectorAll('.lang-translate-check:checked'))
            .filter(cb => cb.dataset.locale !== TARGET_LANG);
        const indicator = document.getElementById('extraLangsIndicator');
        if (extra.length > 0) {
            indicator.textContent = '+' + extra.length;
            indicator.style.display = '';
        } else {
            indicator.style.display = 'none';
        }
    }

    // ── Select all (items + languages) ───────────────────────────────────
    document.getElementById('btnSelectAll')?.addEventListener('click', () => {
        document.querySelectorAll('.lang-translate-check').forEach(cb => cb.checked = true);
        updateExtraLangsIndicator();
        updateColumns();
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = true);
        const selectAllCb = document.getElementById('selectAll');
        if (selectAllCb) selectAllCb.checked = true;
        updateSelectedCount();
    });

    // ── Translate selected (multi-lang) ───────────────────────────────────
    document.getElementById('btnTranslateSelected')?.addEventListener('click', async () => {
        const checked = getCheckedItems();
        if (!checked.length) return;
        const langs = getCheckedLangs();
        if (!langs.length) { showToast('Bitte mindestens eine Sprache auswählen.', 'bg-warning'); return; }

        const btn = document.getElementById('btnTranslateSelected');
        btn.disabled = true;

        try {
            for (let i = 0; i < langs.length; i++) {
                const lang = langs[i];
                btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${lang.toUpperCase()} (${i+1}/${langs.length})…`;

                const res = await fetch(TRANSLATE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ items: checked, source_lang: SOURCE_LANG, target_lang: lang }),
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);

                // Fill textareas for this lang
                data.translations?.forEach(t => {
                    const ci = checked.findIndex(c => c.type===t.type && c.id===t.id && c.field===t.field);
                    if (ci !== -1) {
                        const cbIdx = document.querySelectorAll('.item-checkbox:checked')[ci]?.dataset.index;
                        const ta = document.querySelector(`.translation-input[data-index="${cbIdx}"][data-lang="${lang}"]`);
                        if (ta) { ta.value = t.text; autoResize(ta); }
                        // Also update ITEMS cache
                        if (cbIdx !== undefined && ITEMS[cbIdx]) {
                            if (!ITEMS[cbIdx].translations) ITEMS[cbIdx].translations = {};
                            ITEMS[cbIdx].translations[lang] = t.text;
                        }
                    }
                });
            }
            showToast(`Übersetzt nach: ${langs.map(l=>l.toUpperCase()).join(', ')}`, 'bg-success');
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#translate"></use></svg> Mit DeepL übersetzen (<span id="selectedCount">' + document.querySelectorAll('.item-checkbox:checked').length + '</span>)';
            btn.disabled = document.querySelectorAll('.item-checkbox:checked').length === 0;
        }
    });

    // ── Single translate (event delegation — columns are dynamic) ─────────
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-translate-single');
        if (!btn) return;

        const idx = btn.dataset.index;
        const lang = btn.dataset.lang || TARGET_LANG;
        const item = ITEMS[idx];
        if (!item) return;

        btn.disabled = true;
        try {
            const res = await fetch(TRANSLATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    items: [{ type: item.type, id: item.id, field: item.field }],
                    source_lang: SOURCE_LANG,
                    target_lang: lang,
                }),
            });
            const data = await res.json();
            if (data.translations?.[0]) {
                const ta = document.querySelector(`.translation-input[data-index="${idx}"][data-lang="${lang}"]`);
                if (ta) { ta.value = data.translations[0].text; autoResize(ta); }
                if (!item.translations) item.translations = {};
                item.translations[lang] = data.translations[0].text;
                autoCheckItem(idx);
            }
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
        }
    });

    // ── Apply all checked (per lang) ──────────────────────────────────────
    document.getElementById('btnApplyAll')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnApplyAll');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Speichere...';

        // Group items by language
        const byLang = {};
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            const idx = cb.dataset.index;
            const item = ITEMS[idx];
            if (!item) return;
            document.querySelectorAll(`.translation-input[data-index="${idx}"]`).forEach(ta => {
                const lang = ta.dataset.lang || TARGET_LANG;
                if (!byLang[lang]) byLang[lang] = [];
                byLang[lang].push({ type: item.type, id: item.id, field: item.field, text: ta.value });
            });
        });

        try {
            let total = 0;
            for (const [lang, applyItems] of Object.entries(byLang)) {
                const res = await fetch(APPLY_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ items: applyItems, target_lang: lang }),
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                total += applyItems.length;
            }
            const langs = Object.keys(byLang).map(l => l.toUpperCase()).join(', ');
            showToast(`${total} Übersetzungen gespeichert (${langs})!`, 'bg-success');
            setTimeout(() => location.reload(), 1500);
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check2-all"></use></svg> Übernehmen';
        }
    });

    // ── Language publish/draft toggles ────────────────────────────────────
    const TOGGLE_BASE     = @json(route('admin.language-settings.toggle', ['locale' => '__LOCALE__']));
    const langModal       = new bootstrap.Modal(document.getElementById('langPublishModal'));
    const langModalText   = document.getElementById('langPublishModalText');
    const langModalConfirm= document.getElementById('langPublishModalConfirm');
    let pendingToggleBtn  = null;

    document.querySelectorAll('.lang-publish-toggle').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const locale = btn.dataset.locale;
            const currentlyPublished = btn.dataset.published === '1';
            const action = currentlyPublished ? 'auf <strong>Draft</strong> setzen' : 'auf <strong>Live</strong> stellen';
            langModalText.innerHTML = `Möchtest du <strong>${locale.toUpperCase()}</strong> wirklich ${action}?`
                + (currentlyPublished
                    ? '<br><span class="small text-muted mt-1 d-block">Die Sprache wird im Frontend ausgeblendet.</span>'
                    : '<span class="small text-muted mt-1 d-block">Die Sprache erscheint sofort im Frontend.</span>');
            langModalConfirm.className = 'btn btn-sm ' + (currentlyPublished ? 'btn-danger' : 'btn-success');
            langModalConfirm.textContent = currentlyPublished ? 'Ja, auf Draft setzen' : 'Ja, live stellen';
            pendingToggleBtn = btn;
            langModal.show();
        });
    });

    langModalConfirm.addEventListener('click', async () => {
        langModal.hide();
        if (!pendingToggleBtn) return;
        const btn = pendingToggleBtn;
        pendingToggleBtn = null;
        const locale = btn.dataset.locale;
        const url = TOGGLE_BASE.replace('__LOCALE__', locale);
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            const isPublished = data.is_published;
            btn.dataset.published = isPublished ? '1' : '0';
            const badge = btn.querySelector('.badge');
            badge.textContent = isPublished ? 'Live' : 'Draft';
            badge.className = 'badge ' + (isPublished ? 'bg-success' : 'bg-secondary');
            btn.title = isPublished ? 'Live – klicken für Draft' : 'Draft – klicken für Live';

            const dropBtnBadge = btn.closest('.dropdown')?.querySelector('.dropdown-toggle .badge');
            if (dropBtnBadge && locale === TARGET_LANG) {
                dropBtnBadge.textContent = isPublished ? 'Live' : 'Draft';
                dropBtnBadge.className = 'badge ms-1 ' + (isPublished ? 'bg-success' : 'bg-secondary');
            }
            showToast(`${locale.toUpperCase()} ist jetzt ${isPublished ? 'Live' : 'Draft'}`, isPublished ? 'bg-success' : 'bg-secondary');
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        }
    });
});
</script>
@endpush
