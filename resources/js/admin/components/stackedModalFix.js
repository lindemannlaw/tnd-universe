/**
 * Bootstrap 5 quirk: when an inner (stacked) modal is hidden, Bootstrap removes
 * the `modal-open` class from <body>, which breaks scroll-lock for the outer
 * modal that is still open. Re-add it as long as any modal is still visible.
 */
export function stackedModalFix() {
    document.addEventListener('hidden.bs.modal', () => {
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }
    });
}
