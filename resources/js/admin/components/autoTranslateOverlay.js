/**
 * Auto-translate overlay — shown after a new record is saved.
 *
 * Triggered by a custom DOM event 'auto-translate-start' whose detail matches:
 *   { type, id, translateUrl, applyUrl, sourceLang, targetLangs,
 *     contentFields, seoFields }
 *
 * Two-phase flow:
 *   Phase 1 – content fields  (title, description, location, …)
 *   Phase 2 – SEO & GEO fields (seo_title, seo_description, …)
 *
 * Each language is processed sequentially so the user sees real-time progress.
 */

// ── Locale display helpers ──────────────────────────────────────────────────

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

// ── Entry point ─────────────────────────────────────────────────────────────

export function autoTranslateOverlay() {
    document.addEventListener('auto-translate-start', (e) => {
        if (e.detail) runOverlay(e.detail);
    });
}

// ── Orchestration ───────────────────────────────────────────────────────────

async function runOverlay(cfg) {
    const { type, id, translateUrl, applyUrl, sourceLang, targetLangs, contentFields, seoFields } = cfg;

    if (!targetLangs?.length) return;

    const overlay = buildOverlay(targetLangs);
    document.body.appendChild(overlay);

    // ── Phase 1: content ────────────────────────────────────────────────────
    for (const lang of targetLangs) {
        setStatus(overlay, 'content', lang, 'translating');
        try {
            await translateAndApply(translateUrl, applyUrl, type, id, contentFields, sourceLang, lang);
            setStatus(overlay, 'content', lang, 'done');
        } catch {
            setStatus(overlay, 'content', lang, 'error');
        }
    }

    // ── Phase separator: show SEO section ───────────────────────────────────
    overlay.querySelector('[data-ato-seo]').style.display = '';

    // ── Phase 2: SEO & GEO ──────────────────────────────────────────────────
    for (const lang of targetLangs) {
        setStatus(overlay, 'seo', lang, 'translating');
        try {
            await translateAndApply(translateUrl, applyUrl, type, id, seoFields, sourceLang, lang);
            setStatus(overlay, 'seo', lang, 'done');
        } catch {
            setStatus(overlay, 'seo', lang, 'error');
        }
    }

    // ── Done ────────────────────────────────────────────────────────────────
    overlay.querySelector('[data-ato-spinner]').style.display = 'none';

    const closeBtn = overlay.querySelector('[data-ato-close]');
    closeBtn.disabled = false;
    closeBtn.textContent = 'Fertig ✓';
    closeBtn.classList.replace('btn-secondary', 'btn-success');
    closeBtn.addEventListener('click', () => overlay.remove(), { once: true });
}

// ── API calls ───────────────────────────────────────────────────────────────

async function translateAndApply(translateUrl, applyUrl, type, id, fields, sourceLang, targetLang) {
    const items = fields.map(field => ({ type, id, field }));

    // 1. Get translations from DeepL
    const tRes = await post(translateUrl, {
        items,
        source_lang: sourceLang,
        target_lang: targetLang,
    });

    const translated = (tRes.translations ?? []).filter(t => t.text?.trim());
    if (!translated.length) return;

    // 2. Persist to DB
    await post(applyUrl, {
        items: translated,
        target_lang: targetLang,
    });
}

function post(url, body) {
    return fetch(url, {
        method:  'POST',
        headers: {
            'Content-Type':  'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]')?.content ?? '',
        },
        body: JSON.stringify(body),
    }).then(async r => {
        if (!r.ok) throw new Error(await r.text());
        return r.json();
    });
}

// ── DOM helpers ─────────────────────────────────────────────────────────────

function setStatus(overlay, phase, lang, status) {
    const badge = overlay.querySelector(`[data-ato-badge="${phase}-${lang}"]`);
    if (!badge) return;

    const icon = badge.querySelector('[data-ato-icon]');

    // Reset classes
    ['bg-secondary', 'bg-primary', 'bg-success', 'bg-danger'].forEach(c => badge.classList.remove(c));

    switch (status) {
        case 'translating':
            badge.classList.add('bg-primary');
            icon.innerHTML = `<span class="spinner-border" style="width:.65rem;height:.65rem;border-width:2px"></span>`;
            break;
        case 'done':
            badge.classList.add('bg-success');
            icon.textContent = '✓';
            break;
        case 'error':
            badge.classList.add('bg-danger');
            icon.textContent = '✗';
            break;
        default:
            badge.classList.add('bg-secondary');
            icon.textContent = '·';
    }
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

function buildOverlay(targetLangs) {
    const el = document.createElement('div');
    el.style.cssText = [
        'position:fixed', 'inset:0', 'z-index:10100',
        'display:flex', 'align-items:center', 'justify-content:center',
        'background:rgba(0,0,0,.6)', 'backdrop-filter:blur(3px)',
    ].join(';');

    el.innerHTML = `
        <div class="card border-0 shadow-lg" style="min-width:440px;max-width:540px">
            <div class="card-header bg-dark text-white d-flex align-items-center gap-2 border-0 py-3">
                <span data-ato-spinner class="spinner-border spinner-border-sm text-light flex-shrink-0"></span>
                <span class="fw-semibold">Automatische Übersetzungen werden generiert</span>
            </div>

            <div class="card-body py-4 d-flex flex-column gap-4">

                <div>
                    <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.05em">
                        Inhalt
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        ${langBadges(targetLangs, 'content')}
                    </div>
                </div>

                <div data-ato-seo style="display:none">
                    <div class="d-flex align-items-center gap-2 text-muted mb-3" style="font-size:.8rem">
                        <div class="flex-grow-1 border-top"></div>
                        <span class="fw-semibold text-uppercase" style="letter-spacing:.05em">Jetzt die SEO &amp; GEO Texte</span>
                        <div class="flex-grow-1 border-top"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        ${langBadges(targetLangs, 'seo')}
                    </div>
                </div>

            </div>

            <div class="card-footer bg-transparent border-0 text-end pb-3 pe-3">
                <button data-ato-close class="btn btn-secondary btn-sm" disabled>Bitte warten…</button>
            </div>
        </div>
    `;

    return el;
}
