if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Sin service worker igual se navega bien; solo se pierde la instalación PWA.
        });
    });
}