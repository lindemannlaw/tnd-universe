import * as bootstrap from "bootstrap";

import { ajax } from '../../ajax.js';
import { select } from './select.js';
import { fields } from '../fields/fields.js';
import { wysiwyg } from './wysiwyg.js';
import { projectDescriptionBlocks } from './projectDescriptionBlocks.js';
import { translateBlocks } from './translateBlocks.js';

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
                            // #region agent log
                            if (data?.html) {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(data.html, 'text/html');
                                const colEntries = {};
                                doc.querySelectorAll('[name*="col_span"],[name*="col_start"]').forEach(f => {
                                    colEntries[f.getAttribute('name')] = f.value;
                                });
                                console.log('[debug-fb4a59 RETURNED] col values from server:', JSON.stringify(colEntries));
                            }
                            // #endregion
                            if (modalEl && data?.html) {
                                reloadModal(modalEl, data.html);

                                // #region agent log
                                const domColEntries = {};
                                modalEl.querySelectorAll('[name*="col_span"],[name*="col_start"]').forEach(f => {
                                    domColEntries[f.getAttribute('name')] = f.value;
                                });
                                console.log('[debug-fb4a59 POST-RELOAD DOM] col values after reloadModal+syncLibs:', JSON.stringify(domColEntries));
                                // #endregion
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
    const isShown = modalEl.classList.contains('show');

    // Capture all currently active pill/tab targets (outer + inner tabs)
    const activeTabs = [...modalEl.querySelectorAll('[data-bs-toggle="pill"].active, [data-bs-toggle="tab"].active')]
        .map(el => el.dataset.bsTarget)
        .filter(Boolean);

    modalEl.innerHTML = html;

    updateSyncLibs();

    // Restore every previously active tab
    activeTabs.forEach(target => {
        const tab = modalEl.querySelector(`[data-bs-target="${target}"]`);
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
    });

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
    translateBlocks();
}
