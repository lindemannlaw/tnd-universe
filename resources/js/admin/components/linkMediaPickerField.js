/**
 * Receives `picker:select` CustomEvents fired by the media-picker modal and
 * updates the matching link-media-picker-field on the article-edit form:
 *  - writes the chosen media id into the hidden input
 *  - re-renders the preview block (filename + human-readable size)
 *
 * Also handles the "Clear" button which wipes the FK back to NULL.
 */

const HUMAN = (bytes) => {
    if (!bytes || bytes < 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let v = Number(bytes);
    while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
    return `${v.toFixed(v < 10 && i > 0 ? 2 : 0)} ${units[i]}`;
};

export function linkMediaPickerField() {
    document.addEventListener('picker:select', (event) => {
        const { id, file_name, size, field } = event.detail || {};
        if (!field) return;

        const wrapper = document.querySelector(`[data-link-media-field="${field}"]`);
        if (!wrapper) return;

        const input   = wrapper.querySelector('[data-link-media-input]');
        const preview = wrapper.querySelector('[data-preview]');

        if (input)   input.value = id ?? '';
        if (preview) {
            preview.innerHTML = `
                <span class="fw-semibold"></span>
                <span class="text-gray small"></span>
            `;
            preview.querySelector('.fw-semibold').textContent  = file_name || '';
            preview.querySelector('.text-gray.small').textContent = size ? `(${HUMAN(size)})` : '';
        }
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-link-media-clear]');
        if (!button) return;

        const wrapper = button.closest('[data-link-media-field]');
        if (!wrapper) return;

        const input   = wrapper.querySelector('[data-link-media-input]');
        const preview = wrapper.querySelector('[data-preview]');

        if (input)   input.value = '';
        if (preview) preview.innerHTML = '<span class="text-gray">—</span>';
    });
}
