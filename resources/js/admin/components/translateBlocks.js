/**
 * DeepL translation with timestamp-based review overlay + standalone SEO generation.
 *
 * Timestamps are stored server-side per field:
 *   { "title": { "en_changed_at": "ISO", "de_translated_at": "ISO" }, ... }
 *
 * A field "needs translation" when en_changed_at > de_translated_at (or de_translated_at missing).
 * Projects without timestamps = all fields are considered in-sync (unchecked).
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

    document.querySelectorAll('[data-generate-seo]').forEach(button => {
        if (button._seoInited) return;
        button._seoInited = true;
        button.addEventListener('click', () => handleGenerateSeo(button));
    });
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function getForm(button) {
    return button.closest('form')
        ?? button.closest('.modal-content')?.querySelector('form');
}

function getTimestamps(button) {
    try { return JSON.parse(button.dataset.textTimestamps || '{}'); }
    catch { return {}; }
}

function getUpdateTimestampsUrl(button) {
    return button.dataset.updateTimestampsUrl || '';
}

function csrfToken() {
    return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

/**
 * Convert form field name to normalized timestamp key.
 * "title[en]" → "title"
 * "description_blocks[en][1][items][0][content]" → "description_blocks.1.items.0.content"
 * "property_details[en][property_type]" → "property_details.property_type"
 */
function formNameToTimestampKey(name) {
    return name
        .replace(/\[en\]/, '')        // remove locale
        .replace(/\[de\]/, '')
        .replace(/^\[/, '')
        .replace(/\[/g, '.')          // [ → .
        .replace(/\]/g, '');          // remove ]
}

/**
 * Check if a field needs translation based on timestamps.
 * Returns true if en_changed_at > de_translated_at or de_translated_at is missing.
 */
function needsTranslation(timestamps, tsKey) {
    const entry = timestamps[tsKey];
    if (!entry || !entry.en_changed_at) return false; // no timestamp = in-sync
    if (!entry.de_translated_at) return true;          // never translated
    return entry.en_changed_at > entry.de_translated_at;
}

function getEnChangedAt(timestamps, tsKey) {
    return timestamps[tsKey]?.en_changed_at ?? null;
}

// ---------------------------------------------------------------------------
// Translation handler
// ---------------------------------------------------------------------------

async function handleTranslate(button) {
    const sourceLocale = 'en';
    const targetLocale = button.dataset.targetLocale || 'de';
    const translateUrl = button.dataset.translateUrl;
    const timestamps   = getTimestamps(button);
    const updateTsUrl  = getUpdateTimestampsUrl(button);

    if (!translateUrl) return;

    const form = getForm(button);
    if (!form) return;

    const titleSpan     = button.querySelector('span');
    const originalTitle = titleSpan?.textContent ?? '';
    button.disabled     = true;
    if (titleSpan) titleSpan.textContent = 'Übersetze…';

    const allItems         = collectTextItems(form, sourceLocale);
    const itemsWithContent = allItems.filter(({ text }) => hasContent(text));

    if (itemsWithContent.length === 0) {
        flashButton(button, '✓ Keine Texte', 2000, titleSpan, originalTitle);
        return;
    }

    // Determine which items need translation (timestamp-based)
    const changedKeys = new Set();
    itemsWithContent.forEach(({ key }) => {
        const tsKey = formNameToTimestampKey(key);
        if (needsTranslation(timestamps, tsKey)) changedKeys.add(key);
    });

    try {
        const response = await fetch(translateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
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

        const approved = await showReviewOverlay(translations, itemsWithContent, changedKeys, timestamps);

        button.disabled = false;
        if (approved === null) return; // cancelled

        // Apply approved translations to DE fields
        let count = 0;
        const appliedTsKeys = [];
        for (const [sourceKey, { text: translatedText, isHtml }] of Object.entries(approved)) {
            const targetKey   = sourceKey.replace(`[${sourceLocale}]`, `[${targetLocale}]`);
            const targetField = form.querySelector(`[name="${CSS.escape(targetKey)}"]`);
            if (!targetField) continue;

            targetField.value = translatedText;
            if (targetField._sunEditor) targetField._sunEditor.setContents(translatedText);
            targetField.dispatchEvent(new Event('input', { bubbles: true }));
            count++;
            appliedTsKeys.push(formNameToTimestampKey(sourceKey));
        }

        // Update server-side timestamps
        if (updateTsUrl && appliedTsKeys.length > 0) {
            postTimestampUpdate(updateTsUrl, 'translation', appliedTsKeys);
            // Update local timestamps so re-opening dialog reflects the change
            const now = new Date().toISOString();
            appliedTsKeys.forEach(k => {
                timestamps[k] = { ...(timestamps[k] ?? {}), de_translated_at: now };
            });
            button.dataset.textTimestamps = JSON.stringify(timestamps);
        }

        flashButton(button, `✓ ${count} Feld${count !== 1 ? 'er' : ''} übernommen`, 2500, titleSpan, originalTitle);

    } catch (error) {
        console.error('[translateBlocks] Translation failed:', error);
        alert('Übersetzung fehlgeschlagen: ' + error.message);
        if (titleSpan) titleSpan.textContent = originalTitle;
        button.disabled = false;
    }
}

// ---------------------------------------------------------------------------
// SEO generation handler
// ---------------------------------------------------------------------------

async function handleGenerateSeo(button) {
    const form = getForm(button);
    if (!form) return;

    const generateSeoUrl = button.dataset.generateSeoUrl;
    const updateTsUrl    = getUpdateTimestampsUrl(button);
    if (!generateSeoUrl) return;

    const titleSpan     = button.querySelector('span');
    const originalTitle = titleSpan?.textContent ?? '';
    button.disabled     = true;
    if (titleSpan) titleSpan.textContent = 'SEO erstellen…';

    try {
        await generateSeoFields(form, 'en', generateSeoUrl);

        // Mark SEO fields as generated → triggers "needs translation" on next translate
        if (updateTsUrl) {
            postTimestampUpdate(updateTsUrl, 'seo', ['seo_title', 'seo_description', 'seo_keywords']);
        }

        // Also update the translate button's timestamps if present
        const translateBtn = form.closest('.modal-content')?.querySelector('[data-translate-blocks]');
        if (translateBtn) {
            const ts = getTimestamps(translateBtn);
            const now = new Date().toISOString();
            ['seo_title', 'seo_description', 'seo_keywords'].forEach(k => {
                ts[k] = { ...(ts[k] ?? {}), en_changed_at: now };
            });
            translateBtn.dataset.textTimestamps = JSON.stringify(ts);
        }

        flashButton(button, '✓ SEO erstellt', 2500, titleSpan, originalTitle);
    } catch (e) {
        console.error('[translateBlocks] SEO generation failed:', e);
        alert('SEO-Generierung fehlgeschlagen: ' + e.message);
        if (titleSpan) titleSpan.textContent = originalTitle;
        button.disabled = false;
    }
}

// ---------------------------------------------------------------------------
// Timestamp update POST (fire-and-forget)
// ---------------------------------------------------------------------------

function postTimestampUpdate(url, type, keys) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ type, keys }),
    }).catch(err => console.warn('[translateBlocks] timestamp update failed:', err));
}

// ---------------------------------------------------------------------------
// Review overlay
// ---------------------------------------------------------------------------

function showReviewOverlay(translations, allItems, changedKeys, timestamps) {
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

        const overlay = buildOverlayEl(translations, allItems, changedKeys, timestamps);
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

function buildOverlayEl(translations, allItems, changedKeys, timestamps) {
    const overlay = document.createElement('div');
    overlay.style.cssText = [
        'position:fixed;inset:0;z-index:10050;',
        'display:flex;align-items:center;justify-content:center;',
        'background:rgba(0,0,0,0.55);padding:1rem;',
    ].join('');

    // Sort: changed items first (by en_changed_at DESC), then unchanged
    const sortedItems = [...allItems].sort((a, b) => {
        const aChanged = changedKeys.has(a.key) ? 0 : 1;
        const bChanged = changedKeys.has(b.key) ? 0 : 1;
        if (aChanged !== bChanged) return aChanged - bChanged;
        // Within changed: sort by en_changed_at DESC
        if (aChanged === 0) {
            const aTs = getEnChangedAt(timestamps, formNameToTimestampKey(a.key)) ?? '';
            const bTs = getEnChangedAt(timestamps, formNameToTimestampKey(b.key)) ?? '';
            return bTs.localeCompare(aTs); // DESC
        }
        return 0;
    });

    const changedCount = changedKeys.size;

    const itemsHtml = sortedItems.map(({ key, text: sourceText, isHtml }) => {
        const translated    = translations[key] ?? '';
        const label         = getLabelFromKey(key);
        const isChanged     = changedKeys.has(key);
        const sourceClean   = sourceText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

        // Diff analysis
        const tsKey     = formNameToTimestampKey(key);
        const rawOldTxt = (isChanged && timestamps[tsKey]?.en_old_text) || '';
        const oldClean  = rawOldTxt.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

        const hasTextDiff   = isChanged && oldClean && oldClean !== sourceClean;
        const hasFormatDiff = isChanged && rawOldTxt && !hasTextDiff && rawOldTxt !== sourceText;

        // Badges
        let badgeHtml = '';
        if (hasTextDiff)        badgeHtml = '<span class="badge bg-warning text-dark ms-2">Text ge\u00E4ndert</span>';
        else if (hasFormatDiff) badgeHtml = '<span class="badge bg-info text-dark ms-2">Formatierung ge\u00E4ndert</span>';
        else if (isChanged)     badgeHtml = '<span class="badge bg-warning text-dark ms-2">Ge\u00E4ndert</span>';

        // Source display: render HTML so formatting is visible (bold = bold, etc.)
        // For the diff view: show text diff below the rendered HTML
        const renderedSource = isHtml ? sanitizeHtml(sourceText) : escHtml(sourceText);
        const diffBlockHtml  = hasTextDiff
            ? `<div class="small mt-1 p-2 rounded" style="background:#fff8ed;border:1px dashed #f59e0b;">
                    <span class="text-muted" style="font-size:0.7rem;">\u00C4nderungen:</span><br>
                    ${highlightDiff(oldClean, sourceClean)}
               </div>`
            : hasFormatDiff
                ? `<div class="small mt-1 p-2 rounded" style="background:#ecfeff;border:1px dashed #06b6d4;">
                        <span class="text-muted" style="font-size:0.7rem;">Formatierung ge\u00E4ndert:</span><br>
                        ${highlightHtmlDiff(rawOldTxt, sourceText)}
                   </div>`
                : '';

        const editorHtml = isHtml
            ? `<div contenteditable="true"
                    class="form-control form-control-sm tro-editor"
                    style="min-height:64px;max-height:200px;overflow-y:auto;white-space:pre-wrap;"
                    data-key="${escAttr(key)}" data-is-html="true"
                >${translated}</div>`
            : `<textarea class="form-control form-control-sm tro-editor"
                    rows="${translated.length > 140 ? 4 : 2}"
                    data-key="${escAttr(key)}" data-is-html="false"
                >${escHtml(translated)}</textarea>`;

        return `
            <div class="border rounded p-3 d-flex flex-column gap-2 ${isChanged ? 'border-warning' : ''}" data-tro-item>
                <div class="d-flex align-items-center gap-2">
                    <input class="form-check-input flex-shrink-0 mt-0" type="checkbox" ${isChanged ? 'checked' : ''} data-tro-checkbox>
                    <span class="fw-semibold small text-uppercase">${escHtml(label)}</span>
                    ${badgeHtml}
                </div>
                <div class="d-flex align-items-start gap-2">
                    <span class="flex-shrink-0" style="font-size:1rem;line-height:1.4;" title="English">\u{1F1EC}\u{1F1E7}</span>
                    <div class="small text-muted border-start border-2 border-secondary-subtle ps-2 flex-grow-1"
                         style="white-space:pre-wrap;">
                        ${renderedSource}
                        ${diffBlockHtml}
                    </div>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <span class="flex-shrink-0" style="font-size:1rem;line-height:1.8;" title="Deutsch">\u{1F1E9}\u{1F1EA}</span>
                    <div class="flex-grow-1">${editorHtml}</div>
                </div>
            </div>`;
    }).join('');

    overlay.innerHTML = `
        <div class="bg-white rounded-3 shadow-lg d-flex flex-column"
             style="width:min(800px,96vw);max-height:90vh;">
            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom flex-shrink-0">
                <h5 class="mb-0 me-auto fw-semibold">\u{1F310} \u00DCbersetzungen pr\u00FCfen</h5>
                <label class="d-flex align-items-center gap-2 mb-0 small user-select-none" style="cursor:pointer;">
                    <input class="form-check-input mt-0" type="checkbox" id="tro-select-all">
                    Alle ausw\u00E4hlen
                </label>
            </div>
            <div class="overflow-y-auto flex-grow-1 px-4 py-3 d-flex flex-column gap-3">
                ${changedCount > 0
                    ? `<div class="small fw-semibold text-warning-emphasis">${changedCount} Feld${changedCount !== 1 ? 'er' : ''} ge\u00E4ndert seit letzter \u00DCbersetzung</div>`
                    : `<div class="small text-muted">Keine \u00C4nderungen seit letzter \u00DCbersetzung erkannt</div>`}
                ${itemsHtml}
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 px-4 py-3 border-top flex-shrink-0">
                <span class="small text-muted" id="tro-count"></span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="tro-cancel">Abbrechen</button>
                    <button type="button" class="btn btn-dark btn-sm" id="tro-apply">\u00DCbernehmen</button>
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
        countEl.textContent       = `${checked} von ${allItems.length} ausgew\u00E4hlt`;
        selectAllEl.checked       = checked === all.length;
        selectAllEl.indeterminate = checked > 0 && checked < all.length;
        applyBtn.disabled         = checked === 0;
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
        return item ? `Block ${block} \u00B7 Spalte ${item} \u00B7 ${field}` : `Block ${block} \u00B7 ${field}`;
    }

    const pd = key.match(/property_details\[en\]\[(\w+)\]/);
    if (pd) return `Property Details \u00B7 ${FIELD_LABELS[pd[1]] ?? humanize(pd[1])}`;

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

    const response = await fetch(generateSeoUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
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

/**
 * Sanitize HTML: allow only safe formatting tags, escape everything else.
 */
function sanitizeHtml(html) {
    const ALLOWED = /^(b|strong|i|em|u|s|strike|br|p|ul|ol|li|a|blockquote|h[1-6]|span|sub|sup)$/i;
    // Use DOMParser for safe parsing
    const doc = new DOMParser().parseFromString(html, 'text/html');
    return serializeNode(doc.body);
}

function serializeNode(node) {
    if (node.nodeType === Node.TEXT_NODE) return escHtml(node.textContent);
    if (node.nodeType !== Node.ELEMENT_NODE) return '';

    const tag = node.tagName.toLowerCase();
    const ALLOWED = /^(b|strong|i|em|u|s|strike|br|p|ul|ol|li|a|blockquote|h[1-6]|span|sub|sup)$/;
    if (!ALLOWED.test(tag)) {
        // Not allowed tag: render children only
        return Array.from(node.childNodes).map(serializeNode).join('');
    }

    const inner = Array.from(node.childNodes).map(serializeNode).join('');
    if (tag === 'br') return '<br>';
    // For links, keep href
    if (tag === 'a') {
        const href = node.getAttribute('href') || '';
        return `<a href="${escAttr(href)}" style="color:inherit;text-decoration:underline;">${inner}</a>`;
    }
    return `<${tag}>${inner}</${tag}>`;
}

/**
 * Show formatting differences between old and new HTML.
 * Highlights added/removed HTML tags inline.
 */
function highlightHtmlDiff(oldHtml, newHtml) {
    // Tokenize into text and tag segments
    const oldTokens = tokenizeHtml(oldHtml);
    const newTokens = tokenizeHtml(newHtml);

    const result = [];
    const maxLen = Math.max(oldTokens.length, newTokens.length);

    // Simple token-by-token comparison
    let oi = 0, ni = 0;
    while (oi < oldTokens.length || ni < newTokens.length) {
        const oldT = oldTokens[oi];
        const newT = newTokens[ni];

        if (oi >= oldTokens.length) {
            // Added in new
            result.push(formatToken(newTokens[ni], 'added'));
            ni++;
        } else if (ni >= newTokens.length) {
            // Removed from old
            result.push(formatToken(oldTokens[oi], 'removed'));
            oi++;
        } else if (oldT.text === newT.text && oldT.isTag === newT.isTag) {
            // Same
            result.push(formatToken(newT, 'same'));
            oi++; ni++;
        } else {
            // Different — try to find match ahead
            let foundNew = -1;
            for (let j = ni + 1; j < Math.min(ni + 5, newTokens.length); j++) {
                if (oldT.text === newTokens[j].text && oldT.isTag === newTokens[j].isTag) {
                    foundNew = j; break;
                }
            }
            if (foundNew >= 0) {
                // New tokens were added before the match
                for (let j = ni; j < foundNew; j++) {
                    result.push(formatToken(newTokens[j], 'added'));
                }
                ni = foundNew;
            } else {
                // Token changed or removed
                result.push(formatToken(oldT, 'removed'));
                result.push(formatToken(newT, 'added'));
                oi++; ni++;
            }
        }
    }

    return result.join('');
}

function tokenizeHtml(html) {
    const tokens = [];
    const re = /(<[^>]+>)|([^<]+)/g;
    let m;
    while ((m = re.exec(html)) !== null) {
        if (m[1]) tokens.push({ text: m[1], isTag: true });
        else if (m[2]) tokens.push({ text: m[2], isTag: false });
    }
    return tokens;
}

function formatToken(token, type) {
    if (type === 'same') {
        return token.isTag
            ? `<code style="font-size:0.7rem;color:#6b7280;">${escHtml(token.text)}</code>`
            : escHtml(token.text);
    }
    if (type === 'added') {
        const style = 'background:#fef3c7;color:#92400e;border-radius:2px;padding:0 1px;';
        return token.isTag
            ? `<code style="font-size:0.7rem;${style}">+ ${escHtml(token.text)}</code>`
            : `<span style="${style}">${escHtml(token.text)}</span>`;
    }
    // removed
    const style = 'color:#b45309;text-decoration:line-through;opacity:0.6;';
    return token.isTag
        ? `<code style="font-size:0.7rem;${style}">\u2212 ${escHtml(token.text)}</code>`
        : `<span style="${style}">${escHtml(token.text)}</span>`;
}

/**
 * Word-level diff: highlight words in newText that differ from oldText.
 * Changed/added words get an orange background; removed words get strikethrough.
 */
function highlightDiff(oldText, newText) {
    const oldWords = oldText.split(/(\s+)/);
    const newWords = newText.split(/(\s+)/);

    // Simple LCS-based diff on words
    const lcs = buildLcs(oldWords, newWords);
    const result = [];

    let oi = 0, ni = 0, li = 0;
    while (oi < oldWords.length || ni < newWords.length) {
        if (li < lcs.length && oi < oldWords.length && ni < newWords.length
            && oldWords[oi] === lcs[li] && newWords[ni] === lcs[li]) {
            // Common word
            result.push(escHtml(newWords[ni]));
            oi++; ni++; li++;
        } else if (li < lcs.length && ni < newWords.length && newWords[ni] === lcs[li]) {
            // Deleted from old
            if (oldWords[oi]?.trim()) {
                result.push(`<span style="color:#b45309;text-decoration:line-through;opacity:0.6;">${escHtml(oldWords[oi])}</span>`);
            }
            oi++;
        } else if (ni < newWords.length) {
            // Added in new
            if (newWords[ni]?.trim()) {
                result.push(`<span style="background:#fef3c7;color:#92400e;border-radius:2px;padding:0 2px;">${escHtml(newWords[ni])}</span>`);
            } else {
                result.push(escHtml(newWords[ni])); // whitespace
            }
            ni++;
        } else {
            // Remaining old words (deleted)
            if (oldWords[oi]?.trim()) {
                result.push(`<span style="color:#b45309;text-decoration:line-through;opacity:0.6;">${escHtml(oldWords[oi])}</span>`);
            }
            oi++;
        }
    }

    return result.join('');
}

/**
 * Build longest common subsequence of two word arrays.
 */
function buildLcs(a, b) {
    const m = a.length, n = b.length;
    // For very long texts, skip LCS (too expensive) and just highlight everything
    if (m * n > 500000) return [];

    const dp = Array.from({ length: m + 1 }, () => new Uint16Array(n + 1));

    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            dp[i][j] = a[i - 1] === b[j - 1]
                ? dp[i - 1][j - 1] + 1
                : Math.max(dp[i - 1][j], dp[i][j - 1]);
        }
    }

    const lcs = [];
    let i = m, j = n;
    while (i > 0 && j > 0) {
        if (a[i - 1] === b[j - 1]) {
            lcs.unshift(a[i - 1]);
            i--; j--;
        } else if (dp[i - 1][j] > dp[i][j - 1]) {
            i--;
        } else {
            j--;
        }
    }
    return lcs;
}
