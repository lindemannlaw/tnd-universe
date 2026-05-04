/**
 * Receives `picker:select` CustomEvents fired by the media-picker modal and
 * updates the matching <x-admin.field.image> wrapper:
 *  - writes the chosen media id into the hidden input (named `{field}_media_id`)
 *  - swaps the preview <img src> to the picked URL
 *  - flips dropzone styling from "empty/dashed" to "filled/solid"
 *  - clears the `required` flag once an image has been picked
 */

export function imageFieldPicker() {
    document.addEventListener('picker:select', (event) => {
        const { id, url, field } = event.detail || {};
        if (!field) return;

        const wrapper = document.querySelector(`[data-image-picker-field="${field}"]`);
        if (!wrapper) return;

        const input   = wrapper.querySelector('[data-image-picker-input]');
        const picture = wrapper.querySelector('[data-pif-picture]');
        const image   = wrapper.querySelector('[data-pif-image]');
        const placeholder = wrapper.querySelector('[data-pif-compact-placeholder]');
        const isCompact = picture?.hasAttribute('data-pif-compact');

        if (input) {
            input.value = id ?? '';
            input.removeAttribute('data-image-picker-required');
            input.setAttribute('data-pif-has-image', '');
        }

        if (image && url) {
            image.src = url;
            image.classList.remove('d-none');
        }

        if (picture) {
            picture.classList.remove('border-dashed', 'p-4', 'bg-light');
            picture.classList.add('border-solid', isCompact ? 'p-1' : 'p-3', 'bg-white');
        }

        if (placeholder) {
            placeholder.classList.add('d-none');
            const changeLabel = wrapper.querySelector('[data-pif-compact-change]');
            if (!changeLabel && placeholder) {
                const span = document.createElement('span');
                span.className = 'small text-muted';
                span.setAttribute('data-pif-compact-change', '');
                span.textContent = 'Ändern';
                placeholder.insertAdjacentElement('afterend', span);
            } else {
                changeLabel?.classList.remove('d-none');
            }
        }
    });
}
