import * as bootstrap from 'bootstrap';

import { ajax } from '../../ajax.js';

/**
 * Standalone "Upload to Library" form on the /admin/media index page.
 * On success, closes the modal and reloads the page so the new file appears
 * at the top of the list (default sort: most-recent updated_at).
 */
export function libraryUpload() {
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-library-upload]');
        if (!form) return;

        ajax(event, {
            form,
            successHandler: () => {
                const modalEl = form.closest('.modal');
                if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
                window.location.reload();
            },
            errorHandler: (error) => console.error(error),
        });
    });
}
