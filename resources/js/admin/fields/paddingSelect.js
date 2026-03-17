export function paddingSelect() {
    // Select change: update hidden value input
    document.addEventListener('change', (e) => {
        const select = e.target.closest('[data-padding-select]');
        if (!select) return;

        const wrapper     = select.closest('[data-padding-select-wrapper]');
        const hiddenInput = wrapper?.querySelector('[data-padding-value]');
        const customInput = wrapper?.querySelector('[data-padding-custom-input]');

        if (!wrapper || !hiddenInput || !customInput) return;

        if (select.value === 'custom') {
            customInput.classList.remove('d-none');
            customInput.focus();
        } else {
            customInput.classList.add('d-none');
            hiddenInput.value = select.value;
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // Custom input: sync into hidden value input
    document.addEventListener('input', (e) => {
        const customInput = e.target.closest('[data-padding-custom-input]');
        if (!customInput) return;

        const wrapper     = customInput.closest('[data-padding-select-wrapper]');
        const hiddenInput = wrapper?.querySelector('[data-padding-value]');
        if (hiddenInput) {
            hiddenInput.value = customInput.value || 0;
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}
