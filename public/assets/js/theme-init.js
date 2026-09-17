/**
 * Applique le thème sauvegardé avant le premier rendu, pour éviter un
 * flash de la version claire suivi d'un bascule vers le mode sombre.
 * Chargé sans `defer`, juste après base.css : il doit s'exécuter avant
 * que le navigateur ne peigne la page.
 */
(function () {
    try {
        var theme = localStorage.getItem('kg-theme');
        if (theme === 'dark' || theme === 'light') {
            document.documentElement.setAttribute('data-theme', theme);
        }
    } catch (e) {
        // Stockage indisponible (navigation privée, etc.) : on reste sur
        // la préférence système via prefers-color-scheme.
    }
})();
