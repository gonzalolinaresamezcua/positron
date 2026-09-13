(() => {
    document.querySelectorAll('form[action*="cancelar"]').forEach((form) => {
        form.addEventListener('submit', (ev) => {
            if (!confirm('¿Confirmas la cancelación de esta suscripción?')) {
                ev.preventDefault();
            }
        });
    });
})();
