const html = document.documentElement;
const TOGGLE_CLASS = 'is-opened-main-menu';

export function toggleMenu() {
    // Single delegated click handler on document — works regardless of when
    // the toggle button is added to the DOM and survives re-renders.
    document.addEventListener('click', (event) => {
        const toggleButton = event.target.closest('#main-menu-toggle-button');

        if (toggleButton) {
            html.classList.toggle(TOGGLE_CLASS);
            return;
        }

        // Click landed outside the toggle and outside the menu → close it.
        if (event.target.closest('#main-menu')) return;

        html.classList.remove(TOGGLE_CLASS);
    });

    // Note: no window.scroll listener that closes the menu. On iOS Safari the
    // address bar can show/hide as a synthetic scroll right after a tap on a
    // fixed-position button, which would close the menu the same instant it
    // opened. The outside-click handler above is the close path.
}
