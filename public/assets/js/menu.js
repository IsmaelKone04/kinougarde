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
    var fond = document.querySelector('[data-menu-overlay]');

    if (!menu || !bouton) {
        return;
    }

    function fermer() {
        menu.classList.remove('open');
        bouton.setAttribute('aria-expanded', 'false');
    }

    bouton.addEventListener('click', function () {
        var ouvert = menu.classList.toggle('open');
        bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
    });

    // Le tiroir (sidebar en mobile) se referme aussi au clic sur le fond
    // assombri derrière lui : sans ça, rien n'indique qu'il se superpose au
    // contenu plutôt que de le repousser.
    if (fond) {
        fond.addEventListener('click', fermer);
    }

    // Échap referme le menu : sans clavier, un menu en position fixed devient
    // un piège pour qui n'utilise pas la souris.
    document.addEventListener('keydown', function (evenement) {
        if (evenement.key === 'Escape' && menu.classList.contains('open')) {
            fermer();
            bouton.focus();
        }
    });
});

/**
 * Bascule mode sombre / mode clair. Le choix explicite est mémorisé dans
 * localStorage (clé "kg-theme") et relu par theme-init.js sur chaque page
 * pour rester cohérent d'une navigation à l'autre.
 */
document.addEventListener('DOMContentLoaded', function () {
    var interrupteur = document.getElementById('theme-toggle');
    if (!interrupteur) {
        return;
    }
    var libelle = document.getElementById('theme-toggle-label');

    function themeActuel() {
        var force = document.documentElement.getAttribute('data-theme');
        if (force === 'dark' || force === 'light') {
            return force;
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function rafraichirLibelle() {
        var sombre = themeActuel() === 'dark';
        interrupteur.setAttribute('aria-pressed', sombre ? 'true' : 'false');
        if (libelle) {
            libelle.textContent = sombre ? 'Mode clair' : 'Mode sombre';
        }
    }

    interrupteur.addEventListener('click', function () {
        var nouveau = themeActuel() === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', nouveau);
        try {
            localStorage.setItem('kg-theme', nouveau);
        } catch (e) {}
        rafraichirLibelle();
    });

    rafraichirLibelle();
});
