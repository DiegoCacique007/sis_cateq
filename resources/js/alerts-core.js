export function installAlerts(Swal) {
    const fire = Swal.fire.bind(Swal);
    let tail = Promise.resolve();
    const pending = new Map();
    Swal.fire = (...args) => {
        const options = typeof args[0] === 'object' ? { ...args[0] } : { title: args[0], html: args[1], icon: args[2] };
        const confirmation = Boolean(options.showCancelButton || options.showDenyButton || options.input || options.preConfirm || options.icon === 'question');
        const persistent = confirmation || options.icon === 'error' || options.icon === 'warning' && options.timer !== 4000;
        const key = confirmation ? null : JSON.stringify([options.icon, options.title, options.text, options.html]);
        if (key && pending.has(key)) return pending.get(key);
        const config = {
            ...options,
            toast: !confirmation, position: confirmation ? 'center' : 'top',
            width: confirmation ? 350 : 310, padding: '12px', background: '#1E293B', color: '#fff',
            showCloseButton: true, closeButtonAriaLabel: 'Cerrar aviso',
            timer: persistent ? undefined : ({ success: 2500, info: 3000, warning: 4000 }[options.icon] || 3000),
            timerProgressBar: !persistent, heightAuto: false,
            customClass: { ...options.customClass, popup: confirmation ? 'app-confirm' : 'app-notice' },
            showClass: { popup: 'app-alert-enter' }, hideClass: { popup: 'app-alert-leave' },
        };
        if (confirmation) {
            Object.assign(config, { timer: undefined, timerProgressBar: false, allowOutsideClick: false, focusCancel: true });
        } else {
            config.showConfirmButton = false;
        }
        const result = tail.then(() => fire(config));
        tail = result.catch(() => {});
        if (key) {
            pending.set(key, result);
            result.then(() => pending.delete(key), () => pending.delete(key));
        }
        return result;
    };
    const notify = (icon, title, text, timer) => Swal.fire({ icon, title, text, timer, showConfirmButton: false });
    const confirmAction = ({ title = 'Confirmar acción', text = '', confirmText = 'Confirmar', danger = false } = {}) => Swal.fire({
        icon: danger ? 'warning' : 'question', title, text, showCancelButton: true,
        confirmButtonText: confirmText, cancelButtonText: 'Cancelar', reverseButtons: true,
        confirmButtonColor: danger ? '#b94048' : '#2563a6', cancelButtonColor: '#475569',
    });
    return {
        success: (message = 'Operación realizada correctamente.', title = 'Registro guardado') => notify('success', title, message),
        error: (message = 'Verifica la información e inténtalo nuevamente.', title = 'No se pudo completar') => notify('error', title, message),
        info: (message, title = 'Información') => notify('info', title, message),
        warning: (message, title = 'Atención') => notify('warning', title, message, 4000),
        validation: errors => notify('warning', 'Validación requerida', Object.values(errors).flat().join('\n')),
        confirmAction,
        confirmDelete: (text = 'Esta acción eliminará el registro seleccionado.') => confirmAction({ title: 'Confirmar eliminación', text, confirmText: 'Eliminar', danger: true }),
        confirmSave: (text = 'Se guardará la información capturada.') => confirmAction({ title: 'Confirmar guardado', text, confirmText: 'Guardar' }),
        confirmLogout: () => confirmAction({ title: '¿Cerrar sesión?', text: '¿Está seguro que desea salir del sistema?', confirmText: 'Sí, salir', danger: true }),
    };
}
