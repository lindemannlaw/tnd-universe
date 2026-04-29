import qs from 'qs';
import { showAlert } from './admin/components/alerts.js';

const loadingClassName = 'sending';

export function ajax(event, {form, submitter, url = null, method = null, params = null, beforeHandler, successHandler, errorHandler, completeHandler}) {
    if (!form && (!url || !method)) return;

    event?.preventDefault();

    if (form && form?.isFormSending) return;
    if (submitter && submitter?.isSubmitterSending) return;

    url = url || getUrl(event, form);

    method = (method || form?.method || 'get').toLowerCase();

    const isGet = method === 'get';

    if (!params && form) {
        const formData = new FormData(form);

        if (isGet) {
            const queryString = new URLSearchParams(formData).toString();

            params = qs.parse(queryString);
        } else {
            params = formData;
        }
    }

    if (form) {
        form.isFormSending = true;
        form.classList.add(loadingClassName);

        if (!submitter) {
            const formId = form.id;
            submitter = formId ? document.querySelector(`[data-submit-loader][form="${formId}"]`) : null;
        }
    }

    if (submitter) {
        submitter.isSubmitterSending = true;
        submitter.classList.add(loadingClassName);
    }

    if (typeof beforeHandler === 'function') beforeHandler();

    const axiosConfig = {
        headers: {}
    };

    if (isGet) {
        axiosConfig.params = params;
    } else {
        axiosConfig.headers['Content-Type'] = 'multipart/form-data';
    }

    axios[method](url, isGet ? axiosConfig : params, isGet ? undefined : axiosConfig)
        .then(function(response) {
            const successMessage = response.data?.toast?.message;

            if (successMessage) {
                showAlert({
                    message: successMessage,
                });
            }

            if (typeof successHandler === 'function') successHandler(response);
        })
        .catch(function(error) {
            const errors = parseErrorsMessage(error?.response?.data?.errors);
            const backendError = error?.response?.data?.error;
            const message = backendError || error?.response?.data?.message || 'Request failed';

            if (typeof errorHandler === 'function') errorHandler(error);

            if (errors) {
                showAlert({
                    type: 'error',
                    message: errors,
                });

                console.error(errors);
            } else {
                showAlert({
                    type: 'error',
                    message: message,
                });

                console.error(message);
            }
        })
        .finally(() => {
            if (form) {
                const submitter = event?.submitter || document.querySelector(`button[type="submit"][form="${form.id}"]`);

                form.isFormSending = false;
                form.classList.remove(loadingClassName);
                submitter?.classList.remove(loadingClassName);
            }

            if (submitter) {
                submitter.isSubmitterSending = false;
                submitter?.classList.remove(loadingClassName);
            }

            if (typeof completeHandler === 'function') completeHandler();
        });
}

export function parseErrorsMessage(errors) {
    if (typeof errors === 'string') return errors;
    if (typeof errors != 'object') return null;

    let message = '';

    for (const [key, value] of Object.entries(errors)) {
        message += `${value}<br> `;
    }

    return message;
}

function getUrl(event, form) {
    const url = form?.getAttribute('action');

    if (url && url !== '#') return url;

    const submitter = event?.submitter;

    return submitter?.dataset?.formAction;
}
