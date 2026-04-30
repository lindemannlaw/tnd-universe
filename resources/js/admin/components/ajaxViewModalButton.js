import * as bootstrap from 'bootstrap';

import { ajax } from '../../ajax.js';

import { select } from './select.js';
import { fields } from '../fields/fields.js';
import { wysiwyg } from "./wysiwyg.js";
import { projectDescriptionBlocks } from "./projectDescriptionBlocks.js";
import { translateBlocks } from "./translateBlocks.js";

export function ajaxViewModalButton() {
    document.addEventListener('click', event => {
        const button = event?.target?.closest('[data-ajax-view-modal-button]');

        if (!button) return;

        const targetModal = document.getElementById(button.dataset.modal);
        const url = button.dataset.action;
        // Default 1: existing call-sites keep the old "close all modals first"
        // behaviour. Pass data-with-hide-modals="0" to stack the new modal on
        // top of any modal already open (e.g. the media picker over an
        // article-edit modal).
        const withHideModals = button.dataset.withHideModals === undefined
            ? 1
            : +button.dataset.withHideModals;

        ajax(event, {
            submitter: button,
            url: url,
            method: 'get',
            successHandler: (response) => {
                if (withHideModals) hideAllModals();
                showModal(targetModal, response.data);
            },
            errorHandler: (error) => {
                console.log(error);
            }
        });
    });
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

function showModal(targetModal, html = null) {
    if (!targetModal || !html) return;

    targetModal.innerHTML = html;

    updateSyncLibs();

    let modal = bootstrap.Modal.getInstance(targetModal);

    if (modal?.show) {
        modal.show();

        return;
    }

    modal = new bootstrap.Modal(targetModal);

    modal.show();
}

function updateSyncLibs() {
    select();
    fields();
    wysiwyg();
    projectDescriptionBlocks();
    translateBlocks();
}
