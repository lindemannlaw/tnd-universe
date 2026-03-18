/**
 * DeepL translation with review overlay.
 *
 * Flow:
 *  1. Collect changed EN fields (delta strategy)
 *  2. POST to DeepL endpoint
 *  3. Show review overlay: approve/deselect/edit each translation
 *  4. Apply approved translations to DE fields
 *  5. Update snapshot (delta state)
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
    document.querySelectorAll('[data-translate-blocks]').forEach(button => {
        if (button._translateInited) return;
        button._translateInited = true;
        button.addEventListener('click', () => handleTranslate(button));
    });
}

// ---------------------------------------------------------------------------
// Main handler
// ---------------------------------------------------------------------------

async function handleTranslate(button) {
    const sourceLocale = 'en';
    const targetLocale = button.dataset.targetLocale || 'de';
    const translateUrl = button.dataset.translateUrl;

    if (!translateUrl) {
        console.error('[translateBlocks] data-translate-url missing on button');
        return;
    }

    // Button may sit in modal-header (sibling of form, not ancestor)
    const form = button.closest('form')
        ?? button.closest('.modal-content')?.querySelector('form');
    if (!form) return;

    // Grab UI refs early (needed for loading states below)
    const titleSpan     = button.querySelector('span');
    const originalTitle = titleSpan?.textContent ?? '';
    button.disabled     = true;

    // Generate / refresh EN SEO fields via OpenAI before translation
    const generateSeoUrl = button.dataset.generateSeoUrl ?? null;
    if (generateSeoUrl) {
        if (titleSpan) titleSpan.textContent = 'SEO erstellen…';
        await generateSeoFields(form, sourceLocale, generateSeoUrl);
        if (titleSpan) titleSpan.textContent = 'Übersetze…';
    }

    const allItems   = collectTextItems(form, sourceLocale);
    const snapshot   = form._translationSnapshot ?? null;
    const deltaItems = getDeltaItems(allItems, snapshot);

    if (deltaItems.length === 0) {
        flashButton(button, '✓ Kein Update nötig', 2000, titleSpan, originalTitle);
        return;
    }

    if (titleSpan) titleSpan.textContent = 'Übersetze…';

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
                items: deltaItems.map(({ key, text, isHtml }) => ({ key, text, isHtml })),
            }),
        });

        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            throw new Error(err.error || `HTTP ${response.status}`);
        }

        const { translations = {} } = await response.json();

        // Restore button label while the review overlay is shown
        if (titleSpan) titleSpan.textContent = originalTitle;
        // Keep button disabled until overlay is closed

        const approved = await showReviewOverlay(translations, deltaItems);

        button.disabled = false;

        if (approved === null) return; // user cancelled

        // Apply approved (and potentially edited) translations to DE fields
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

        // Update snapshot: record the EN values that were approved
        const newSnapshot = form._translationSnapshot ?? new Map();
        const approvedKeys = new Set(Object.keys(approved));
        deltaItems.forEach(({ key, text }) => {
            if (approvedKeys.has(key)) newSnapshot.set(key, text);
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

/**
 * Show a full-screen review overlay.
 * @returns {Promise<Record<string, {text: string, isHtml: boolean}> | null>}
 *   Resolves with approved translations keyed by sourceKey, or null on cancel.
 */
function showReviewOverlay(translations, deltaItems) {
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

        const overlay = buildOverlayEl(translations, deltaItems);
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

function buildOverlayEl(translations, deltaItems) {
    const overlay = document.createElement('div');
    overlay.style.cssText = [
        'position:fixed;inset:0;z-index:10050;',
        'display:flex;align-items:center;justify-content:center;',
        'background:rgba(0,0,0,0.55);padding:1rem;',
    ].join('');

    // Build each translation item
    const itemsHtml = deltaItems.map(({ key, text: sourceText, isHtml }) => {
        const translated    = translations[key] ?? '';
        const label         = getLabelFromKey(key);
        const sourcePreview = sourceText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

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
            <div class="border rounded p-3 d-flex flex-column gap-2" data-tro-item>
                <div class="d-flex align-items-center gap-2">
                    <input class="form-check-input flex-shrink-0 mt-0" type="checkbox" checked data-tro-checkbox>
                    <span class="fw-semibold small text-uppercase">${escHtml(label)}</span>
                </div>
                <div class="small text-muted fst-italic border-start border-2 border-secondary-subtle ps-2"
                     style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                    ${escHtml(sourcePreview)}
                </div>
                ${editorHtml}
            </div>`;
    }).join('');

    overlay.innerHTML = `
        <div class="bg-white rounded-3 shadow-lg d-flex flex-column"
             style="width:min(800px,96vw);max-height:90vh;">

            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom flex-shrink-0">
                <h5 class="mb-0 me-auto fw-semibold">Übersetzungen prüfen</h5>
                <label class="d-flex align-items-center gap-2 mb-0 small user-select-none" style="cursor:pointer;">
                    <input class="form-check-input mt-0" type="checkbox" id="tro-select-all" checked>
                    Alle auswählen
                </label>
            </div>

            <div class="overflow-y-auto flex-grow-1 px-4 py-3 d-flex flex-column gap-3">
                ${itemsHtml}
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
        countEl.textContent          = `${checked} von ${deltaItems.length} ausgewählt`;
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
    // description_blocks[en][0][items][1][headline]
    // description_blocks[en][0][content]
    const db = key.match(/description_blocks\[en\]\[(\d+)\](?:\[items\]\[(\d+)\])?\[(\w+)\]/);
    if (db) {
        const block = parseInt(db[1]) + 1;
        const item  = db[2] !== undefined ? parseInt(db[2]) + 1 : null;
        const field = FIELD_LABELS[db[3]] ?? humanize(db[3]);
        return item ? `Block ${block} · Spalte ${item} · ${field}` : `Block ${block} · ${field}`;
    }

    // property_details[en][property_type]
    const pd = key.match(/property_details\[en\]\[(\w+)\]/);
    if (pd) return `Property Details · ${FIELD_LABELS[pd[1]] ?? humanize(pd[1])}`;

    // title[en], short_description[en], …
    const sf = key.match(/^(\w+)\[en\]/);
    if (sf) return FIELD_LABELS[sf[1]] ?? humanize(sf[1]);

    return key;
}

function humanize(str) {
    return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

// ---------------------------------------------------------------------------
// SEO generation (OpenAI)
// ---------------------------------------------------------------------------

/**
 * Call the generate-seo endpoint and populate EN SEO fields with the result.
 * Always overwrites existing SEO fields so the user gets fresh AI-generated copy.
 * Errors are logged but never block the translate flow.
 */
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

    // Skip if there's not enough data to generate meaningful SEO
    if (!context.title && !context.short_description) return;

    try {
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
            console.warn('[translateBlocks] SEO generation failed:', response.status);
            return;
        }

        const { seo_title, seo_description, seo_keywords } = await response.json();

        set(`seo_title[${locale}]`,       seo_title);
        set(`seo_description[${locale}]`, seo_description);
        set(`seo_keywords[${locale}]`,    seo_keywords);

    } catch (e) {
        console.warn('[translateBlocks] SEO generation error:', e.message);
        // Non-fatal – translation continues without generated SEO
    }
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
        if (field.type === 'file'     || field.type === 'hidden' ||
            field.type === 'checkbox' || field.type === 'radio'  ||
            field.tagName === 'SELECT') return;

        const name = field.getAttribute('name');
        if (!name || !name.includes(`[${sourceLocale}]`)) return;

        if (name.startsWith('description_blocks[')) {
            const match = name.match(/\[(\w+)\]$/);
            if (!match || !TEXT_ONLY_NAMES.has(match[1])) return;
        }

        items.push({ key: name, text: field.value ?? '', isHtml: field.hasAttribute('data-wysiwyg') });
    });
    return items;
}

// ---------------------------------------------------------------------------
// Delta
// ---------------------------------------------------------------------------

function getDeltaItems(items, snapshot) {
    if (snapshot === null) return items.filter(({ text }) => hasContent(text));
    return items.filter(({ key, text }) => {
        if (!snapshot.has(key)) return hasContent(text);
        return snapshot.get(key) !== text && hasContent(text);
    });
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
