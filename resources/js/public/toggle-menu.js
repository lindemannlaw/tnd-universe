const html = document.documentElement;

export function toggleMenu() {
    const toggleButton = document.getElementById('main-menu-toggle-button');

    if (!toggleButton) return;

    const toggleClassname = 'is-opened-main-menu';

    toggleButton.addEventListener('click', () => {
        html.classList.toggle(toggleClassname);
    });

    document.addEventListener('click', event => {
        const targetMenu = event?.target?.closest('#main-menu');
        const targetToggleButton = event?.target?.closest('#main-menu-toggle-button');

        if (targetMenu || targetToggleButton) return;

        html.classList.remove(toggleClassname);
    });

    window.addEventListener('scroll', () => {
        html.classList.remove(toggleClassname);
    });
}
