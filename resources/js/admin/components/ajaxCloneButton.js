import { ajax } from '../../ajax.js';

export function ajaxCloneButton() {
    document.addEventListener('click', event => {
        const button = event?.target?.closest('[data-clone-project]');
        if (!button) return;

        if (!confirm('Projekt duplizieren?')) return;

        const url = button.dataset.cloneUrl;
        const updateSection = button.dataset.updateIdSection;

        ajax(event, {
            submitter: button,
            url: url,
            method: 'post',
            successHandler: (response) => {
                if (updateSection && response.data?.html) {
                    const section = document.getElementById(updateSection);
                    if (section) section.innerHTML = response.data.html;
                }
            },
            errorHandler: (error) => {
                console.error('[clone]', error);
                alert('Duplizierung fehlgeschlagen');
            }
        });
    });
}
