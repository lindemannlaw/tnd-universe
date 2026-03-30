@extends('admin.layouts.base')

@section('title', __('admin.translation_check') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel :title="__('admin.translation_check')">
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

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.translations.index') }}" class="d-flex gap-3 mb-4 flex-wrap align-items-center">
            {{-- Type filter --}}
            <select name="type" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>Alle Typen</option>
                @foreach($types as $t)
                    <option value="{{ $t['key'] }}" {{ $typeFilter === $t['key'] ? 'selected' : '' }}>{{ $t['label'] }}</option>
                @endforeach
            </select>

            {{-- Record sub-filter (only when a specific type is selected) --}}
            @if($typeFilter !== 'all' && count($typeRecords) > 0)
                <select name="id" class="form-select form-select-sm" style="width: auto; max-width: 220px;" onchange="this.form.submit()">
                    <option value="">— Alle {{ collect($types)->firstWhere('key', $typeFilter)['label'] ?? '' }} —</option>
                    @foreach($typeRecords as $rec)
                        <option value="{{ $rec['id'] }}" {{ (string)$idFilter === (string)$rec['id'] ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Str::limit($rec['title'], 35) }}
                        </option>
                    @endforeach
                </select>
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
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    {{ $localeFlags[$targetLang] ?? '' }} {{ strtoupper($targetLang) }}
                    @if(isset($langSettings[$targetLang]))
                        <span class="badge ms-1 {{ $langSettings[$targetLang] ? 'bg-success' : 'bg-secondary' }}" style="font-size:.65em;">
                            {{ $langSettings[$targetLang] ? 'Live' : 'Draft' }}
                        </span>
                    @endif
                </button>
                <ul class="dropdown-menu" style="min-width: 180px;">
                    @foreach($locales as $locale)
                        @if($locale !== $sourceLang)
                            @php $isPublished = $langSettings[$locale] ?? true; @endphp
                            <li class="d-flex align-items-center px-2 py-1 gap-2 {{ $targetLang === $locale ? 'bg-light' : '' }}">
                                <a class="dropdown-item flex-grow-1 py-0 px-1 {{ $targetLang === $locale ? 'fw-semibold' : '' }}"
                                   href="{{ route('admin.translations.index', array_merge(request()->only(['type', 'status', 'id']), ['lang' => $locale])) }}">
                                    {{ $localeFlags[$locale] ?? '' }} {{ strtoupper($locale) }}
                                </a>
                                <button type="button"
                                    class="btn btn-sm p-0 border-0 bg-transparent lang-publish-toggle"
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
            <div class="btn-group btn-group-sm">
                @foreach(['all' => 'Alle', 'untranslated' => 'Nicht übersetzt', 'inherited' => 'Geerbt', 'ok' => 'OK', 'missing' => 'Fehlend'] as $key => $label)
                    <a href="{{ route('admin.translations.index', array_merge(request()->only(['type', 'lang', 'id']), ['status' => $key])) }}"
                       class="btn {{ $statusFilter === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                        @if($key !== 'all' && isset($counts[$key]))
                            <span class="badge bg-light text-dark ms-1">{{ $counts[$key] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <input type="hidden" name="status" value="{{ $statusFilter }}">
        </form>

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

                                <div class="row g-3">
                                    {{-- Source --}}
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">
                                            <span class="badge bg-light text-dark border">{{ strtoupper($sourceLang) }}</span>
                                            Quelltext
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-2 btn-save-source" data-index="{{ $i }}" title="Quelltext speichern" style="display:none;">
                                                <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#floppy"/></svg>
                                            </button>
                                        </label>
                                        <textarea class="form-control form-control-sm source-input" data-index="{{ $i }}" style="overflow:hidden; background: var(--bs-light);">{{ $item['source'] }}</textarea>
                                    </div>

                                    {{-- Target (editable) --}}
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">
                                            <span class="badge bg-light text-dark border">{{ strtoupper($targetLang) }}</span>
                                            Vorschlag (editierbar)
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-2 btn-translate-single" data-index="{{ $i }}" title="Mit DeepL übersetzen">
                                                <svg class="bi" width="14" height="14" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#translate"/></svg>
                                            </button>
                                        </label>
                                        <textarea class="form-control form-control-sm translation-input" data-index="{{ $i }}" style="overflow: hidden;">{{ $item['target'] }}</textarea>
                                    </div>
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

        {{-- Toast --}}
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
            <div id="transToast" class="toast align-items-center text-white bg-success border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body" id="transToastMsg"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    </x-admin.container>
@endsection

@push('modals')
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
    const TRANSLATE_URL = @json(route('admin.translations.translate'));
    const APPLY_URL     = @json(route('admin.translations.apply'));
    const SOURCE_LANG   = @json($sourceLang);
    const TARGET_LANG   = @json($targetLang);
    const CSRF          = document.querySelector('meta[name="csrf-token"]')?.content;

    const ITEMS = @json($items);

    // Auto-resize textareas to fit content
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
    document.querySelectorAll('.translation-input, .source-input').forEach(ta => {
        autoResize(ta);
        ta.addEventListener('input', () => autoResize(ta));
    });

    // Source text: show save button on change, save via apply endpoint
    document.querySelectorAll('.source-input').forEach(ta => {
        const idx = ta.dataset.index;
        const saveBtn = document.querySelector(`.btn-save-source[data-index="${idx}"]`);
        const originalValue = ta.value;

        ta.addEventListener('input', () => {
            if (saveBtn) saveBtn.style.display = ta.value !== originalValue ? '' : 'none';
        });
    });

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

    // Select all
    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        updateSelectedCount();
    });

    // Individual checkboxes
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    function updateSelectedCount() {
        const count = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('btnTranslateSelected').disabled = count === 0;
        document.getElementById('btnApplyAll').disabled = count === 0;
    }

    // Translate selected
    document.getElementById('btnTranslateSelected')?.addEventListener('click', async () => {
        const checked = getCheckedItems();
        if (!checked.length) return;

        const btn = document.getElementById('btnTranslateSelected');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Übersetze...';

        try {
            const res = await fetch(TRANSLATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    items: checked,
                    source_lang: SOURCE_LANG,
                    target_lang: TARGET_LANG,
                }),
            });
            const data = await res.json();

            if (data.error) throw new Error(data.error);

            // Fill in translated texts
            data.translations?.forEach(t => {
                const idx = checked.findIndex(c => c.type === t.type && c.id === t.id && c.field === t.field);
                if (idx !== -1) {
                    const cbIdx = document.querySelectorAll('.item-checkbox:checked')[idx]?.dataset.index;
                    const textarea = document.querySelector(`.translation-input[data-index="${cbIdx}"]`);
                    if (textarea) { textarea.value = t.text; autoResize(textarea); }
                }
            });

            showToast(`${data.translations?.length || 0} Felder übersetzt`, 'bg-success');
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#translate"></use></svg> Mit DeepL übersetzen (<span id="selectedCount">' + document.querySelectorAll('.item-checkbox:checked').length + '</span>)';
        }
    });

    // Single translate
    document.querySelectorAll('.btn-translate-single').forEach(btn => {
        btn.addEventListener('click', async () => {
            const idx = btn.dataset.index;
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
                        target_lang: TARGET_LANG,
                    }),
                });
                const data = await res.json();

                if (data.translations?.[0]) {
                    const textarea = document.querySelector(`.translation-input[data-index="${idx}"]`);
                    if (textarea) { textarea.value = data.translations[0].text; autoResize(textarea); }
                }
            } catch (e) {
                showToast('Fehler: ' + e.message, 'bg-danger');
            } finally {
                btn.disabled = false;
            }
        });
    });

    // Apply
    document.getElementById('btnApplyAll')?.addEventListener('click', async () => {
        const checked = getCheckedItems();
        if (!checked.length) return;

        const btn = document.getElementById('btnApplyAll');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Speichere...';

        // Collect edited texts
        const applyItems = [];
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            const idx = cb.dataset.index;
            const item = ITEMS[idx];
            const textarea = document.querySelector(`.translation-input[data-index="${idx}"]`);
            if (item && textarea) {
                applyItems.push({
                    type: item.type,
                    id: item.id,
                    field: item.field,
                    text: textarea.value,
                });
            }
        });

        try {
            const res = await fetch(APPLY_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ items: applyItems, target_lang: TARGET_LANG }),
            });
            const data = await res.json();

            if (data.error) throw new Error(data.error);

            showToast(`${applyItems.length} Übersetzungen gespeichert!`, 'bg-success');
            setTimeout(() => location.reload(), 1500);
        } catch (e) {
            showToast('Fehler: ' + e.message, 'bg-danger');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg class="bi" width="16" height="16" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#check2-all"></use></svg> Übernehmen';
        }
    });

    function getCheckedItems() {
        const items = [];
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            const idx = cb.dataset.index;
            const item = ITEMS[idx];
            if (item) items.push({ type: item.type, id: item.id, field: item.field });
        });
        return items;
    }

    function showToast(msg, bgClass) {
        const toast = document.getElementById('transToast');
        const msgEl = document.getElementById('transToastMsg');
        msgEl.textContent = msg;
        toast.className = 'toast align-items-center text-white border-0 ' + bgClass;
        new bootstrap.Toast(toast, { delay: 3000 }).show();
    }

    // Language publish/draft toggles
    const TOGGLE_BASE = @json(route('admin.language-settings.toggle', ['locale' => '__LOCALE__']));
    const langModal = new bootstrap.Modal(document.getElementById('langPublishModal'));
    const langModalText = document.getElementById('langPublishModalText');
    const langModalConfirm = document.getElementById('langPublishModalConfirm');
    let pendingToggleBtn = null;

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

            // Update confirm button color based on action
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

            // Update the dropdown button badge if it's the active language
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
