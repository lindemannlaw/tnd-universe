export function autoOpenEditModal() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }
}

function run() {
    const url = new URL(window.location.href);
    const editId = url.searchParams.get('edit');

    if (!editId) return;

    const button = findEditButton(editId);

    if (!button) return;

    url.searchParams.delete('edit');
    window.history.replaceState({}, '', url.pathname + (url.search ? url.search : '') + url.hash);

    button.click();
}

function findEditButton(id) {
    const buttons = document.querySelectorAll('[data-ajax-view-modal-button][data-action]');
    const suffix = `/${id}/edit`;

    for (const btn of buttons) {
        const action = btn.dataset.action || '';

        try {
            const path = new URL(action, window.location.origin).pathname;

            if (path.endsWith(suffix)) return btn;
        } catch {
            if (action.endsWith(suffix)) return btn;
        }
    }

    return null;
}
