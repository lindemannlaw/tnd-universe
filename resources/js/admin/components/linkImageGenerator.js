/**
 * Manages the "image below the link" UI on news article forms:
 *
 *   - Toggle "show image" → expand/collapse the body
 *   - Source radio (PDF / custom upload) → swap the action button
 *   - "Generate from PDF" button:
 *       1. Reads the linked PDF URL from the matching link-media-picker wrapper
 *       2. Renders page 1 with PDF.js to a canvas
 *       3. Converts to a JPEG blob
 *       4. Uploads via /admin/media/upload (the same endpoint the picker
 *          uses for library uploads)
 *       5. Wires the returned media id into the hidden image input and
 *          updates the preview
 *   - "Clear" button → reset both the FK input and the preview
 *
 * Also reacts to picker:select events whose field is an
 * `_image_media_id` slot (custom-image picks).
 */

import { ajax } from '../../ajax.js';
import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

const HUMAN = (bytes) => {
    if (!bytes || bytes < 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let v = Number(bytes);
    while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
    return `${v.toFixed(v < 10 && i > 0 ? 2 : 0)} ${units[i]}`;
};

function findWrapper(target) {
    return target?.closest('[data-link-image-wrapper]') ?? null;
}

function showWhen(el, cond) {
    if (!el) return;
    el.style.display = cond ? '' : 'none';
}

function setStatus(wrapper, text, kind = 'info') {
    const status = wrapper.querySelector('[data-link-image-status]');
    if (!status) return;
    status.textContent = text || '';
    status.classList.remove('text-danger', 'text-success', 'text-gray');
    status.classList.add(
        kind === 'error'   ? 'text-danger'
        : kind === 'success' ? 'text-success'
        : 'text-gray'
    );
}

function applySourceVisibility(wrapper) {
    const radio = wrapper.querySelector('[data-link-image-source-radio]:checked');
    const source = radio?.value ?? 'pdf';
    showWhen(wrapper.querySelector('[data-link-image-generate]'), source === 'pdf');
    showWhen(wrapper.querySelector('[data-link-image-pick]'), source === 'custom');
}

function applyShowToggle(wrapper) {
    const showField = wrapper.dataset.showField;
    if (!showField) return;
    // The "show image" toggle is rendered via <x-admin.field.radio-switch>,
    // which produces TWO radio inputs (value=0 and value=1) sharing the same
    // name. We treat it as "shown" when the active (value=1) radio is checked.
    const activeRadio = document.querySelector(`input[name="${showField}"][value="1"]:checked`);
    const body = wrapper.querySelector('[data-link-image-body]');
    showWhen(body, !!activeRadio);
}

function setSelectedImage(wrapper, media) {
    const input   = wrapper.querySelector('[data-link-image-input]');
    const preview = wrapper.querySelector('[data-link-image-preview]');

    if (input) input.value = media?.id ?? '';

    if (preview) {
        if (media) {
            preview.innerHTML = '';
            const span = document.createElement('span');
            span.className = 'd-inline-flex align-items-center gap-2';

            if (media.url) {
                const img = document.createElement('img');
                img.src = media.url;
                img.alt = '';
                img.style.cssText = 'height: 36px; width: 48px; object-fit: cover; border-radius: 4px; background: #eee;';
                span.appendChild(img);
            }

            const name = document.createElement('span');
            name.className = 'fw-semibold';
            name.textContent = media.file_name || media.name || '';
            span.appendChild(name);

            if (media.size) {
                const size = document.createElement('span');
                size.className = 'text-gray small';
                size.textContent = `(${HUMAN(media.size)})`;
                span.appendChild(size);
            }

            preview.appendChild(span);
        } else {
            preview.innerHTML = `<span class="text-gray">${preview.dataset.placeholder ?? '—'}</span>`;
        }
    }
}

async function renderPdfFirstPage(pdfUrl) {
    const loadingTask = pdfjsLib.getDocument({ url: pdfUrl, withCredentials: true });
    const pdf  = await loadingTask.promise;
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 2 });
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width  = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvasContext: ctx, viewport }).promise;
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => blob ? resolve(blob) : reject(new Error('canvas.toBlob returned null')),
            'image/jpeg',
            0.92,
        );
    });
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function uploadBlob(blob, filename) {
    const fd = new FormData();
    fd.append('file', blob, filename);
    fd.append('_token', csrfToken());

    return new Promise((resolve, reject) => {
        ajax(null, {
            url: '/admin/media/upload',
            method: 'post',
            params: fd,
            successHandler: (response) => {
                if (response.data?.media) resolve(response.data.media);
                else reject(new Error('upload returned no media'));
            },
            errorHandler: (error) => reject(error),
        });
    });
}

export function linkImageGenerator() {
    // Initial render: apply source/show state to every image field on the page.
    document.querySelectorAll('[data-link-image-wrapper]').forEach((wrapper) => {
        applyShowToggle(wrapper);
        applySourceVisibility(wrapper);
    });

    // "Show image" radio-switch
    document.addEventListener('change', (event) => {
        const input = event.target;
        if (!input || !input.name || input.tagName !== 'INPUT') return;
        const wrapper = document.querySelector(
            `[data-link-image-wrapper][data-show-field="${input.name}"]`
        );
        if (!wrapper) return;
        applyShowToggle(wrapper);
    });

    // Source radio
    document.addEventListener('change', (event) => {
        const radio = event.target.closest('[data-link-image-source-radio]');
        if (!radio) return;
        const wrapper = findWrapper(radio);
        if (!wrapper) return;
        applySourceVisibility(wrapper);
    });

    // Generate from PDF
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-link-image-generate]');
        if (!button) return;

        const wrapper = findWrapper(button);
        if (!wrapper) return;

        // Find the matching link-media-picker wrapper to read the PDF URL.
        const pdfSourceField = wrapper.dataset.pdfSourceField;
        const pdfWrapper = pdfSourceField
            ? document.querySelector(`[data-link-media-field="${pdfSourceField}"]`)
            : null;
        const pdfUrl  = pdfWrapper?.dataset.linkMediaUrl  || '';
        const pdfMime = pdfWrapper?.dataset.linkMediaMime || '';

        if (!pdfUrl) {
            setStatus(wrapper, button.dataset.statusNoPdf || 'Bitte zuerst eine PDF im Link-Picker auswählen.', 'error');
            return;
        }

        if (pdfMime && pdfMime !== 'application/pdf') {
            setStatus(wrapper, button.dataset.statusNotPdf || 'Die ausgewählte Datei ist keine PDF.', 'error');
            return;
        }

        const label = button.querySelector('[data-link-image-generate-label]');
        const originalLabel = label?.textContent;
        button.disabled = true;
        if (label) label.textContent = button.dataset.statusGenerating || 'Generiere…';
        setStatus(wrapper, button.dataset.statusGenerating || 'Generiere Vorschau aus PDF…');

        try {
            const blob = await renderPdfFirstPage(pdfUrl);
            const filename = `pdf-cover-${Date.now()}.jpg`;
            const media = await uploadBlob(blob, filename);
            setSelectedImage(wrapper, media);
            setStatus(wrapper, button.dataset.statusDone || 'Vorschau-Bild erzeugt.', 'success');
        } catch (e) {
            console.error('[linkImageGenerator]', e);
            setStatus(wrapper, button.dataset.statusFailed || 'Konnte Vorschau nicht erzeugen.', 'error');
        } finally {
            button.disabled = false;
            if (label && originalLabel) label.textContent = originalLabel;
        }
    });

    // Clear
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-link-image-clear]');
        if (!button) return;
        const wrapper = findWrapper(button);
        if (!wrapper) return;
        setSelectedImage(wrapper, null);
        setStatus(wrapper, '');
    });

    // Custom-image pick result (from media picker)
    document.addEventListener('picker:select', (event) => {
        const { id, name, file_name, size, url, mime_type, field } = event.detail || {};
        if (!field || !field.endsWith('_image_media_id')) return;

        const wrapper = document.querySelector(
            `[data-link-image-wrapper][data-media-field="${field}"]`
        );
        if (!wrapper) return;

        setSelectedImage(wrapper, {
            id, name, file_name, size, url, mime_type,
        });
        setStatus(wrapper, '');
    });
}
