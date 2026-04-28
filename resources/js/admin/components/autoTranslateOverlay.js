/**
 * Auto-translate overlay — shown after a record is saved (store or update).
 *
 * Triggered by 'auto-translate-start' DOM event whose detail matches:
 *   {
 *     type, id, isUpdate, translateUrl, applyUrl, geoGenerateUrl,
 *     hasSeo, sourceLang, targetLangs, contentFields, seoFields,
 *     editUrl, translationsUrl, seoGeoUrl,
 *     unchangedCount, changedFields, changedSeoFields
 *   }
 *
 * Three-phase flow (store):
 *   Phase 1 – Translate content fields (title, description, …)
 *   Phase 2 – Generate SEO/GEO via Claude (EN + all target langs)
 *   Phase 3 – Translate SEO/GEO fields
 *
 * For update: shows delta (x unchanged, y changed) and only processes changed fields.
 * Final summary screen with status per phase + navigation links.
 */

const LOCALE_FLAGS = {
    de: '🇩🇪', fr: '🇫🇷', pl: '🇵🇱', ru: '🇷🇺',
    el: '🇬🇷', ar: '🇸🇦', zh: '🇨🇳', es: '🇪🇸',
    it: '🇮🇹', pt: '🇵🇹', nl: '🇳🇱', ja: '🇯🇵',
};

const LOCALE_NAMES = {
    de: 'DE', fr: 'FR', pl: 'PL', ru: 'RU',
    el: 'GR', ar: 'AR', zh: 'ZH', es: 'ES',
    it: 'IT', pt: 'PT', nl: 'NL', ja: 'JA',
};

const ATO_I18N = {
    overlay_changed_fields_processing: 'Geänderte Felder werden verarbeitet…',
    overlay_auto_translations_generating: 'Automatische Übersetzungen werden generiert…',
    overlay_translate_content: 'Inhalt übersetzen',
    overlay_generate_seo_geo: 'SEO & GEO generieren',
    overlay_translate_seo_geo: 'SEO & GEO übersetzen',
    overlay_please_wait: 'Bitte warten…',
    overlay_translations: 'Übersetzungen',
    overlay_no_fields_to_translate: 'Keine Felder zu übersetzen',
    overlay_no_changed_fields: 'Keine geänderten Felder',
    overlay_no_fields: 'Keine Felder',
    overlay_seo_geo_not_available: 'Nicht verfügbar für diesen Typ',
    overlay_skipped_user_choice: 'Übersprungen (Benutzer-Auswahl)',
    overlay_skipped_no_confirm: 'Übersprungen (bei Dialog mit "Nein" bestätigt)',
    overlay_close_done: 'Schließen ✓',
    overlay_confirm_seo_geo_rerun: 'SEO & GEO neu ausführen?',
    overlay_confirm_seo_geo_text: 'Inhalt wird wie gewohnt übersetzt. Soll zusätzlich SEO/GEO neu generiert und übersetzt werden?',
    overlay_yes: 'Ja',
    overlay_no: 'Nein',
    overlay_saved: 'Gespeichert',
    overlay_saved_detail: 'Eintrag erfolgreich gespeichert',
    overlay_fields: 'Felder',
    overlay_changed_unchanged: ':unchanged unverändert · :changed geändert',
    overlay_translations_detail_ok: ':count Sprachen übersetzt',
    overlay_translations_detail_mix: ':ok OK · :err Fehler',
    overlay_seo_geo: 'SEO & GEO',
    overlay_generated: 'Generiert',
    overlay_generation_error: 'Fehler bei Generierung',
    overlay_geo_translations: 'GEO-Übersetzungen',
    ...(window.ADMIN_I18N || {}),
};

function tr(key, repl = {}) {
    let text = ATO_I18N[key] ?? key;
    Object.entries(repl).forEach(([k, v]) => {
        text = text.replace(`:${k}`, String(v));
    });
    return text;
}

// ── Entry point ─────────────────────────────────────────────────────────────

export function autoTranslateOverlay() {
    document.addEventListener('auto-translate-start', (e) => {
        if (e.detail) runOverlay(e.detail);
    });
}

// ── Orchestration ───────────────────────────────────────────────────────────

async function runOverlay(cfg) {
    const {
        type, id, isUpdate = false,
        translateUrl, applyUrl, geoGenerateUrl,
        hasSeo = false, sourceLang, targetLangs,
        contentFields = [], seoFields = [],
        editUrl, translationsUrl, seoGeoUrl,
        unchangedCount = 0, changedFields = [], changedSeoFields = [],
    } = cfg;

    if (!targetLangs?.length) return;

    const summary = {
        contentOk: 0, contentErr: 0,
        geoOk: false, geoErr: false, geoSkipped: !hasSeo || !geoGenerateUrl,
        geoSkippedByUser: false,
        seoOk: 0, seoErr: 0,
    };

    const overlay = buildOverlay(cfg);
    document.body.appendChild(overlay);

    // ── Phase 1: Translate content fields ───────────────────────────────────
    if (contentFields.length) {
        showSection(overlay, 'content');
        for (const lang of targetLangs) {
            setStatus(overlay, 'content', lang, 'running');
            try {
                await translateAndApply(translateUrl, applyUrl, type, id, contentFields, sourceLang, lang);
                setStatus(overlay, 'content', lang, 'ok');
                summary.contentOk++;
            } catch {
                setStatus(overlay, 'content', lang, 'err');
                summary.contentErr++;
            }
        }
    } else {
        showSection(overlay, 'content');
        setNote(overlay, 'content', isUpdate ? tr('overlay_no_changed_fields') : tr('overlay_no_fields'));
    }

    // ── Phase 2+3: SEO/GEO (for updates only after explicit confirmation) ──
    let shouldRunSeoGeo = true;
    if (isUpdate && hasSeo && geoGenerateUrl) {
        shouldRunSeoGeo = await askSeoGeoConfirmation(overlay);
        if (!shouldRunSeoGeo) {
            summary.geoSkippedByUser = true;
            summary.geoSkipped = true;
            setNote(overlay, 'geo', tr('overlay_skipped_no_confirm'));
            setNote(overlay, 'seo', tr('overlay_skipped_no_confirm'));
        }
    }

    // ── Phase 2: Generate SEO/GEO (EN first, then all target langs) ─────────
    if (shouldRunSeoGeo && hasSeo && geoGenerateUrl) {
        showSection(overlay, 'geo');
        const allGeoLangs = [sourceLang, ...targetLangs];
        let geoAnyOk = false;
        for (const lang of allGeoLangs) {
            setStatus(overlay, 'geo', lang, 'running');
            try {
                await generateAndSaveGeo(geoGenerateUrl, applyUrl, type, id, lang);
                setStatus(overlay, 'geo', lang, 'ok');
                geoAnyOk = true;
            } catch {
                setStatus(overlay, 'geo', lang, 'err');
            }
        }
        summary.geoOk = geoAnyOk;
        summary.geoErr = !geoAnyOk;
    }

    // ── Phase 3: Translate SEO/GEO fields ───────────────────────────────────
    if (shouldRunSeoGeo && hasSeo && seoFields.length) {
        showSection(overlay, 'seo');
        for (const lang of targetLangs) {
            setStatus(overlay, 'seo', lang, 'running');
            try {
                await translateAndApply(translateUrl, applyUrl, type, id, seoFields, sourceLang, lang);
                setStatus(overlay, 'seo', lang, 'ok');
                summary.seoOk++;
            } catch {
                setStatus(overlay, 'seo', lang, 'err');
                summary.seoErr++;
            }
        }
    }

    // ── Show final summary ──────────────────────────────────────────────────
    showSummary(overlay, summary, cfg);
}

// ── API calls ───────────────────────────────────────────────────────────────

async function translateAndApply(translateUrl, applyUrl, type, id, fields, sourceLang, targetLang) {
    const items = fields.map(field => ({ type, id, field }));

    const tRes = await post(translateUrl, {
        items,
        source_lang: sourceLang,
        target_lang: targetLang,
    });

    const translated = (tRes.translations ?? []).filter(t => t.text?.trim());
    if (!translated.length) return;

    await post(applyUrl, {
        items: translated,
        target_lang: targetLang,
    });
}

async function generateAndSaveGeo(geoGenerateUrl, applyUrl, type, id, locale) {
    const result = await post(geoGenerateUrl, { type, id, locale });

    const items = Object.entries(result)
        .filter(([, v]) => v?.trim?.())
        .map(([field, text]) => ({ type, id, field, text }));

    if (!items.length) throw new Error('Empty GEO result');

    await post(applyUrl, { items, target_lang: locale });
}

function post(url, body) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
        },
        body: JSON.stringify(body),
    }).then(async r => {
        if (!r.ok) throw new Error(await r.text());
        return r.json();
    });
}

// ── DOM helpers ─────────────────────────────────────────────────────────────

function showSection(overlay, phase) {
    const el = overlay.querySelector(`[data-ato-section="${phase}"]`);
    if (el) el.style.display = '';
}

function setNote(overlay, phase, text) {
    const el = overlay.querySelector(`[data-ato-note="${phase}"]`);
    if (el) { el.textContent = text; el.style.display = ''; }
}

function setStatus(overlay, phase, lang, status) {
    const badge = overlay.querySelector(`[data-ato-badge="${phase}-${lang}"]`);
    if (!badge) return;
    const icon = badge.querySelector('[data-ato-icon]');
    ['bg-secondary', 'bg-primary', 'bg-success', 'bg-danger', 'bg-warning'].forEach(c => badge.classList.remove(c));
    switch (status) {
        case 'running':
            badge.classList.add('bg-primary');
            icon.innerHTML = `<span class="spinner-border" style="width:.65rem;height:.65rem;border-width:2px"></span>`;
            break;
        case 'ok':
            badge.classList.add('bg-success');
            icon.textContent = '✓';
            break;
        case 'err':
            badge.classList.add('bg-danger');
            icon.textContent = '✗';
            break;
        default:
            badge.classList.add('bg-secondary');
            icon.textContent = '·';
    }
}

function showSummary(overlay, summary, cfg) {
    const { isUpdate, unchangedCount, changedFields, changedSeoFields,
            hasSeo, editUrl, translationsUrl, seoGeoUrl } = cfg;

    overlay.querySelector('[data-ato-spinner]').style.display = 'none';

    const summaryEl = overlay.querySelector('[data-ato-summary]');
    if (!summaryEl) return;

    const rows = [];

    // Speichern — always ok (we got here)
    rows.push(summaryRow('✓', 'bg-success', tr('overlay_saved'), tr('overlay_saved_detail')));

    // Delta info for updates
    if (isUpdate && unchangedCount > 0) {
        const changedTotal = (changedFields?.length ?? 0) + (changedSeoFields?.length ?? 0);
        rows.push(summaryRow('ℹ', 'bg-secondary', tr('overlay_fields'),
            tr('overlay_changed_unchanged', { unchanged: unchangedCount, changed: changedTotal })));
    }

    // Translations
    const tTotal = summary.contentOk + summary.contentErr;
    if (tTotal > 0) {
        const allOk = summary.contentErr === 0;
        rows.push(summaryRow(
            allOk ? '✓' : '!',
            allOk ? 'bg-success' : 'bg-warning text-dark',
            tr('overlay_translations'),
            allOk
                ? tr('overlay_translations_detail_ok', { count: summary.contentOk })
                : tr('overlay_translations_detail_mix', { ok: summary.contentOk, err: summary.contentErr })
        ));
    } else {
        rows.push(summaryRow('–', 'bg-secondary', tr('overlay_translations'), tr('overlay_no_fields_to_translate')));
    }

    // GEO
    if (!summary.geoSkipped) {
        rows.push(summaryRow(
            summary.geoOk ? '✓' : '✗',
            summary.geoOk ? 'bg-success' : 'bg-danger',
            tr('overlay_seo_geo'),
            summary.geoOk ? tr('overlay_generated') : tr('overlay_generation_error')
        ));
        // GEO translations
        const gTotal = summary.seoOk + summary.seoErr;
        if (gTotal > 0) {
            const allOk = summary.seoErr === 0;
            rows.push(summaryRow(
                allOk ? '✓' : '!',
                allOk ? 'bg-success' : 'bg-warning text-dark',
                tr('overlay_geo_translations'),
                allOk
                    ? tr('overlay_translations_detail_ok', { count: summary.seoOk })
                    : tr('overlay_translations_detail_mix', { ok: summary.seoOk, err: summary.seoErr })
            ));
        }
    } else {
        const detail = summary.geoSkippedByUser
            ? tr('overlay_skipped_user_choice')
            : tr('overlay_seo_geo_not_available');
        rows.push(summaryRow('–', 'bg-secondary', tr('overlay_seo_geo'), detail));
    }

    summaryEl.innerHTML = `
        <div class="d-flex flex-column gap-2 mb-3">
            ${rows.join('')}
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            ${editUrl ? `<a href="${editUrl}" class="btn btn-sm btn-outline-secondary">← Zurück zum Eintrag</a>` : ''}
            ${translationsUrl ? `<a href="${translationsUrl}" class="btn btn-sm btn-outline-primary">${tr('overlay_translations')}</a>` : ''}
            ${seoGeoUrl ? `<a href="${seoGeoUrl}" class="btn btn-sm btn-outline-info">SEO / GEO</a>` : ''}
        </div>
    `;
    summaryEl.style.display = '';

    const closeBtn = overlay.querySelector('[data-ato-close]');
    closeBtn.disabled = false;
    closeBtn.textContent = tr('overlay_close_done');
    closeBtn.classList.replace('btn-secondary', 'btn-success');
    closeBtn.addEventListener('click', () => overlay.remove(), { once: true });
}

function summaryRow(icon, badgeClass, label, detail) {
    return `
        <div class="d-flex align-items-center gap-2">
            <span class="badge ${badgeClass} d-flex align-items-center justify-content-center"
                  style="width:1.4rem;height:1.4rem;font-size:.75rem;flex-shrink:0">${icon}</span>
            <span class="small fw-semibold" style="min-width:130px">${label}</span>
            <span class="small text-muted">${detail}</span>
        </div>`;
}

function askSeoGeoConfirmation(overlay) {
    return new Promise(resolve => {
        const dialog = document.createElement('div');
        dialog.style.cssText = [
            'position:absolute',
            'inset:0',
            'display:flex',
            'align-items:center',
            'justify-content:center',
            'background:rgba(0,0,0,.45)',
            'z-index:2',
            'border-radius:.375rem',
        ].join(';');

        dialog.innerHTML = `
            <div class="card shadow border-0" style="max-width:420px;width:calc(100% - 2rem)">
                <div class="card-body">
                    <div class="fw-semibold mb-2">${tr('overlay_confirm_seo_geo_rerun')}</div>
                    <div class="small text-muted mb-3">
                        ${tr('overlay_confirm_seo_geo_text')}
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-ato-seo-no>${tr('overlay_no')}</button>
                        <button type="button" class="btn btn-sm btn-primary" data-ato-seo-yes>${tr('overlay_yes')}</button>
                    </div>
                </div>
            </div>
        `;

        const host = overlay.querySelector('.card') || overlay;
        host.style.position = 'relative';
        host.appendChild(dialog);

        dialog.querySelector('[data-ato-seo-no]')?.addEventListener('click', () => {
            dialog.remove();
            resolve(false);
        }, { once: true });

        dialog.querySelector('[data-ato-seo-yes]')?.addEventListener('click', () => {
            dialog.remove();
            resolve(true);
        }, { once: true });
    });
}

function langBadges(langs, phase) {
    return langs.map(lang => {
        const flag = LOCALE_FLAGS[lang] ?? '';
        const name = LOCALE_NAMES[lang] ?? lang.toUpperCase();
        return `<span
            data-ato-badge="${phase}-${lang}"
            class="badge bg-secondary d-inline-flex align-items-center gap-1 px-2 py-2"
            style="font-size:.8rem;min-width:3.2rem"
        ><span data-ato-icon>·</span>${flag ? ' ' + flag : ''} ${name}</span>`;
    }).join('');
}

// ── Build overlay DOM ────────────────────────────────────────────────────────

function buildOverlay(cfg) {
    const {
        isUpdate, targetLangs, sourceLang, hasSeo,
        unchangedCount, changedFields, changedSeoFields,
    } = cfg;

    const allGeoLangs  = hasSeo ? [sourceLang, ...targetLangs] : [];
    const changedTotal = (changedFields?.length ?? 0) + (changedSeoFields?.length ?? 0);

    let deltaNote = '';
    if (isUpdate) {
        deltaNote = `
            <div class="alert alert-light border py-2 px-3 mb-1 small">
                <strong>${changedTotal}</strong> geänderte Felder werden verarbeitet
                ${unchangedCount > 0 ? `· <span class="text-muted">${unchangedCount} unverändert</span>` : ''}
            </div>`;
    }

    const el = document.createElement('div');
    el.style.cssText = [
        'position:fixed', 'inset:0', 'z-index:10100',
        'display:flex', 'align-items:center', 'justify-content:center',
        'background:rgba(0,0,0,.6)', 'backdrop-filter:blur(3px)',
    ].join(';');

    el.innerHTML = `
        <div class="card border-0 shadow-lg" style="min-width:460px;max-width:560px;max-height:90vh;overflow-y:auto">
            <div class="card-header bg-dark text-white d-flex align-items-center gap-2 border-0 py-3">
                <span data-ato-spinner class="spinner-border spinner-border-sm text-light flex-shrink-0"></span>
                <span class="fw-semibold">${isUpdate ? tr('overlay_changed_fields_processing') : tr('overlay_auto_translations_generating')}</span>
            </div>

            <div class="card-body py-3 d-flex flex-column gap-3">

                ${deltaNote}

                <div data-ato-section="content">
                    <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.05em">
                        ${tr('overlay_translate_content')}
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        ${langBadges(targetLangs, 'content')}
                    </div>
                    <div data-ato-note="content" class="small text-muted mt-1" style="display:none"></div>
                </div>

                ${hasSeo ? `
                <div data-ato-section="geo" style="display:none">
                    <div class="d-flex align-items-center gap-2 text-muted mb-2" style="font-size:.8rem">
                        <div class="flex-grow-1 border-top"></div>
                        <span class="fw-semibold text-uppercase" style="letter-spacing:.05em">${tr('overlay_generate_seo_geo')}</span>
                        <div class="flex-grow-1 border-top"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        ${langBadges(allGeoLangs, 'geo')}
                    </div>
                </div>

                <div data-ato-section="seo" style="display:none">
                    <div class="d-flex align-items-center gap-2 text-muted mb-2" style="font-size:.8rem">
                        <div class="flex-grow-1 border-top"></div>
                        <span class="fw-semibold text-uppercase" style="letter-spacing:.05em">${tr('overlay_translate_seo_geo')}</span>
                        <div class="flex-grow-1 border-top"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        ${langBadges(targetLangs, 'seo')}
                    </div>
                </div>` : ''}

                <div data-ato-summary style="display:none;border-top:1px solid rgba(0,0,0,.1);padding-top:.75rem;margin-top:.25rem">
                </div>

            </div>

            <div class="card-footer bg-transparent border-0 text-end pb-3 pe-3">
                <button data-ato-close class="btn btn-secondary btn-sm" disabled>${tr('overlay_please_wait')}</button>
            </div>
        </div>
    `;

    return el;
}
