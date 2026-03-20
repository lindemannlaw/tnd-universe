/**
 * DeepL translation with timestamp-based review overlay + standalone SEO generation.
 *
 * Timestamps are stored server-side per field:
 *   { "title": { "en_changed_at": "ISO", "de_translated_at": "ISO" }, ... }
 *
 * A field "needs translation" when en_changed_at > de_translated_at (or de_translated_at missing).
 * Projects without timestamps = all fields are considered in-sync (unchecked).
 */

const TRANSLATE_ICON = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.913 17H20.087M12.913 17L11 21M12.913 17L15.7783 11.009C16.0092 10.5263 16.1246 10.2849 16.2826 10.2086C16.4199 10.1423 16.5801 10.1423 16.7174 10.2086C16.8754 10.2849 16.9908 10.5263 17.2217 11.009L20.087 17M20.087 17L22 21M2 5H8M8 5H11.5M8 5V3M11.5 5H14M11.5 5C11.0039 7.95729 9.85259 10.6362 8.16555 12.8844M10 14C9.38747 13.7248 8.76265 13.3421 8.16555 12.8844M8.16555 12.8844C6.81302 11.8478 5.60276 10.4266 5 9M8.16555 12.8844C6.56086 15.0229 4.47143 16.7718 2 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

const TEXT_ONLY_NAMES = new Set(['content', 'headline', 'link_text', 'link_url', 'subhead', 'title', 'number', 'subline']);

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
    title:             'Titel',
    number:            'Zahl',
    subline:           'Subline',
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

    // Capture current DE values before translation for diff display
    const currentDeValues = {};
    const emptyDeKeys = new Set(); // Track fields where DE is empty but EN has content
    const deEqualsEnKeys = new Set(); // Track fields where DE text ≈ EN text (never translated)
    itemsWithContent.forEach(({ key, text: enText }) => {
        const deKey   = key.replace(`[${sourceLocale}]`, `[${targetLocale}]`);
        const deField = form.querySelector(`[name="${CSS.escape(deKey)}"]:not([disabled])`)
                     ?? form.querySelector(`[name="${CSS.escape(deKey)}"]`);
        if (!deField) return;
        const val = deField._sunEditor ? deField._sunEditor.getContents() : (deField.value ?? '');
        if (hasContent(val)) {
            currentDeValues[key] = val;
            // Check if DE text is identical to EN text (never properly translated)
            const enClean = enText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            const deClean = val.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            if (enClean && deClean && enClean === deClean) {
                deEqualsEnKeys.add(key);
            }
        } else {
            emptyDeKeys.add(key); // DE is empty → needs translation
        }
    });

    // Also mark fields where DE is empty or DE=EN as needing translation
    emptyDeKeys.forEach(key => changedKeys.add(key));
    deEqualsEnKeys.forEach(key => changedKeys.add(key));

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

        const approved = await showReviewOverlay(translations, itemsWithContent, changedKeys, timestamps, currentDeValues, emptyDeKeys, deEqualsEnKeys, translateUrl);

        button.disabled = false;
        if (approved === null) return; // cancelled

        // Apply approved translations to DE form fields (for live preview)
        let count = 0;
        const appliedTsKeys = [];
        const translationsPayload = [];
        for (const [sourceKey, { text: translatedText, isHtml }] of Object.entries(approved)) {
            const targetKey   = sourceKey.replace(`[${sourceLocale}]`, `[${targetLocale}]`);
            const targetField = form.querySelector(`[name="${CSS.escape(targetKey)}"]:not([disabled])`)
                             ?? form.querySelector(`[name="${CSS.escape(targetKey)}"]`);
            if (targetField) {
                targetField.value = translatedText;
                if (targetField._sunEditor) targetField._sunEditor.setContents(translatedText);
                targetField.dispatchEvent(new Event('input', { bubbles: true }));
            }
            count++;
            appliedTsKeys.push(formNameToTimestampKey(sourceKey));
            translationsPayload.push({ key: sourceKey, text: translatedText });
        }

        // Save directly to DB via API
        const applyUrl = button.dataset.applyTranslationsUrl;
        if (applyUrl && translationsPayload.length > 0) {
            try {
                const saveResp = await fetch(applyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        translations: translationsPayload,
                        timestamp_keys: appliedTsKeys,
                    }),
                });

                if (!saveResp.ok) {
                    const errData = await saveResp.json().catch(() => ({}));
                    throw new Error(errData.error || `HTTP ${saveResp.status}`);
                }

                // Update local timestamps
                const now = new Date().toISOString();
                appliedTsKeys.forEach(k => {
                    timestamps[k] = { ...(timestamps[k] ?? {}), de_translated_at: now };
                });
                button.dataset.textTimestamps = JSON.stringify(timestamps);

                showToast('success', `${count} Übersetzung${count !== 1 ? 'en' : ''} gespeichert`);
                flashButton(button, `✓ ${count} gespeichert`, 2500, titleSpan, originalTitle);
            } catch (saveErr) {
                console.error('[translateBlocks] Save failed:', saveErr);
                showToast('error', 'Speichern fehlgeschlagen: ' + saveErr.message);
                flashButton(button, '✗ Fehler', 2500, titleSpan, originalTitle);
            }
        } else {
            flashButton(button, `✓ ${count} übernommen`, 2500, titleSpan, originalTitle);
        }

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

function showReviewOverlay(translations, allItems, changedKeys, timestamps, currentDeValues = {}, emptyDeKeys = new Set(), deEqualsEnKeys = new Set(), translateUrl = '') {
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

        const overlay = buildOverlayEl(translations, allItems, changedKeys, timestamps, currentDeValues, emptyDeKeys, deEqualsEnKeys);
        // Append inside modal so Bootstrap's focus-trap allows typing
        const modal = document.querySelector('.modal.show') || document.body;
        modal.appendChild(overlay);

        // Wire up per-field re-translate buttons
        if (translateUrl) {
            wireRetranslateButtons(overlay, allItems, translateUrl);
        }

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

function buildOverlayEl(translations, allItems, changedKeys, timestamps, currentDeValues = {}, emptyDeKeys = new Set(), deEqualsEnKeys = new Set()) {
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
        const isEmptyDe   = emptyDeKeys.has(key);
        const isDeEqualsEn = deEqualsEnKeys.has(key);
        let badgeHtml = '';
        if (isEmptyDe)          badgeHtml = '<span class="badge bg-danger text-white ms-2">DE leer</span>';
        else if (isDeEqualsEn)  badgeHtml = '<span class="badge bg-danger text-white ms-2">DE = EN</span>';
        else if (hasTextDiff)   badgeHtml = '<span class="badge bg-warning text-dark ms-2">Text ge\u00E4ndert</span>';
        else if (hasFormatDiff) badgeHtml = '<span class="badge bg-info text-dark ms-2">Formatierung ge\u00E4ndert</span>';
        else if (isChanged)     badgeHtml = '<span class="badge bg-warning text-dark ms-2">Ge\u00E4ndert</span>';

        // EN source: only show diff (compact), not full text
        let enContentHtml;
        if (hasTextDiff) {
            enContentHtml = `<div class="small">${highlightDiff(oldClean, sourceClean)}</div>`;
        } else if (hasFormatDiff) {
            enContentHtml = `<div class="small">${highlightHtmlDiff(rawOldTxt, sourceText)}</div>`;
        } else {
            // No diff available: show truncated preview
            const preview = sourceClean.length > 120 ? sourceClean.substring(0, 120) + '\u2026' : sourceClean;
            enContentHtml = `<div class="small text-muted fst-italic">${escHtml(preview)}</div>`;
        }

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

        // DE diff: compare current DE value with new DeepL translation
        const currentDe    = currentDeValues[key] || '';
        const currentDeClean = currentDe.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        const newDeClean     = (translated || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        const hasDeDiff      = currentDeClean && newDeClean && currentDeClean !== newDeClean;
        const deDiffHtml     = hasDeDiff
            ? `<div class="small mb-1 p-2 rounded" style="background:#f0fdf4;border:1px dashed #22c55e;">
                    <span class="text-muted" style="font-size:0.7rem;">\u00C4nderungen DE (alt \u2192 neu):</span><br>
                    ${highlightDiff(currentDeClean, newDeClean)}
               </div>`
            : '';

        return `
            <div class="border rounded p-2 d-flex flex-column gap-1 ${isChanged ? 'border-warning' : ''}" data-tro-item>
                <div class="d-flex align-items-center gap-2">
                    <input class="form-check-input flex-shrink-0 mt-0" type="checkbox" ${isChanged ? 'checked' : ''} data-tro-checkbox>
                    <span class="fw-semibold small text-uppercase">${escHtml(label)}</span>
                    ${badgeHtml}
                </div>
                <div class="d-flex align-items-start gap-1">
                    <span class="flex-shrink-0" style="font-size:0.85rem;" title="English">\u{1F1EC}\u{1F1E7}</span>
                    <div class="border-start border-2 border-secondary-subtle ps-2 flex-grow-1"
                         style="white-space:pre-wrap;">
                        ${enContentHtml}
                    </div>
                </div>
                <div class="d-flex align-items-start gap-1">
                    <div class="flex-shrink-0 d-flex flex-column align-items-center gap-1">
                        <span style="font-size:0.85rem;" title="Deutsch">\u{1F1E9}\u{1F1EA}</span>
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary tro-retranslate p-0 d-flex align-items-center justify-content-center"
                            data-retranslate-key="${escAttr(key)}"
                            title="Neu \u00FCbersetzen"
                            style="width:22px;height:22px;">
                            ${TRANSLATE_ICON}
                        </button>
                    </div>
                    <div class="flex-grow-1">
                        ${deDiffHtml}
                        ${editorHtml}
                    </div>
                </div>
            </div>`;
    }).join('');

    overlay.innerHTML = `
        <div class="bg-white rounded-3 shadow-lg d-flex flex-column"
             style="width:min(800px,96vw);max-height:90vh;">
            <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom flex-shrink-0">
                <h5 class="mb-0 me-auto fw-semibold">\u{1F310} \u00DCbersetzungen pr\u00FCfen</h5>
                <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" id="tro-retranslate-all"
                        title="Alle ausgew\u00E4hlten Felder neu mit DeepL \u00FCbersetzen">
                    ${TRANSLATE_ICON}
                    <span>Alle mit DeepL \u00FCbersetzen (<span id="tro-retranslate-count">0</span>)</span>
                </button>
                <label class="d-flex align-items-center gap-2 mb-0 small user-select-none" style="cursor:pointer;">
                    <input class="form-check-input mt-0" type="checkbox" id="tro-select-all">
                    Alle ausw\u00E4hlen
                </label>
            </div>
            <div class="overflow-y-auto flex-grow-1 px-4 py-3 d-flex flex-column gap-3">
                ${changedCount > 0
                    ? `<div class="small fw-semibold text-warning-emphasis">${changedCount} Feld${changedCount !== 1 ? 'er' : ''} ben\u00F6tigen \u00DCbersetzung</div>`
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
    const selectAllEl       = overlay.querySelector('#tro-select-all');
    const countEl           = overlay.querySelector('#tro-count');
    const applyBtn          = overlay.querySelector('#tro-apply');
    const retranslateCountEl = overlay.querySelector('#tro-retranslate-count');

    const updateState = () => {
        const all     = [...overlay.querySelectorAll('[data-tro-checkbox]')];
        const checked = all.filter(c => c.checked).length;
        countEl.textContent       = `${checked} von ${allItems.length} ausgew\u00E4hlt`;
        selectAllEl.checked       = checked === all.length;
        selectAllEl.indeterminate = checked > 0 && checked < all.length;
        applyBtn.disabled         = checked === 0;
        if (retranslateCountEl) retranslateCountEl.textContent = checked;
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
// Per-field re-translate
// ---------------------------------------------------------------------------

function wireRetranslateButtons(overlay, allItems, translateUrl) {
    const itemMap = Object.fromEntries(allItems.map(i => [i.key, i]));

    overlay.querySelectorAll('.tro-retranslate').forEach(btn => {
        btn.addEventListener('click', async () => {
            const key  = btn.dataset.retranslateKey;
            const item = itemMap[key];
            if (!item) return;

            const editor = overlay.querySelector(`.tro-editor[data-key="${CSS.escape(key)}"]`);
            if (!editor) return;

            // Visual feedback
            const origHtml = btn.innerHTML;
            btn.disabled   = true;
            btn.innerHTML  = '\u231B';

            try {
                const response = await fetch(translateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        source_lang: 'en',
                        target_lang: 'de',
                        items: [{ key: item.key, text: item.text, isHtml: item.isHtml }],
                    }),
                });

                if (!response.ok) {
                    const err = await response.json().catch(() => ({}));
                    throw new Error(err.error || `HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('[translateBlocks] Single response:', JSON.stringify(data).substring(0, 300));
                console.log('[translateBlocks] Looking for key:', key);
                const translated = data.translations?.[key] ?? '';

                if (translated) {
                    if (editor.dataset.isHtml === 'true') {
                        editor.innerHTML = translated;
                    } else {
                        editor.value = translated;
                    }
                    // Auto-check the checkbox
                    const troItem = btn.closest('[data-tro-item]');
                    const cb = troItem?.querySelector('[data-tro-checkbox]');
                    if (cb && !cb.checked) {
                        cb.checked = true;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    btn.innerHTML = '\u2705';
                } else {
                    console.warn('[translateBlocks] No translation returned for key:', key, data);
                    btn.innerHTML = '\u274C';
                    showToast('error', 'Keine \u00DCbersetzung erhalten');
                }
            } catch (err) {
                console.error('[translateBlocks] Re-translate failed:', err);
                btn.innerHTML = '\u274C';
                showToast('error', '\u00DCbersetzung fehlgeschlagen: ' + err.message);
            }

            setTimeout(() => {
                btn.innerHTML = origHtml;
                btn.disabled  = false;
            }, 1500);
        });
    });

    // "Alle mit DeepL übersetzen" button
    const retranslateAllBtn = overlay.querySelector('#tro-retranslate-all');
    if (retranslateAllBtn) {
        retranslateAllBtn.addEventListener('click', async () => {
            // Collect all checked items
            const checkedItems = [];
            overlay.querySelectorAll('[data-tro-item]').forEach(troItem => {
                const cb = troItem.querySelector('[data-tro-checkbox]');
                if (!cb?.checked) return;
                const editor = troItem.querySelector('.tro-editor');
                if (!editor) return;
                const key  = editor.dataset.key;
                const item = itemMap[key];
                if (item) checkedItems.push({ key, item, editor });
            });

            if (checkedItems.length === 0) return;

            // Visual feedback
            const origHtml = retranslateAllBtn.innerHTML;
            retranslateAllBtn.disabled  = true;
            retranslateAllBtn.innerHTML = `\u231B <span>\u00DCbersetze ${checkedItems.length} Felder\u2026</span>`;

            try {
                const response = await fetch(translateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        source_lang: 'en',
                        target_lang: 'de',
                        items: checkedItems.map(({ item }) => ({
                            key: item.key, text: item.text, isHtml: item.isHtml,
                        })),
                    }),
                });

                if (!response.ok) {
                    const err = await response.json().catch(() => ({}));
                    throw new Error(err.error || `HTTP ${response.status}`);
                }

                const data = await response.json();
                const translations = data.translations ?? {};
                const responseKeys = Object.keys(translations);
                console.log('[translateBlocks] Bulk response keys:', responseKeys.length, responseKeys.slice(0, 3));
                console.log('[translateBlocks] Expected keys:', checkedItems.slice(0, 3).map(ci => ci.key));

                let updated = 0;
                let skipped = 0;

                checkedItems.forEach(({ key, editor }) => {
                    const translated = translations[key];
                    if (translated === undefined || translated === null) {
                        skipped++;
                        console.warn('[translateBlocks] Key not found in response:', key);
                        return;
                    }
                    if (editor.dataset.isHtml === 'true') {
                        editor.innerHTML = translated;
                    } else {
                        editor.value = translated;
                    }
                    updated++;
                });

                console.log('[translateBlocks] Bulk result:', { updated, skipped, totalResponse: responseKeys.length });
                retranslateAllBtn.innerHTML = `\u2705 <span>${updated} \u00FCbersetzt</span>`;
                if (updated > 0) {
                    showToast('success', `${updated} Feld${updated !== 1 ? 'er' : ''} \u00FCbersetzt`);
                } else if (skipped > 0) {
                    showToast('error', `${skipped} Keys nicht in Response gefunden`);
                } else {
                    showToast('error', 'Keine \u00DCbersetzungen in Response');
                }
            } catch (err) {
                console.error('[translateBlocks] Retranslate-all failed:', err);
                retranslateAllBtn.innerHTML = `\u274C <span>Fehler</span>`;
                showToast('error', '\u00DCbersetzung fehlgeschlagen: ' + err.message);
            }

            setTimeout(() => {
                retranslateAllBtn.innerHTML = origHtml;
                retranslateAllBtn.disabled  = false;
                // Update count
                const countSpan = retranslateAllBtn.querySelector('#tro-retranslate-count');
                if (countSpan) {
                    const checked = [...overlay.querySelectorAll('[data-tro-checkbox]')].filter(c => c.checked).length;
                    countSpan.textContent = checked;
                }
            }, 2000);
        });
    }
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

/**
 * Show a toast notification.
 * @param {'success'|'error'} type
 * @param {string} message
 */
function showToast(type, message) {
    const bg    = type === 'success' ? '#059669' : '#db2777'; // green / magenta
    const toast = document.createElement('div');
    toast.style.cssText = [
        'position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);z-index:20000;',
        `background:${bg};color:#fff;padding:0.75rem 1.5rem;border-radius:0.5rem;`,
        'font-size:0.875rem;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,0.25);',
        'opacity:0;transition:opacity 0.3s ease;pointer-events:none;',
    ].join('');
    toast.textContent = message;
    document.body.appendChild(toast);
    requestAnimationFrame(() => { toast.style.opacity = '1'; });
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 350);
    }, 3000);
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
