import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// Ejecuta el objeto window.AppAlert real de cada layout con un Swal falso que captura sus argumentos.
const layouts = ['admin', 'catequista', 'coordinador_comunidades', 'coordinador_general', 'parroco'];

function appAlert(layout) {
    const source = readFileSync(new URL(`../../resources/views/layouts/app_parroquia_${layout}.blade.php`, import.meta.url), 'utf8');
    const declaration = source.indexOf('window.AppAlert =');
    assert.notEqual(declaration, -1, `${layout}: falta window.AppAlert`);
    // Primera llave tras la declaración: «window.AppAlert = {» o «window.AppAlert = window.AppAlert || {».
    const start = source.indexOf('{', declaration);
    let depth = 0;
    let end = start;
    for (; end < source.length; end++) {
        if (source[end] === '{') depth++;
        if (source[end] === '}' && --depth === 0) break;
    }
    const calls = [];
    const Swal = { fire: (options) => { calls.push(options); return Promise.resolve({ isConfirmed: true }); } };
    const object = vm.runInNewContext(`(${source.slice(start, end + 1)})`, { window: { Swal }, Swal, alert() {}, Promise });

    return { object, calls };
}

for (const layout of layouts) {
    test(`${layout}: los mensajes de validación se muestran como texto y conservan el formato`, async () => {
        const { object, calls } = appAlert(layout);
        await object.validation({ calificaciones: ['<svg/onload=alert(1)>', 'Nombre <b>Juan</b> & "x" \'y\''] });

        const { html, text } = calls.at(-1);
        assert.equal(text, undefined);
        assert.ok(!html.includes('<svg'), 'no debe insertar la etiqueta svg');
        assert.ok(!html.includes('<b>'), 'no debe insertar la etiqueta b');
        assert.ok(html.includes('&lt;svg/onload=alert(1)&gt;'));
        assert.ok(html.includes('Nombre &lt;b&gt;Juan&lt;/b&gt; &amp; &quot;x&quot; &#39;y&#39;'));
        // Formato visual intacto: contenedor, una línea con viñeta por error.
        assert.ok(html.startsWith('<div style="text-align:left;font-size:12.5px;line-height:1.35;">'));
        assert.equal(html.split('<div style="margin-bottom:4px;">• ').length - 1, 2);
        assert.ok(html.endsWith('</div></div>'));
    });

    test(`${layout}: éxito y error siguen usando text`, async () => {
        const { object, calls } = appAlert(layout);
        await object.success('<b>hecho</b>');
        await object.error('<b>falló</b>');

        assert.equal(calls[0].text, '<b>hecho</b>');
        assert.equal(calls[0].html, undefined);
        assert.equal(calls[1].text, '<b>falló</b>');
        assert.equal(calls[1].title, 'Error');
        assert.equal(calls[1].html, undefined);
    });
}

test('admin: confirmAction muestra título y texto como texto, nunca como HTML', async () => {
    const { object, calls } = appAlert('admin');
    const name = "Ana');alert(1);//\"<script>alert(2)</script>";
    const result = await object.confirmAction({ title: '¿Bloquear la cuenta?', text: `¿Bloquear a ${name}?`, confirmText: 'Bloquear', danger: true });

    assert.equal(result.isConfirmed, true);
    assert.equal(calls[0].title, '¿Bloquear la cuenta?');
    assert.equal(calls[0].text, `¿Bloquear a ${name}?`);
    assert.equal(calls[0].html, undefined);
    assert.equal(calls[0].confirmButtonText, 'Bloquear');
    assert.equal(calls[0].icon, 'warning');
});
