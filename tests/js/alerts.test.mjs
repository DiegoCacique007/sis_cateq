import { test } from 'node:test';
import assert from 'node:assert/strict';
import { installAlerts } from '../../resources/js/alerts-core.js';

function setup() {
    const calls = [];
    const swal = { fire: config => { calls.push(config); return Promise.resolve({ isConfirmed: false }); } };
    return { calls, swal, alerts: installAlerts(swal) };
}
test('notifications use shared top styling and expected durations', async () => {
    const { alerts, calls } = setup();
    await alerts.success('Guardado');
    await alerts.info('Periodo actualizado');
    await alerts.warning('Revisa los datos');
    assert.deepEqual(calls.map(c => c.timer), [2500, 3000, 4000]);
    for (const c of calls) {
        assert.equal(c.toast, true); assert.equal(c.position, 'top');
        assert.equal(c.background, '#1E293B'); assert.equal(c.width, 310);
        assert.equal(c.showConfirmButton, false); assert.equal(c.showCloseButton, true);
    }
});
test('errors and all validation messages persist and use text rather than HTML', async () => {
    const { alerts, calls, swal } = setup();
    await alerts.validation({ nombre: ['<script>dato</script>'], correo: ['Correo incorrecto'] });
    await swal.fire({ icon: 'error', text: 'Error importante', timer: 4000 });
    assert.equal(calls[0].text, '<script>dato</script>\nCorreo incorrecto');
    for (const c of calls) { assert.equal(c.timer, undefined); assert.equal(c.html, undefined); }
});
test('critical confirmations stay modal, require a choice and preserve cancellation', async () => {
    const { alerts, calls } = setup();
    const result = await alerts.confirmDelete('Eliminar alumno');
    assert.equal(result.isConfirmed, false);
    await alerts.confirmAction({ title: 'Bloquear', text: 'Usuario', danger: true });
    for (const c of calls) {
        assert.equal(c.toast, false); assert.equal(c.position, 'center');
        assert.equal(c.width, 350); assert.equal(c.showCancelButton, true);
        assert.equal(c.timer, undefined); assert.equal(c.allowOutsideClick, false);
        assert.equal(c.focusCancel, true);
    }
});
test('queued alerts do not replace each other and duplicate notifications share a result', async () => {
    const calls = [], resolvers = [];
    const swal = { fire: config => { calls.push(config); return new Promise(resolve => resolvers.push(resolve)); } };
    const alerts = installAlerts(swal);
    const first = alerts.error('Error');
    assert.equal(alerts.error('Error'), first);
    const second = alerts.confirmLogout();
    await Promise.resolve();
    assert.equal(calls.length, 1);
    resolvers[0]({ isConfirmed: false });
    await first; await Promise.resolve();
    assert.equal(calls.length, 2);
    resolvers[1]({ isConfirmed: true });
    assert.equal((await second).isConfirmed, true);
});
test('legacy Swal calls retain operation callbacks and safe confirmation behavior', async () => {
    const { swal, calls } = setup();
    const preConfirm = () => 'resultado';
    await swal.fire({ preConfirm, showCancelButton: true, width: 900, position: 'top-end', timer: 10 });
    assert.equal(calls[0].preConfirm, preConfirm);
    assert.equal(calls[0].width, 350);
    assert.equal(calls[0].timer, undefined);
});
