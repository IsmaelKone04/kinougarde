<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

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
    <link rel="stylesheet" href="parents.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 50px auto; background-color: #555; }
        header, footer, .menu {
            background-color: rgba(255, 255, 255, .1);
            backdrop-filter: blur(10px);
            color: #fff;
        }
        header { padding: 10px; display: flex; justify-content: space-between; align-items: center; }
        h1, p { text-align: center; color: #fff; margin-bottom: 20px; }
        .container {
            max-width: 700px;
            margin: 50px auto;
            background-color: rgba(0, 0, 0, .35);
            color: #fff;
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }
        .menu {
            position: fixed; top: 0; right: -250px; width: 250px; height: 100%;
            transition: all .3s ease; z-index: 1000; padding-top: 60px;
        }
        .menu.open { right: 0; }
        .menu ul { list-style: none; }
        .menu ul li { padding: 15px; border-bottom: 1px solid #555; }
        .menu ul li:hover { background-color: #555; }
        .menu ul li a { color: #fff; text-decoration: none; }
        .menu-toggle {
            background-color: rgba(255, 255, 255, .1);
            color: #fff; border: none; padding: 10px 20px; font-size: 16px; cursor: pointer;
        }
        section { padding: 20px; }
        footer { text-align: center; padding: 20px 0; min-height: 50px; }
        footer p { margin: 0; }
    </style>
</head>
<body>
<header>
    <button class="menu-toggle" onclick="toggleMenu()">☰ Menu</button>
</header>

<h1>Tableau de bord</h1>
<p>Bienvenue, <?= e($parent['prenom'] . ' ' . $parent['nom']) ?>.</p>

<div class="menu" id="menu">
    <ul>
        <li><a href="mon-enfant.php">Mes enfants</a></li>
        <li><a href="liste_nounous.php">Trouver une nounou</a></li>
        <li><a href="home.php">Accueil</a></li>
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

<script>
    function toggleMenu() {
        document.getElementById("menu").classList.toggle("open");
    }
</script>
</body>
</html>
