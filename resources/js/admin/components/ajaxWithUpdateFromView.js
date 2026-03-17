import * as bootstrap from "bootstrap";

import { ajax } from '../../ajax.js';
import { select } from './select.js';
import { fields } from '../fields/fields.js';
import { wysiwyg } from './wysiwyg.js';
import { projectDescriptionBlocks } from './projectDescriptionBlocks.js';

export function ajaxWithUpdateFromView() {
    document.addEventListener('submit', event => {
        const form = event?.target?.closest('[data-ajax-with-update-from-view]');

        if (!form) return;

        const updateSection = document.getElementById(form.dataset.updateIdSection);

        ajax(event, {
            form: form,
            successHandler: (response) => {
                if (!form.hasAttribute('data-keep-modal-open')) {
                    hideAllModals();
                    updateSection.innerHTML = response.data?.html;
                    return;
                }

                // Update the list in background
                if (updateSection) {
                    updateSection.innerHTML = response.data?.html;
                }

                // Re-fetch the edit view so the modal shows the server-synced data
                const refreshUrl = form.dataset.modalRefreshUrl;
                if (refreshUrl) {
                    const modalEl = form.closest('.modal');

                    fetch(refreshUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Modal-Refresh': '1',
                        },
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (modalEl && data?.html) {
                                reloadModal(modalEl, data.html);
                            }
                        })
                        .catch(e => console.error('[ajaxWithUpdateFromView] modal refresh failed', e));
                }
            },
            errorHandler: (error) => {
                console.error(error);
            }
        });
    });
}

function reloadModal(modalEl, html) {
    // Preserve Bootstrap modal instance & visibility while replacing content
    const isShown   = modalEl.classList.contains('show');
    const activeTab = modalEl.querySelector('.nav-link.active')?.dataset.bsTarget;

    modalEl.innerHTML = html;

    updateSyncLibs();

    // Restore the previously active tab if possible
    if (activeTab) {
        const tab = modalEl.querySelector(`[data-bs-target="${activeTab}"]`);
        if (tab) {
            bootstrap.Tab.getOrCreateInstance(tab).show();
        }
    }

    // Re-attach Bootstrap modal if needed
    if (isShown && !bootstrap.Modal.getInstance(modalEl)) {
        new bootstrap.Modal(modalEl).show();
    }
}

function hideAllModals() {
    const modals = document.querySelectorAll('.modal.show');

    modals.forEach(modalEl => {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        } else {
            new bootstrap.Modal(modalEl).hide();
        }
    });
}

function updateSyncLibs() {
    select();
    fields();
    wysiwyg();
    projectDescriptionBlocks();
}
