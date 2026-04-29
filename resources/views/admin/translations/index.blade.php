@extends('admin.layouts.base')

@section('title', __('admin.translation_check') . ' - ' . config('app.name'))

@section('panel')
    {{-- Custom 3-col panel: title (sidebar width) | select-all | buttons --}}
    <div id="mainPanel" class="main-panel d-flex align-items-center px-3 px-sm-4 border-bottom border-dark border-opacity-25 shadow-sm bg-white">
        <div class="py-2 pe-2 fs-4 lh-1 fw-semibold flex-shrink-0"
             style="width:calc(220px + 1.25rem);">{{ __('admin.translation_check') }}</div>
        <div class="form-check mb-0 py-2 me-auto">
            <input class="form-check-input" type="checkbox" id="selectAll">
            <label class="form-check-label small" for="selectAll">{{ __('admin.select_all') }}</label>
        </div>
        <div class="d-flex align-items-center py-2 gap-3">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnTranslateSelected" disabled>
                <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#translate"/></svg>
                {{ __('admin.translate_with_deepl') }} (<span id="selectedCount">0</span>)
            </button>
            <button type="button" class="btn btn-sm btn-primary" id="btnApplyAll" disabled>
                <svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check2-all"/></svg>
                {{ __('admin.apply') }}
            </button>
        </div>
    </div>
@endsection

@section('content')
    @php
    $localeFlags = [
        'de' => '🇩🇪', 'fr' => '🇫🇷', 'pl' => '🇵🇱', 'el' => '🇬🇷',
        'ru' => '🇷🇺', 'ar' => '🇸🇦', 'zh' => '🇨🇳', 'en' => '🇬🇧',
        'es' => '🇪🇸', 'it' => '🇮🇹', 'pt' => '🇵🇹', 'ja' => '🇯🇵',
        'ko' => '🇰🇷', 'nl' => '🇳🇱', 'tr' => '🇹🇷',
    ];
    $statusConfig = [
        'all'          => ['label' => 'Alle',            'active' => 'btn-primary',   'inactive' => 'btn-outline-secondary'],
        'untranslated' => ['label' => 'Nicht übersetzt', 'active' => 'btn-danger',    'inactive' => 'btn-outline-secondary', 'badge' => 'bg-danger',            'badgeText' => 'text-danger'],
        'inherited'    => ['label' => 'Geerbt',          'active' => 'btn-secondary', 'inactive' => 'btn-outline-secondary', 'badge' => 'bg-secondary',         'badgeText' => 'text-secondary'],
        'ok'           => ['label' => 'OK',              'active' => 'btn-success',   'inactive' => 'btn-outline-secondary', 'badge' => 'bg-success',           'badgeText' => 'text-success'],
        'missing'      => ['label' => 'Fehlend',         'active' => 'btn-warning',   'inactive' => 'btn-outline-secondary', 'badge' => 'bg-warning text-dark', 'badgeText' => 'text-warning'],
    ];
    @endphp

    <div id="twoPanelLayout" class="d-flex px-3 px-sm-4" style="overflow:hidden; gap:1.25rem;">

            {{-- ── Sidebar ───────────────────────────────────────────────────── --}}
            <div style="width:220px; flex-shrink:0; overflow-y:auto; padding-right:2px;">

                    {{-- Sprachen --}}
                    <div class="border rounded mb-2">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 user-select-none sidebar-section-toggle"
                             style="cursor:pointer; background:var(--bs-light); border-radius:inherit;"
                             data-bs-toggle="collapse" data-bs-target="#sidebarLangs" aria-expanded="true">
                            <span class="text-uppercase fw-semibold text-muted" style="font-size:.7rem;letter-spacing:.05em;">{{ __('admin.languages') }}</span>
                            <svg class="bi sidebar-chevron" width="11" height="11" fill="currentColor" style="transition:transform .2s;flex-shrink:0;"><use xlink:href="/img/icons/bootstrap-icons.svg#chevron-up"/></svg>
                        </div>
                        <div class="collapse show" id="sidebarLangs">
                            <div class="px-2 py-2">
                                <div class="d-flex justify-content-between align-items-center mb-1 px-1" style="font-size:.8rem;">
                                    <span class="text-muted">{{ __('admin.for_deepl') }}:</span>
                                    <div class="d-flex gap-2">
                                        <a href="#" class="text-primary text-decoration-none" id="selectAllLangs">Alle</a>
                                        <span class="text-muted">/</span>
                                        <a href="#" class="text-secondary text-decoration-none" id="deselectAllLangs">Keine</a>
                                    </div>
                                </div>
                                @foreach($locales as $locale)
                                    @if($locale !== $sourceLang)
                                        @php $isPublished = $langSettings[$locale] ?? true; @endphp
                                        <div class="d-flex align-items-center py-1 px-1 gap-2 rounded {{ $targetLang === $locale ? 'bg-primary bg-opacity-10' : '' }}"
                                             style="font-size:.85rem;">
                                            <input type="checkbox"
                                                   class="form-check-input flex-shrink-0 lang-translate-check"
                                                   id="lang-check-{{ $locale }}"
                                                   data-locale="{{ $locale }}"
                                                   style="cursor:pointer;margin-top:0;"
                                                   {{ $targetLang === $locale ? 'checked' : '' }}>
                                            <a class="flex-grow-1 text-decoration-none {{ $targetLang === $locale ? 'fw-semibold text-primary' : 'text-body' }}"
                                               href="{{ route('admin.translations.index', array_merge(request()->only(['type', 'status', 'id']), ['lang' => $locale])) }}">
                                                {{ $localeFlags[$locale] ?? '' }} {{ strtoupper($locale) }}
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm p-0 border-0 bg-transparent lang-publish-toggle flex-shrink-0"
                                                    data-locale="{{ $locale }}"
                                                    data-published="{{ $isPublished ? '1' : '0' }}"
                                                    title="{{ $isPublished ? 'Live – klicken für Draft' : 'Draft – klicken für Live' }}">
                                                <span class="badge {{ $isPublished ? 'bg-success' : 'bg-secondary' }}" style="font-size:.65em;cursor:pointer;">
                                                    {{ $isPublished ? 'Live' : 'Draft' }}
                                                </span>
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="border rounded mb-2">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 user-select-none sidebar-section-toggle"
                             style="cursor:pointer; background:var(--bs-light);"
                             data-bs-toggle="collapse" data-bs-target="#sidebarStatus" aria-expanded="true">
                            <span class="text-uppercase fw-semibold text-muted" style="font-size:.7rem;letter-spacing:.05em;">Status</span>
                            <svg class="bi sidebar-chevron" width="11" height="11" fill="currentColor" style="transition:transform .2s;flex-shrink:0;"><use xlink:href="/img/icons/bootstrap-icons.svg#chevron-up"/></svg>
                        </div>
                        <div class="collapse show" id="sidebarStatus">
                            <div class="px-2 py-2">
                                <div class="d-flex justify-content-between align-items-center mb-1 px-1" style="font-size:.8rem;">
                                    <span class="text-muted">{{ __('admin.filter') }}:</span>
                                    <div class="d-flex gap-2">
                                        <a href="#" class="text-primary text-decoration-none" id="selectAllStatus">Alle</a>
                                        <span class="text-muted">/</span>
                                        <a href="#" class="text-secondary text-decoration-none" id="deselectAllStatus">Keine</a>
                                    </div>
                                </div>
                                <div class="d-flex flex-column" style="gap:2px;">
                                    @foreach($statusConfig as $key => $cfg)
                                        @php $active = in_array($key, $statusFilter); @endphp
                                        <label class="d-flex align-items-center gap-2 px-2 py-1 rounded small status-filter-label"
                                               style="cursor:pointer;{{ $active ? 'background:rgba(0,0,0,.06);' : '' }}">
                                            <input type="checkbox"
                                                   class="form-check-input flex-shrink-0 status-check"
                                                   data-status="{{ $key }}"
                                                   style="cursor:pointer;margin-top:0;"
                                                   {{ $active ? 'checked' : '' }}>
                                            <span class="flex-grow-1 {{ $active ? 'fw-semibold ' . ($cfg['badgeText'] ?? '') : 'text-body' }}">
                                                {{ $cfg['label'] }}
                                            </span>
                                            @if($key !== 'all' && isset($counts[$key]))
                                                <span class="badge {{ $cfg['badge'] ?? 'bg-secondary' }} ms-auto"
                                                      style="font-size:.7em;">{{ $counts[$key] }}</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Inhalt --}}
                    <div class="border rounded mb-2">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 user-select-none sidebar-section-toggle"
                             style="cursor:pointer; background:var(--bs-light); border-radius:inherit;"
                             data-bs-toggle="collapse" data-bs-target="#sidebarContent" aria-expanded="true">
                            <span class="text-uppercase fw-semibold text-muted" style="font-size:.7rem;letter-spacing:.05em;">{{ __('admin.content') }}</span>
                            <svg class="bi sidebar-chevron" width="11" height="11" fill="currentColor" style="transition:transform .2s;flex-shrink:0;"><use xlink:href="/img/icons/bootstrap-icons.svg#chevron-up"/></svg>
                        </div>
                        <div class="collapse show" id="sidebarContent">
                            @include('admin.partials.content-nav', [
                                'dashboard'   => 'translations',
                                'typeFilter'  => $typeFilter,
                                'idFilter'    => $idFilter,
                                'navPages'    => $navPages,
                                'navSections' => $navSections,
                                'extraParams' => ['lang' => $targetLang, 'status' => $statusFilter],
                                'inner'       => true,
                            ])
                        </div>
                    </div>

            </div>{{-- /sidebar --}}

            {{-- ── Main content ───────────────────────────────────────────────── --}}
            <div style="flex:1; overflow-y:auto; min-width:0;">

                {{-- Record sub-filter --}}
                @if($typeFilter !== 'all' && count($typeRecords) > 0)
                    <div class="mb-3">
                        @php
                            $selectedTypeLabel = collect($types)->firstWhere('key', $typeFilter)['label'] ?? '';
                        @endphp
                        <form method="GET" action="{{ route('admin.translations.index') }}" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="type" value="{{ $typeFilter }}">
                            <input type="hidden" name="lang" value="{{ $targetLang }}">
                            @foreach($statusFilter as $s)
                                <input type="hidden" name="status[]" value="{{ $s }}">
                            @endforeach
                            <select name="id" class="form-select form-select-sm" style="width:auto;max-width:240px;" onchange="this.form.submit()">
                                <option value="">— {{ __('admin.all_of_type', ['type' => $selectedTypeLabel]) }} —</option>
                                @foreach($typeRecords as $rec)
                                    <option value="{{ $rec['id'] }}" {{ (string)$idFilter === (string)$rec['id'] ? 'selected' : '' }}>
                                        {{ \Illuminate\Support\Str::limit($rec['title'], 35) }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                @endif

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
                                                    {{ __('admin.source_text') }}
                                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2 btn-save-source" data-index="{{ $i }}" title="{{ __('admin.save_source_text') }}" style="display:none;">
                                                        <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#floppy"/></svg>
                                                    </button>
                                                </label>
                                                <textarea class="form-control form-control-sm source-input" data-index="{{ $i }}" style="overflow:hidden; background: var(--bs-light);">{{ $item['source'] }}</textarea>
                                                <div class="small mt-1 placeholder-hint-source" data-index="{{ $i }}" style="display:none;"></div>
                                            </div>
                                            {{-- Translation columns inserted dynamically by JS --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            {{ __('admin.no_entries_for_filter') }}
                        </div>
                    @endforelse
                </div>

            </div>{{-- /content --}}
        </div>{{-- /twoPanelLayout --}}
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
                    <button type="button" class="btn btn-sm btn-primary" id="langPublishModalConfirm">{{ __('admin.yes_switch') }}</button>
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
    @php
        $i18nTranslatedTo = __('admin.translated_to', ['langs' => ':langs']);
        $i18nTranslatedSaved = __('admin.translated_saved', ['count' => ':count', 'langs' => ':langs']);
        $i18nTranslateWithDeeplCount = __('admin.translate_with_deepl_count', ['count' => ':count']);
    @endphp
    const I18N = @json([
        'variables' => __('admin.variables'),
        'missing_in_translation' => __('admin.missing_in_translation'),
        'suggestion_editable' => __('admin.suggestion_editable'),
        'source_saved' => __('admin.source_saved'),
        'please_select_at_least_one_language' => __('admin.please_select_at_least_one_language'),
        'translated_to' => $i18nTranslatedTo,
        'no_translation_received' => __('admin.no_translation_received'),
        'translated_saved' => $i18nTranslatedSaved,
        'apply' => __('admin.apply'),
        'translate_with_deepl_count' => $i18nTranslateWithDeeplCount,
        'translate_with_deepl' => __('admin.translate_with_deepl'),
        'switch_to_draft' => __('admin.switch_to_draft'),
        'switch_to_live' => __('admin.switch_to_live'),
    ]);

    // ── Two-panel layout height ───────────────────────────────────────────
    const layout = document.getElementById('twoPanelLayout');
    function resizeLayout() {
        if (!layout) return;
        layout.style.height = (window.innerHeight - layout.getBoundingClientRect().top - 8) + 'px';
    }
    resizeLayout();
    window.addEventListener('resize', resizeLayout);

    // ── Sidebar collapse chevron rotation ─────────────────────────────────
    document.querySelectorAll('.sidebar-section-toggle').forEach(toggle => {
        const targetId = toggle.dataset.bsTarget;
        const targetEl = document.querySelector(targetId);
        const chevron  = toggle.querySelector('.sidebar-chevron');
        if (!targetEl || !chevron) return;
        targetEl.addEventListener('hide.bs.collapse', () => chevron.style.transform = 'rotate(180deg)');
        targetEl.addEventListener('show.bs.collapse', () => chevron.style.transform = 'rotate(0deg)');
    });

    // ── Helpers ───────────────────────────────────────────────────────────
    function autoResize(ta) {
        ta.style.height = 'auto';
        ta.style.height = ta.scrollHeight + 'px';
    }
    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function extractPlaceholders(text) {
        const matches = String(text ?? '').match(/\{[^{}]+\}|:[A-Za-z_][A-Za-z0-9_]*/g) || [];
        return [...new Set(matches)];
    }
    function renderPlaceholderHints(idx) {
        const sourceTa = document.querySelector(`.source-input[data-index="${idx}"]`);
        if (!sourceTa) return;

        const sourceVars = extractPlaceholders(sourceTa.value);
        const sourceHint = document.querySelector(`.placeholder-hint-source[data-index="${idx}"]`);
        if (sourceHint) {
            if (!sourceVars.length) {
                sourceHint.style.display = 'none';
                sourceHint.innerHTML = '';
            } else {
                sourceHint.style.display = '';
                sourceHint.innerHTML = `<span class="text-muted">${escHtml(I18N.variables)}:</span> ${
                    sourceVars.map(v => `<span class="badge bg-dark-subtle text-dark border">${escHtml(v)}</span>`).join(' ')
                }`;
            }
        }

        document.querySelectorAll(`.placeholder-hint-target[data-index="${idx}"]`).forEach(hint => {
            const lang = hint.dataset.lang;
            const ta = document.querySelector(`.translation-input[data-index="${idx}"][data-lang="${lang}"]`);
            if (!ta) return;

            const targetVars = extractPlaceholders(ta.value);
            const missing = sourceVars.filter(v => !targetVars.includes(v));
            if (!sourceVars.length) {
                hint.style.display = 'none';
                hint.innerHTML = '';
                return;
            }

            const varBadges = sourceVars.map(v => {
                const isMissing = missing.includes(v);
                return `<span class="badge ${isMissing ? 'bg-danger' : 'bg-success'}">${escHtml(v)}</span>`;
            }).join(' ');

            hint.style.display = '';
            hint.innerHTML = `
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="text-muted">${escHtml(I18N.variables)}:</span> ${varBadges}
                    ${missing.length ? `<span class="text-danger ms-1">${escHtml(I18N.missing_in_translation)}</span>` : ''}
                </div>
            `;
        });
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
            ${escHtml(I18N.suggestion_editable)}
            <button type="button" class="btn btn-sm btn-link p-0 ms-2 btn-translate-single"
                    data-index="${idx}" data-lang="${lang}" title="${escHtml(I18N.translate_with_deepl_count.replace(':count', '1'))}">
                <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#translate"></use></svg>
            </button>
        </label>
        <textarea class="form-control form-control-sm translation-input"
                  data-index="${idx}" data-lang="${lang}"
                  style="overflow:hidden;">${escHtml(val)}</textarea>
        <div class="small mt-1 placeholder-hint-target" data-index="${idx}" data-lang="${lang}" style="display:none;"></div>`;
    }

    function updateColumns() {
        const langs = getCheckedLangs();
        const total = 1 + langs.length;
        const cc = colClass(total);

        document.querySelectorAll('.translation-row').forEach(row => {
            const srcCol = row.querySelector('.col-source');
            const idx = srcCol?.dataset.index;
            const item = ITEMS[idx];
            if (!srcCol || !item) return;

            const currentVals = {};
            row.querySelectorAll('.translation-input').forEach(ta => {
                currentVals[ta.dataset.lang] = ta.value;
            });

            srcCol.className = 'col-source ' + cc;
            row.querySelectorAll('.translation-col').forEach(el => el.remove());

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
                    ta.addEventListener('input', () => { autoResize(ta); autoCheckItem(idx); renderPlaceholderHints(idx); });
                }
            });

            renderPlaceholderHints(idx);
        });

        document.querySelectorAll('.source-input').forEach(ta => autoResize(ta));
    }

    // ── Source textarea auto-resize + save button ────────────────────────
    document.querySelectorAll('.source-input').forEach(ta => {
        const idx = ta.dataset.index;
        const saveBtn = document.querySelector(`.btn-save-source[data-index="${idx}"]`);
        const originalValue = ta.value;
        ta.addEventListener('input', () => {
            autoResize(ta);
            if (saveBtn) saveBtn.style.display = ta.value !== originalValue ? '' : 'none';
            autoCheckItem(idx);
            renderPlaceholderHints(idx);
        });
        renderPlaceholderHints(idx);
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
                showToast(I18N.source_saved, 'bg-success');
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

    // ── Status Alle / Keine ───────────────────────────────────────────────
    function navigateWithStatus(statuses) {
        const url = new URL(window.location.href);
        url.searchParams.delete('status');
        url.searchParams.delete('status[]');
        if (statuses.length === 0 || statuses.includes('all')) {
            url.searchParams.set('status', 'all');
        } else {
            statuses.forEach(s => url.searchParams.append('status[]', s));
        }
        window.location.href = url.toString();
    }

    document.getElementById('selectAllStatus')?.addEventListener('click', (e) => {
        e.preventDefault();
        navigateWithStatus(['all']);
    });
    document.getElementById('deselectAllStatus')?.addEventListener('click', (e) => {
        e.preventDefault();
        // Uncheck all without navigating so user can pick fresh
        document.querySelectorAll('.status-check:not([data-status="all"])').forEach(c => c.checked = false);
        const allCb = document.querySelector('.status-check[data-status="all"]');
        if (allCb) allCb.checked = true;
    });

    // ── Status multi-select (checkbox → URL navigation) ──────────────────
    document.querySelectorAll('.status-check').forEach(cb => {
        cb.addEventListener('change', () => {
            const allCb = document.querySelector('.status-check[data-status="all"]');
            if (cb.dataset.status === 'all' && cb.checked) {
                document.querySelectorAll('.status-check:not([data-status="all"])').forEach(c => c.checked = false);
            } else if (cb.dataset.status !== 'all') {
                if (allCb) allCb.checked = false;
            }
            const now = Array.from(document.querySelectorAll('.status-check:not([data-status="all"]):checked'))
                             .map(c => c.dataset.status);
            navigateWithStatus(now.length === 0 ? ['all'] : now);
        });
    });

    // ── Language multi-select + localStorage persistence ──────────────────
    const LANG_STORAGE_KEY = 'tnd_trans_checked_langs';

    function saveLangs() {
        const checked = Array.from(document.querySelectorAll('.lang-translate-check:checked'))
                             .map(cb => cb.dataset.locale);
        localStorage.setItem(LANG_STORAGE_KEY, JSON.stringify(checked));
    }

    function restoreLangs() {
        const stored = localStorage.getItem(LANG_STORAGE_KEY);
        if (stored === null) {
            // Default: all checked
            document.querySelectorAll('.lang-translate-check').forEach(cb => cb.checked = true);
        } else {
            try {
                const saved = JSON.parse(stored);
                document.querySelectorAll('.lang-translate-check').forEach(cb => {
                    cb.checked = saved.includes(cb.dataset.locale);
                });
            } catch(e) {
                document.querySelectorAll('.lang-translate-check').forEach(cb => cb.checked = true);
            }
        }
        updateColumns();
    }

    restoreLangs();

    document.getElementById('selectAllLangs')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.lang-translate-check').forEach(cb => cb.checked = true);
        saveLangs();
        updateColumns();
    });
    document.getElementById('deselectAllLangs')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.lang-translate-check').forEach(cb => cb.checked = false);
        saveLangs();
        updateColumns();
    });
    document.querySelectorAll('.lang-translate-check').forEach(cb => {
        cb.addEventListener('change', () => { saveLangs(); updateColumns(); });
    });

    // ── Translate selected (multi-lang) ───────────────────────────────────
    document.getElementById('btnTranslateSelected')?.addEventListener('click', async () => {
        const checked = getCheckedItems();
        if (!checked.length) return;
        const langs = getCheckedLangs();
        if (!langs.length) { showToast(I18N.please_select_at_least_one_language, 'bg-warning'); return; }

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

                data.translations?.forEach(t => {
                    const ci = checked.findIndex(c => c.type===t.type && c.id===t.id && c.field===t.field);
                    if (ci !== -1) {
                        const cbIdx = document.querySelectorAll('.item-checkbox:checked')[ci]?.dataset.index;
                        const ta = document.querySelector(`.translation-input[data-index="${cbIdx}"][data-lang="${lang}"]`);
                        if (ta) { ta.value = t.text; autoResize(ta); }
                        if (cbIdx !== undefined && ITEMS[cbIdx]) {
                            if (!ITEMS[cbIdx].translations) ITEMS[cbIdx].translations = {};
                            ITEMS[cbIdx].translations[lang] = t.text;
                        }
                    }
                });
            }
            showToast(I18N.translated_to.replace(':langs', langs.map(l=>l.toUpperCase()).join(', ')), 'bg-success');
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#translate"></use></svg> '
                + I18N.translate_with_deepl
                + ' (<span id="selectedCount">' + document.querySelectorAll('.item-checkbox:checked').length + '</span>)';
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
            if (data.error) throw new Error(data.error);
            const t = data.translations?.[0];
            if (t) {
                const ta = document.querySelector(`.translation-input[data-index="${idx}"][data-lang="${lang}"]`);
                if (ta) { ta.value = t.text; autoResize(ta); }
                if (!item.translations) item.translations = {};
                item.translations[lang] = t.text;
                autoCheckItem(idx);
            } else {
                showToast(I18N.no_translation_received, 'bg-warning');
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
            showToast(I18N.translated_saved.replace(':count', total).replace(':langs', langs), 'bg-success');
            setTimeout(() => location.reload(), 1500);
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check2-all"></use></svg> ' + I18N.apply;
        }
    });

    // ── Language publish/draft toggles ────────────────────────────────────
    const TOGGLE_BASE      = @json(route('admin.language-settings.toggle', ['locale' => '__LOCALE__']));
    const langModal        = new bootstrap.Modal(document.getElementById('langPublishModal'));
    const langModalText    = document.getElementById('langPublishModalText');
    const langModalConfirm = document.getElementById('langPublishModalConfirm');
    let pendingToggleBtn   = null;

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
            langModalConfirm.textContent = currentlyPublished ? I18N.switch_to_draft : I18N.switch_to_live;
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
            showToast(`${locale.toUpperCase()} ist jetzt ${isPublished ? 'Live' : 'Draft'}`, isPublished ? 'bg-success' : 'bg-secondary');
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        }
    });
});
</script>
@endpush
