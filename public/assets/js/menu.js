/**
 * Ouverture / fermeture du menu latéral.
 *
 * Remplace la fonction toggleMenu() qui était recopiée à l'identique dans un
 * <script> en bas de trois pages, et l'attribut onclick qui l'appelait : le
 * comportement est attaché ici, une seule fois, par le fichier qui le définit.
 */
document.addEventListener('DOMContentLoaded', function () {
    var menu = document.getElementById('menu');
    var bouton = document.querySelector('.menu-toggle');

    if (!menu || !bouton) {
        return;
    }

    bouton.addEventListener('click', function () {
        var ouvert = menu.classList.toggle('open');
        bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
    });

    // Échap referme le menu : sans clavier, un menu en position fixed devient
    // un piège pour qui n'utilise pas la souris.
    document.addEventListener('keydown', function (evenement) {
        if (evenement.key === 'Escape' && menu.classList.contains('open')) {
            menu.classList.remove('open');
            bouton.setAttribute('aria-expanded', 'false');
            bouton.focus();
        }
    });
});
