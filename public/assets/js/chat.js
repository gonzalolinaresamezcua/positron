(() => {
    const form = document.getElementById('chat-form');
    if (!form) {
        return;
    }
    const stream = document.getElementById('chat-stream');
    const input = document.getElementById('chat-input');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const bubble = (role, text) => {
        const art = document.createElement('article');
        art.className = `pg-bubble pg-bubble-${role}`;
        const p = document.createElement('p');
        p.textContent = text;
        art.appendChild(p);
        stream.appendChild(art);
        stream.scrollTop = stream.scrollHeight;
        return p;
    };

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const text = (input.value || '').trim();
        if (!text) {
            return;
        }
        input.value = '';
        bubble('user', text);
        const pending = bubble('assistant', '…');
        const body = new URLSearchParams();
        body.set('_csrf', csrf);
        body.set('message', text);
        body.set('conversation_id', form.dataset.conversation || '');
        try {
            const res = await fetch('/chat/mensajes', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-Token': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const data = await res.json();
            if (!data.ok) {
                pending.textContent = data.error || 'No se pudo completar.';
                return;
            }
            pending.textContent = data.reply;
            if (data.conversation_id && !form.dataset.conversation) {
                form.dataset.conversation = String(data.conversation_id);
                history.replaceState(null, '', `/chat?c=${data.conversation_id}`);
            }
        } catch (err) {
            pending.textContent = 'Error de red.';
        }
    });
})();
