/**
 * Apparition progressive des fiches de la liste des nounous.
 *
 * Les fiches partent à opacity: 0 dans la feuille de style. Si ce script ne
 * s'exécute pas, elles resteraient invisibles — d'où le repli en fin de
 * fichier, qui les affiche immédiatement plutôt que de laisser une page vide.
 */
document.addEventListener('DOMContentLoaded', function () {
    var fiches = document.querySelectorAll('.profile');

    // Respecte le réglage système « réduire les animations ».
    var animationsReduites =
        window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    fiches.forEach(function (fiche, index) {
        if (animationsReduites) {
            fiche.classList.add('show');
            return;
        }
        setTimeout(function () {
            fiche.classList.add('show');
        }, index * 150);
    });
});
