import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import '../css/alerts.css';
import { installAlerts } from './alerts-core';

window.Swal = Swal;
window.AppAlert = installAlerts(Swal);

document.addEventListener('DOMContentLoaded', () => {
    const flash = document.getElementById('app-alert-flash');
    if (flash) {
        const messages = JSON.parse(flash.textContent);
        const seen = new Set();
        for (const [key, type] of [['error', 'error'], ['warning', 'warning'], ['success', 'success'], ['status', 'success'], ['info', 'info']]) {
            if (messages[key] && !seen.has(messages[key])) {
                seen.add(messages[key]);
                window.AppAlert[type](messages[key]);
            }
        }
        if (Object.keys(messages.errors).length) window.AppAlert.validation(messages.errors);
    }
    for (const [selector, method] of [['.js-delete-form', 'confirmDelete'], ['.js-confirm-save-form', 'confirmSave']]) {
        document.querySelectorAll(selector).forEach(form => form.addEventListener('submit', async event => {
            event.preventDefault();
            if ((await window.AppAlert[method](form.dataset.message || undefined)).isConfirmed) form.submit();
        }));
    }
});
