<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$moi = exiger_connexion('parent');

$parent = parent_par_utilisateur($bdd, (int) $moi['id']);
if ($parent === null) {
    http_response_code(500);
    exit('Profil parent introuvable pour ce compte.');
}

$enfants = enfants_du_parent($bdd, (int) $parent['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord parent — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/dashboard-parent.css">
    <script src="assets/js/menu.js" defer></script>
</head>
<body>
<header>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰ Menu</button>
</header>

<h1>Tableau de bord</h1>
<p>Bienvenue, <?= e($parent['prenom'] . ' ' . $parent['nom']) ?>.</p>

<div class="menu" id="menu">
    <ul>
        <li><a href="mon-enfant.php">Mes enfants</a></li>
        <li><a href="liste-nounous.php">Trouver une nounou</a></li>
        <li><a href="index.php">Accueil</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
    </ul>
</div>

<div class="container">
    <section>
        <h2>Vos informations</h2>
        <p>Nom : <?= e($parent['prenom'] . ' ' . $parent['nom']) ?></p>
        <p>Téléphone : <?= e($parent['telephone']) ?></p>
        <p>Ville : <?= e($parent['ville']) ?></p>
        <p>Profession : <?= e($parent['profession']) ?></p>
    </section>

    <section>
        <h2>Mes enfants</h2>
        <?php if (!$enfants): ?>
            <p>Aucun enfant enregistré. <a href="mon-enfant.php">En ajouter un</a>.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($enfants as $enfant): ?>
                    <li>
                        <strong><?= e($enfant['prenom'] . ' ' . $enfant['nom']) ?></strong><br>
                        Âge : <?= (int) $enfant['age'] ?><br>
                        Allergie : <?= e($enfant['allergie'] ?: 'aucune') ?><br>
                        Besoin spécifique : <?= e($enfant['besoin_specifique'] ?: '—') ?><br>
                        <a href="modifier-enfant.php?id=<?= (int) $enfant['id'] ?>">Modifier</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<footer>
    <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
    <p>&copy; 2024 <?= e(APP_NOM) ?>. Projet étudiant, à but non commercial.</p>
</footer>
</body>
</html>
