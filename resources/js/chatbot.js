import '../css/chatbot.css';

document.querySelectorAll('[data-chatbot]').forEach((root) => {
    const launch = root.querySelector('.cateq-chat-launch');
    const panel = root.querySelector('.cateq-chat-panel');
    const log = root.querySelector('.cateq-chat-log');
    const form = root.querySelector('form');
    const input = form.querySelector('input');
    const progress = root.querySelector('[data-progress]');
    let busy = false;

    function setOpen(open) {
        panel.hidden = !open;
        launch.setAttribute('aria-expanded', String(open));
        (open ? input : launch).focus();
    }
    launch.addEventListener('click', () => setOpen(panel.hidden));
    root.querySelector('[data-close]').addEventListener('click', () => setOpen(false));
    root.addEventListener('keydown', (event) => { if (event.key === 'Escape') setOpen(false); });

    function append(text, user = false) {
        const bubble = document.createElement('div');
        bubble.className = `cateq-chat-message${user ? ' is-user' : ''}`;
        bubble.textContent = text;
        log.append(bubble);
        // Historial únicamente en memoria de la página y acotado.
        while (log.children.length > 60) log.firstElementChild.remove();
        log.scrollTop = log.scrollHeight;
        return bubble;
    }

    async function send(message, assignmentId = null) {
        if (busy) return;
        busy = true;
        root.querySelectorAll('form button, .cateq-chat-log button').forEach((button) => { button.disabled = true; });
        progress.hidden = false;
        append(message, true);
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 30000);
        try {
            const payload = { message };
            if (assignmentId !== null) payload.assignment_id = assignmentId;
            const result = await fetch(root.dataset.endpoint, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
                body: JSON.stringify(payload),
            });
            const response = await result.json();
            if (typeof response.message !== 'string') throw new Error('Invalid response');
            const bubble = append(response.message);
            const items = response.data?.items;
            if (Array.isArray(items) && items.length) {
                const list = document.createElement('ul');
                for (const text of items) {
                    const item = document.createElement('li');
                    item.textContent = String(text);
                    list.append(item);
                }
                bubble.append(list);
            }
            if (response.status === 'selection_required') {
                for (const option of response.data?.options ?? []) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = option.label;
                    button.addEventListener('click', () => send(message, option.id));
                    bubble.append(button);
                }
            }
            for (const action of response.actions ?? []) {
                if (action.type !== 'navigate') continue;
                const url = new URL(action.url, window.location.href);
                if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) continue;
                const link = document.createElement('a');
                link.href = url.href;
                link.textContent = action.label;
                bubble.append(link);
            }
            if (response.meta?.hasMore) {
                const note = document.createElement('p');
                note.textContent = 'Se muestran hasta 100 resultados. Consulta el módulo para ver el resto.';
                bubble.append(note);
            }
        } catch {
            append('No pude procesar la solicitud en este momento. Comprueba la conexión e inténtalo de nuevo.');
        } finally {
            clearTimeout(timeout);
            busy = false;
            progress.hidden = true;
            root.querySelectorAll('form button, .cateq-chat-log button').forEach((button) => { button.disabled = false; });
            log.scrollTop = log.scrollHeight;
            if (!panel.hidden) input.focus();
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (busy || message.length < 2 || message.length > 2000) return;
        input.value = '';
        send(message);
    });
});
