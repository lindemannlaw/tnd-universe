import * as bootstrap from 'bootstrap';

import { ajax } from '../../ajax.js';

const PICKER_MODAL_ID = 'media-picker-modal';

export function mediaPickerModal() {
    // Pick button → fire picker:select and close picker
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-picker-pick]');
        if (!button) return;
        if (!button.closest(`#${PICKER_MODAL_ID}`)) return;

        const modalEl = document.getElementById(PICKER_MODAL_ID);
        const fieldEl = modalEl?.querySelector('[data-media-picker-modal]');
        const field   = fieldEl?.dataset.field || null;

        firePickerSelect({
            id:        button.dataset.mediaId,
            name:      button.dataset.mediaName,
            file_name: button.dataset.mediaFileName,
            size:      button.dataset.mediaSize,
            mime_type: button.dataset.mediaMime,
            field,
        });

        bootstrap.Modal.getInstance(modalEl)?.hide();
    });

    // Search form submit → AJAX, replace inner list
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-picker-search]');
        if (!form) return;
        if (!form.closest(`#${PICKER_MODAL_ID}`)) return;

        ajax(event, {
            form,
            successHandler: (response) => {
                const listEl = document.getElementById('media-picker-list');
                if (listEl) listEl.innerHTML = response.data;
                wireSortLinks();
                wirePagerLinks();
            },
            errorHandler: (error) => console.error(error),
        });
    });

    // Upload form submit → AJAX POST, on success fire picker:select with the
    // newly created media and close picker.
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-picker-upload]');
        if (!form) return;
        if (!form.closest(`#${PICKER_MODAL_ID}`)) return;

        ajax(event, {
            form,
            successHandler: (response) => {
                const m = response.data?.media;
                if (!m) return;

                const modalEl = document.getElementById(PICKER_MODAL_ID);
                const fieldEl = modalEl?.querySelector('[data-media-picker-modal]');
                const field   = fieldEl?.dataset.field || null;

                firePickerSelect({
                    id:        m.id,
                    name:      m.name,
                    file_name: m.file_name,
                    size:      m.size,
                    mime_type: m.mime_type,
                    field,
                });

                bootstrap.Modal.getInstance(modalEl)?.hide();
            },
            errorHandler: (error) => console.error(error),
        });
    });

    // Sort headers inside picker — convert anchor click to AJAX
    function wireSortLinks() {
        const modalEl = document.getElementById(PICKER_MODAL_ID);
        if (!modalEl) return;

        modalEl.querySelectorAll('thead a[href*="sort_by="]').forEach((link) => {
            if (link.dataset.pickerSortWired) return;
            link.dataset.pickerSortWired = '1';
            link.addEventListener('click', (e) => {
                e.preventDefault();
                fetchPickerList(link.href);
            });
        });
    }

    function wirePagerLinks() {
        const modalEl = document.getElementById(PICKER_MODAL_ID);
        if (!modalEl) return;

        modalEl.querySelectorAll('.pagination a[href]').forEach((link) => {
            if (link.dataset.pickerPagerWired) return;
            link.dataset.pickerPagerWired = '1';
            link.addEventListener('click', (e) => {
                e.preventDefault();
                fetchPickerList(link.href);
            });
        });
    }

    function fetchPickerList(url) {
        ajax(null, {
            url,
            method: 'get',
            successHandler: (response) => {
                const listEl = document.getElementById('media-picker-list');
                if (listEl) listEl.innerHTML = response.data;
                wireSortLinks();
                wirePagerLinks();
            },
            errorHandler: (error) => console.error(error),
        });
    }

    // After the picker modal is shown, wire its sort + pager links.
    document.addEventListener('shown.bs.modal', (event) => {
        if (event.target?.id !== PICKER_MODAL_ID) return;
        wireSortLinks();
        wirePagerLinks();
    });
}

function firePickerSelect(detail) {
    document.dispatchEvent(new CustomEvent('picker:select', { detail }));
}
