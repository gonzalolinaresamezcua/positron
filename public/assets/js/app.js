(() => {
    const stars = document.querySelector('.pg-stars');
    if (stars && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        let t = 0;
        const tick = () => {
            t += 0.004;
            stars.style.transform = `translateY(${Math.sin(t) * 6}px)`;
            requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }
})();
