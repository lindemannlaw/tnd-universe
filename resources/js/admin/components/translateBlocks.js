/**
 * DeepL translation with review overlay + standalone SEO generation.
 *
 * Flow (Translate):
 *  1. Collect ALL EN text fields
 *  2. Identify changed fields (delta vs DB snapshot)
 *  3. Send ALL fields with content to DeepL
 *  4. Show review overlay: changed fields on top (pre-selected),
 *     unchanged fields below (deselected)
 *  5. Apply approved translations to DE fields
 *
 * Flow (SEO):
 *  1. Generate EN SEO via Claude
 *  2. Populate EN SEO fields
 */

const TEXT_ONLY_NAMES = new Set(['content', 'headline', 'link_text', 'link_url', 'subhead']);

const FIELD_LABELS = {
    title:             'Titel',
    short_description: 'Kurzbeschreibung',
    location:          'Standort',
    seo_title:         'SEO Titel',
    seo_description:   'SEO Beschreibung',
    seo_keywords:      'SEO Keywords',
    property_type:     'Immobilien-Typ',
    status:            'Status',
    year_built:        'Baujahr',
    content:           'Inhalt',
    headline:          'Headline',
    link_text:         'Link Text',
    link_url:          'Link URL',
    subhead:           'Subheadline',
};

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

export function translateBlocks() {
    // Translate buttons
    document.querySelectorAll('[data-translate-blocks]').forEach(button => {
        if (button._translateInited) return;
        button._translateInited = true;
        button.addEventListener('click', () => handleTranslate(button));

        // Seed snapshot from current (DB) values
        const form = getForm(button);
        if (form && !form._translationSnapshot) {
            const initial = new Map();
            collectTextItems(form, 'en').forEach(({ key, text }) => {
                if (hasContent(text)) initial.set(key, text);
            });
            form._translationSnapshot = initial;
        }
    });

    // SEO generate buttons (standalone)
    document.querySelectorAll('[data-generate-seo]').forEach(button => {
        if (button._seoInited) return;
        button._seoInited = true;
        button.addEventListener('click', () => handleGenerateSeo(button));
    });
}

// ---------------------------------------------------------------------------
// Form helper
// ---------------------------------------------------------------------------

function getForm(button) {
    return button.closest('form')
        ?? button.closest('.modal-content')?.querySelector('form');
}

// ---------------------------------------------------------------------------
// SEO generation handler
// ---------------------------------------------------------------------------

async function handleGenerateSeo(button) {
    const form = getForm(button);
    if (!form) return;

    const generateSeoUrl = button.dataset.generateSeoUrl;
    if (!generateSeoUrl) {
        console.error('[translateBlocks] data-generate-seo-url missing');
        return;
    }

    const titleSpan     = button.querySelector('span');
    const originalTitle = titleSpan?.textContent ?? '';
    button.disabled     = true;
    if (titleSpan) titleSpan.textContent = 'SEO erstellen…';

    try {
        await generateSeoFields(form, 'en', generateSeoUrl);
        flashButton(button, '✓ SEO erstellt', 2500, titleSpan, originalTitle);
    } catch (e) {
        console.error('[translateBlocks] SEO generation failed:', e);
        alert('SEO-Generierung fehlgeschlagen: ' + e.message);
        if (titleSpan) titleSpan.textContent = originalTitle;
        button.disabled = false;
    }
}

// ---------------------------------------------------------------------------
// Translation handler
// ---------------------------------------------------------------------------

async function handleTranslate(button) {
    const sourceLocale = 'en';
    const targetLocale = button.dataset.targetLocale || 'de';
    const translateUrl = button.dataset.translateUrl;

    if (!translateUrl) {
        console.error('[translateBlocks] data-translate-url missing on button');
        return;
    }

    const form = getForm(button);
    if (!form) return;

    const titleSpan     = button.querySelector('span');
    const originalTitle = titleSpan?.textContent ?? '';
    button.disabled     = true;
    if (titleSpan) titleSpan.textContent = 'Übersetze…';

    // Collect all EN text items and determine which ones changed
    const allItems   = collectTextItems(form, sourceLocale);
    const snapshot   = form._translationSnapshot ?? null;
    const itemsWithContent = allItems.filter(({ text }) => hasContent(text));

    if (itemsWithContent.length === 0) {
        flashButton(button, '✓ Keine Texte', 2000, titleSpan, originalTitle);
        return;
    }

    // Determine which items changed vs snapshot
    const changedKeys = new Set();
    itemsWithContent.forEach(({ key, text }) => {
        if (snapshot === null) {
            // No snapshot = first time, treat all as changed
            changedKeys.add(key);
        } else if (!snapshot.has(key)) {
            // New field
            changedKeys.add(key);
        } else if (snapshot.get(key) !== text) {
            // Changed field
            changedKeys.add(key);
        }
    });

    try {
        const csrfToken = document.querySelector('meta[name=csrf-token]')?.content ?? '';

        const response = await fetch(translateUrl, {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                source_lang: sourceLocale,
                target_lang: targetLocale,
                items: itemsWithContent.map(({ key, text, isHtml }) => ({ key, text, isHtml })),
            }),
        });

        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw new Error(err.error || `HTTP ${response.status}`);
        }

        const { translations = {} } = await response.json();

        if (titleSpan) titleSpan.textContent = originalTitle;

        const approved = await showReviewOverlay(translations, itemsWithContent, changedKeys);

        button.disabled = false;

        if (approved === null) return; // user cancelled

        // Apply approved translations to DE fields
        let count = 0;
        for (const [sourceKey, { text: translatedText, isHtml }] of Object.entries(approved)) {
            const targetKey   = sourceKey.replace(`[${sourceLocale}]`, `[${targetLocale}]`);
            const targetField = form.querySelector(`[name="${CSS.escape(targetKey)}"]`);
            if (!targetField) continue;

            targetField.value = translatedText;

            if (targetField._sunEditor) {
                targetField._sunEditor.setContents(translatedText);
            }

            targetField.dispatchEvent(new Event('input', { bubbles: true }));
            count++;
        }

        // Update snapshot with all items that were shown (regardless of approval)
        const newSnapshot = form._translationSnapshot ?? new Map();
        itemsWithContent.forEach(({ key, text }) => {
            newSnapshot.set(key, text);
        });
        form._translationSnapshot = newSnapshot;

        flashButton(button, `✓ ${count} Feld${count !== 1 ? 'er' : ''} übernommen`, 2500, titleSpan, originalTitle);

    } catch (error) {
        console.error('[translateBlocks] Translation failed:', error);
        alert('Übersetzung fehlgeschlagen: ' + error.message);
        if (titleSpan) titleSpan.textContent = originalTitle;
        button.disabled = false;
    }
}

// ---------------------------------------------------------------------------
// Review overlay
// ---------------------------------------------------------------------------

function showReviewOverlay(translations, allItems, changedKeys) {
    return new Promise(resolve => {
        let settled = false;

        function finish(result) {
            if (settled) return;
            settled = true;
            document.removeEventListener('keydown', onKeydown);
            overlay.remove();
            resolve(result);
        }

        const onKeydown = e => { if (e.key === 'Escape') finish(null); };
        document.addEventListener('keydown', onKeydown);

        const overlay = buildOverlayEl(translations, allItems, changedKeys);
        document.body.appendChild(overlay);

        overlay.addEventListener('click', e => { if (e.target === overlay) finish(null); });
        overlay.querySelector('#tro-cancel').addEventListener('click', () => finish(null));
        overlay.querySelector('#tro-apply').addEventListener('click', () => {
            const result = {};
            overlay.querySelectorAll('[data-tro-item]').forEach(item => {
                const cb = item.querySelector('[data-tro-checkbox]');
                if (!cb?.checked) return;
                const editor = item.querySelector('.tro-editor');
                if (!editor) return;
                const key    = editor.dataset.key;
                const isHtml = editor.dataset.isHtml === 'true';
                result[key]  = { text: isHtml ? editor.innerHTML : editor.value, isHtml };
            });
            finish(result);
        });
    });
}

function buildOverlayEl(translations, allItems, changedKeys) {
    const overlay = document.createElement('div');
    overlay.style.cssText = [
        'position:fixed;inset:0;z-index:10050;',
        'display:flex;align-items:center;justify-content:center;',
        'background:rgba(0,0,0,0.55);padding:1rem;',
    ].join('');

    // Sort: changed items first, then unchanged
    const sortedItems = [...allItems].sort((a, b) => {
        const aChanged = changedKeys.has(a.key) ? 0 : 1;
        const bChanged = changedKeys.has(b.key) ? 0 : 1;
        return aChanged - bChanged;
    });

    const changedCount = [...changedKeys].length;

    const itemsHtml = sortedItems.map(({ key, text: sourceText, isHtml }) => {
        const translated    = translations[key] ?? '';
        const label         = getLabelFromKey(key);
        const isChanged     = changedKeys.has(key);
        const sourcePreview = sourceText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        const badgeHtml     = isChanged
            ? '<span class="badge bg-warning text-dark ms-2">Geändert</span>'
            : '';

        const editorHtml = isHtml
            ? `<div
                    contenteditable="true"
                    class="form-control form-control-sm tro-editor"
                    style="min-height:64px;max-height:200px;overflow-y:auto;white-space:pre-wrap;"
                    data-key="${escAttr(key)}"
                    data-is-html="true"
                >${translated}</div>`
            : `<textarea
                    class="form-control form-control-sm tro-editor"
                    rows="${translated.length > 140 ? 4 : 2}"
                    data-key="${escAttr(key)}"
                    data-is-html="false"
                >${escHtml(translated)}</textarea>`;

        return `
            <div class="border rounded p-3 d-flex flex-column gap-2 ${isChanged ? 'border-warning' : ''}" data-tro-item>
                <div class="d-flex align-items-center gap-2">
                    <input class="form-check-input flex-shrink-0 mt-0" type="checkbox" ${isChanged ? 'checked' : ''} data-tro-checkbox>
                    <span class="fw-semibold small text-uppercase">${escHtml(label)}</span>
                    ${badgeHtml}
                </div>
                <div class="d-flex align-items-start gap-2">
                    <span class="flex-shrink-0" style="font-size:1rem;line-height:1.4;" title="English">🇬🇧</span>
                    <div class="small text-muted fst-italic border-start border-2 border-secondary-subtle ps-2 flex-grow-1"
                         style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                        ${escHtml(sourcePreview)}
                    </div>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <span class="flex-shrink-0" style="font-size:1rem;line-height:1.8;" title="Deutsch">🇩🇪</span>
                    <div class="flex-grow-1">${editorHtml}</div>
                </div>
            </div>`;
    }).join('');

    // Separator between changed and unchanged sections
    const separatorIndex = changedCount;

    overlay.innerHTML = `
        <div class="bg-white rounded-3 shadow-lg d-flex flex-column"
             style="width:min(800px,96vw);max-height:90vh;">

            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom flex-shrink-0">
                <h5 class="mb-0 me-auto fw-semibold">Übersetzungen prüfen</h5>
                <label class="d-flex align-items-center gap-2 mb-0 small user-select-none" style="cursor:pointer;">
                    <input class="form-check-input mt-0" type="checkbox" id="tro-select-all">
                    Alle auswählen
                </label>
            </div>

            <div class="overflow-y-auto flex-grow-1 px-4 py-3 d-flex flex-column gap-3">
                ${changedCount > 0 && changedCount < allItems.length
                    ? `<div class="small fw-semibold text-warning-emphasis">${changedCount} geänderte Felder</div>`
                    : ''}
                ${itemsHtml}
                ${changedCount > 0 && changedCount < allItems.length
                    ? '' // separator is visual via border-warning on changed items
                    : ''}
            </div>

            <div class="d-flex align-items-center justify-content-between gap-2 px-4 py-3 border-top flex-shrink-0">
                <span class="small text-muted" id="tro-count"></span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="tro-cancel">Abbrechen</button>
                    <button type="button" class="btn btn-dark btn-sm" id="tro-apply">Übernehmen</button>
                </div>
            </div>
        </div>`;

    // Select-All + count logic
    const selectAllEl = overlay.querySelector('#tro-select-all');
    const countEl     = overlay.querySelector('#tro-count');
    const applyBtn    = overlay.querySelector('#tro-apply');

    const updateState = () => {
        const all     = [...overlay.querySelectorAll('[data-tro-checkbox]')];
        const checked = all.filter(c => c.checked).length;
        countEl.textContent          = `${checked} von ${allItems.length} ausgewählt`;
        selectAllEl.checked          = checked === all.length;
        selectAllEl.indeterminate    = checked > 0 && checked < all.length;
        applyBtn.disabled            = checked === 0;
    };

    selectAllEl.addEventListener('change', () => {
        overlay.querySelectorAll('[data-tro-checkbox]').forEach(cb => { cb.checked = selectAllEl.checked; });
        updateState();
    });

    overlay.querySelectorAll('[data-tro-checkbox]').forEach(cb => {
        cb.addEventListener('change', updateState);
    });

    updateState();
    return overlay;
}

// ---------------------------------------------------------------------------
// Label helper
// ---------------------------------------------------------------------------

function getLabelFromKey(key) {
    const db = key.match(/description_blocks\[en\]\[(\d+)\](?:\[items\]\[(\d+)\])?\[(\w+)\]/);
    if (db) {
        const block = parseInt(db[1]) + 1;
        const item  = db[2] !== undefined ? parseInt(db[2]) + 1 : null;
        const field = FIELD_LABELS[db[3]] ?? humanize(db[3]);
        return item ? `Block ${block} · Spalte ${item} · ${field}` : `Block ${block} · ${field}`;
    }

    const pd = key.match(/property_details\[en\]\[(\w+)\]/);
    if (pd) return `Property Details · ${FIELD_LABELS[pd[1]] ?? humanize(pd[1])}`;

    const sf = key.match(/^(\w+)\[en\]/);
    if (sf) return FIELD_LABELS[sf[1]] ?? humanize(sf[1]);

    return key;
}

function humanize(str) {
    return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

// ---------------------------------------------------------------------------
// SEO generation (Claude/Anthropic)
// ---------------------------------------------------------------------------

async function generateSeoFields(form, locale, generateSeoUrl) {
    const get = name => {
        const el = form.querySelector(`[name="${CSS.escape(name)}"]`);
        return el ? stripHtml(el.value).trim() : '';
    };
    const set = (name, value) => {
        if (!value) return;
        const el = form.querySelector(`[name="${CSS.escape(name)}"]`);
        if (!el) return;
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const context = {
        title:             get(`title[${locale}]`),
        short_description: get(`short_description[${locale}]`),
        location:          get(`location[${locale}]`),
        property_type:     get(`property_details[${locale}][property_type]`),
        area:              get('area'),
    };

    if (!context.title && !context.short_description) return;

    const csrfToken = document.querySelector('meta[name=csrf-token]')?.content ?? '';

    const response = await fetch(generateSeoUrl, {
        method: 'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ locale, context }),
    });

    if (!response.ok) {
        const err = await response.json().catch(() => ({}));
        throw new Error(err.error || `SEO generation failed: HTTP ${response.status}`);
    }

    const { seo_title, seo_description, seo_keywords } = await response.json();

    set(`seo_title[${locale}]`,       seo_title);
    set(`seo_description[${locale}]`, seo_description);
    set(`seo_keywords[${locale}]`,    seo_keywords);
}

function stripHtml(str) {
    return String(str ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

// ---------------------------------------------------------------------------
// Field collection
// ---------------------------------------------------------------------------

function collectTextItems(form, sourceLocale) {
    const items = [];
    form.querySelectorAll('[name]').forEach(field => {
        if (field.disabled) return;
        if (field.type === 'file'     || field.type === 'hidden' ||
            field.type === 'checkbox' || field.type === 'radio'  ||
            field.tagName === 'SELECT') return;

        const name = field.getAttribute('name');
        if (!name || !name.includes(`[${sourceLocale}]`)) return;

        if (name.startsWith('description_blocks[')) {
            const match = name.match(/\[(\w+)\]$/);
            if (!match || !TEXT_ONLY_NAMES.has(match[1])) return;
        }

        // For WYSIWYG fields, get content from SunEditor if available
        const value = field._sunEditor ? field._sunEditor.getContents() : (field.value ?? '');

        items.push({ key: name, text: value, isHtml: field.hasAttribute('data-wysiwyg') });
    });
    return items;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function flashButton(button, label, duration, titleSpan, originalTitle) {
    const span = titleSpan ?? button.querySelector('span');
    const orig = originalTitle ?? span?.textContent ?? '';
    if (span) span.textContent = label;
    setTimeout(() => { if (span) span.textContent = orig; button.disabled = false; }, duration);
}

function hasContent(value) {
    if (!value) return false;
    return value.replace(/<[^>]*>/g, '').trim().length > 0;
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escAttr(str) {
    return String(str ?? '').replace(/"/g, '&quot;');
}
