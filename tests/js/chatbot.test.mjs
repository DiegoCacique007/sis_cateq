import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// DOM mínimo para comprobar eventos/protocolo; no sustituye una revisión visual en navegador.
class Element {
    constructor() { this.children = []; this.events = {}; this.hidden = true; this.value = ''; this.disabled = false; }
    addEventListener(name, fn) { this.events[name] = fn; }
    setAttribute(name, value) { this[name] = value; }
    focus() { this.focused = true; }
    append(child) { this.children.push(child); child.parent = this; }
    remove() { this.parent.children = this.parent.children.filter((child) => child !== this); }
    get firstElementChild() { return this.children[0]; }
    set innerHTML(_) { throw new Error('Unsafe HTML insertion'); }
}

function setup(fetch) {
    const root = new Element();
    const launch = new Element();
    const close = new Element();
    const panel = new Element();
    const log = new Element();
    const form = new Element();
    const input = new Element();
    const progress = new Element();
    const submit = new Element();
    root.dataset = { endpoint: '/chatbot/message', csrf: 'test-csrf' };
    const nodes = { '.cateq-chat-launch': launch, '[data-close]': close, '.cateq-chat-panel': panel, '.cateq-chat-log': log, form, '[data-progress]': progress };
    root.querySelector = (selector) => nodes[selector];
    root.querySelectorAll = () => [submit, ...log.children.flatMap((bubble) => bubble.children.filter((child) => child.type === 'button'))];
    form.querySelector = () => input;
    const document = { querySelectorAll: () => [root], createElement: () => new Element() };
    const source = readFileSync(new URL('../../resources/js/chatbot.js', import.meta.url), 'utf8').replace(/^import .*;\r?\n/, '');
    vm.runInNewContext(source, { document, fetch, AbortController, setTimeout, clearTimeout, URL, window: { location: { href: 'http://localhost/dashboard', origin: 'http://localhost' } } });
    return { root, launch, close, panel, log, form, input, progress, submit };
}
const settle = () => new Promise((resolve) => setImmediate(resolve));
const send = (ui, text) => { ui.input.value = text; ui.form.events.submit({ preventDefault() {} }); };

test('abre, cierra y reabre conservando mensajes solo en la página', async () => {
    const ui = setup(async () => ({ json: async () => ({ message: 'Respuesta', data: {}, actions: [] }) }));
    ui.launch.events.click();
    assert.equal(ui.panel.hidden, false);
    send(ui, 'ayuda');
    await settle();
    ui.close.events.click();
    assert.equal(ui.panel.hidden, true);
    ui.launch.events.click();
    assert.equal(ui.panel.hidden, false);
    assert.equal(ui.log.children.at(-1).textContent, 'Respuesta');
});

test('selección reenvía el mensaje original y solo assignment_id, con CSRF', async () => {
    const calls = [];
    const ui = setup(async (url, options) => {
        calls.push({ url, options });
        return { json: async () => ({ status: 'selection_required', message: 'Selecciona', data: { options: [{ id: 7, label: 'Grupo A' }] } }) };
    });
    send(ui, 'mis alumnos');
    await settle();
    ui.log.children.at(-1).children[0].events.click();
    await settle();
    assert.deepEqual(JSON.parse(calls[1].options.body), { message: 'mis alumnos', assignment_id: 7 });
    assert.equal(calls[1].options.headers['X-CSRF-TOKEN'], 'test-csrf');
    assert.equal(calls[1].options.credentials, 'same-origin');
});

test('bloquea doble envío y recupera controles después de fallo de red', async () => {
    let calls = 0;
    let reject;
    const ui = setup(() => { calls++; return new Promise((_, fail) => { reject = fail; }); });
    send(ui, 'ayuda');
    send(ui, 'otra consulta');
    assert.equal(calls, 1);
    assert.equal(ui.submit.disabled, true);
    assert.equal(ui.progress.hidden, false);
    reject(new Error('network'));
    await settle();
    assert.equal(ui.submit.disabled, false);
    assert.equal(ui.progress.hidden, true);
    assert.match(ui.log.children.at(-1).textContent, /Comprueba la conexión/);
});

test('renderiza texto malicioso como texto y rechaza navegación externa', async () => {
    const malicious = '<img src=x onerror=alert(1)>';
    const ui = setup(async () => ({ json: async () => ({ message: malicious, data: { items: [malicious] }, actions: [{ type: 'navigate', url: 'https://external.invalid', label: 'Salir' }] }) }));
    send(ui, malicious);
    await settle();
    const bubble = ui.log.children.at(-1);
    assert.equal(bubble.textContent, malicious);
    assert.equal(bubble.children[0].children[0].textContent, malicious);
    assert.equal(bubble.children.length, 1);
});

test('muestra mensajes de sesión caducada o rate limit sin internals', async () => {
    const ui = setup(async () => ({ status: 419, json: async () => ({ status: 'error', code: 'SESSION_EXPIRED', message: 'Recarga la página.', data: {}, actions: [] }) }));
    send(ui, 'ayuda');
    await settle();
    assert.equal(ui.log.children.at(-1).textContent, 'Recarga la página.');
});
