const html = document.documentElement;
const TOGGLE_CLASS = 'is-opened-main-menu';

export function toggleMenu() {
    // Single delegated click handler on document — works regardless of when
    // the toggle button is added to the DOM and survives re-renders. Avoids
    // the stale-element trap of binding directly to #main-menu-toggle-button
    // at module-init time.
    document.addEventListener('click', (event) => {
        const toggleButton = event.target.closest('#main-menu-toggle-button');

        if (toggleButton) {
            html.classList.toggle(TOGGLE_CLASS);
            return;
        }

        // Click was elsewhere — close the menu unless it landed inside the
        // open menu itself (e.g. on a nav link, which the browser handles
        // via the link's own navigation).
        if (event.target.closest('#main-menu')) return;

        html.classList.remove(TOGGLE_CLASS);
    });

    window.addEventListener('scroll', () => {
        html.classList.remove(TOGGLE_CLASS);
    });
}
