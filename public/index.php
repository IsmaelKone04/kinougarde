<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil — kiNouGarde</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/accueil.css">
    <script src="assets/js/menu.js" defer></script>
</head>
<body class="marque">
<header class="topbar">
    <a href="index.php" class="brand"><span class="brand-mark">🍼</span> kiNouGarde</a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰ Menu</button>
    <!-- Des liens, plus des <select onchange="location = this.value">. Les deux
         listes déroulantes d'origine n'étaient pas navigables au clavier, et celle
         de la connexion proposait deux entrées menant à la même page. -->
    <nav class="menu" id="menu">
        <ul>
            <li><a href="inscription-parents.php">Inscription parent</a></li>
            <li><a href="inscription-nounou.php">Inscription nounou</a></li>
            <li><a href="login.php">Se connecter</a></li>
        </ul>
    </nav>
</header>

<section class="hero">
    <span class="eyebrow" style="color:#fff;">Garde d'enfants en toute confiance</span>
    <h1>Trouvez la nounou qu'il vous faut</h1>
    <p class="hero-tagline">Des profils vérifiés, une messagerie intégrée et un suivi simple des contrats — pour les parents comme pour les nounous.</p>
    <div class="hero-actions">
        <a href="inscription-parents.php" class="btn btn-accent">Je suis un parent</a>
        <a href="inscription-nounou.php" class="btn btn-ghost-light">Je suis une nounou</a>
    </div>
</section>

<section class="services">
    <h2>Nos services</h2>
    <p>kiNouGarde est votre solution pour trouver des nounous fiables et qualifiées pour prendre soin de vos enfants. Nous offrons :</p>
    <div class="service-grid">
        <div class="service-item">
            <span class="service-icon">🔎</span>
            <p>Un vaste réseau de nounous vérifiées et évaluées.</p>
        </div>
        <div class="service-item">
            <span class="service-icon">🎯</span>
            <p>Des outils de recherche pour trouver la nounou qui correspond à vos besoins.</p>
        </div>
        <div class="service-item">
            <span class="service-icon">💬</span>
            <p>Une messagerie intégrée pour échanger directement avec les familles ou les nounous.</p>
        </div>
        <div class="service-item">
            <span class="service-icon">📋</span>
            <p>Un suivi clair des contrats et des montants dus.</p>
        </div>
    </div>
</section>

<!-- Témoignages fictifs : la plateforme n'a jamais été mise en service.
     Ils illustrent la maquette, ils n'attestent de rien. -->
<section class="testimonials">
    <h2>Témoignages <em>(exemples fictifs)</em></h2>
    <div class="testimonial-grid">
        <?php
        $temoignages = [
            ['Marie',   "J'ai trouvé une nounou incroyable grâce à kiNouGarde ! Je recommande vivement cette plateforme.", 5],
            ['Pierre',  "Le service client de kiNouGarde est exceptionnel. Ils ont répondu à toutes mes questions rapidement.", 4],
            ['Sophie',  "Grâce à kiNouGarde, j'ai trouvé une famille formidable à qui confier mes enfants. Merci pour cette belle expérience !", 5],
            ['Thomas',  "kiNouGarde m'a aidé à trouver une nounou compétente et de confiance pour mes enfants.", 4],
            ['Julie',   "Je suis reconnaissante envers kiNouGarde pour m'avoir aidé à trouver une solution de garde flexible et abordable.", 3],
            ['Nicolas', "Merci kiNouGarde pour votre professionnalisme et votre soutien tout au long de la recherche.", 3],
        ];
        foreach ($temoignages as [$auteur, $texte, $note]):
        ?>
            <div class="testimonial">
                <?= avatar_html($auteur, '', null, 'sm') ?>
                <div class="testimonial-body">
                    <p>&laquo; <?= e($texte) ?> &raquo;</p>
                    <strong><?= e($auteur) ?></strong>
                    <span class="stars"><?= str_repeat('★', $note) . str_repeat('☆', 5 - $note) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<footer class="site-footer site-footer-clair">
    <p>kiNouGarde est la plateforme idéale pour trouver la garde d'enfants dont vous avez besoin. Rejoignez-nous dès aujourd'hui !</p>
    <p>&copy; 2024 kiNouGarde. Projet étudiant, à but non commercial.</p>
</footer>
</body>
</html>
