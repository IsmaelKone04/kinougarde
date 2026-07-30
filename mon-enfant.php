<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$moi    = exiger_connexion('parent');
$parent = parent_par_utilisateur($bdd, (int) $moi['id']);
if ($parent === null) {
    http_response_code(500);
    exit('Profil parent introuvable pour ce compte.');
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_child'])) {
    verifier_csrf();

    $nom               = trim((string) ($_POST['nom'] ?? ''));
    $prenom            = trim((string) ($_POST['prenom'] ?? ''));
    $age               = (int) ($_POST['age'] ?? 0);
    $allergie          = trim((string) ($_POST['allergie'] ?? ''));
    $besoin_specifique = trim((string) ($_POST['besoin_specifique'] ?? ''));

    if ($nom === '' || $prenom === '') {
        $erreurs[] = 'Le nom et le prénom sont obligatoires.';
    }
    if ($age < 0 || $age > 25) {
        $erreurs[] = "L'âge doit être compris entre 0 et 25 ans.";
    }

    if (!$erreurs) {
        // parent_id vient de la session, jamais du formulaire : sinon un parent
        // pourrait rattacher un enfant au dossier d'une autre famille.
        $req = $bdd->prepare(
            'INSERT INTO enfants (parent_id, nom, prenom, age, allergie, besoin_specifique)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $req->execute([
            (int) $parent['id'], $nom, $prenom, $age,
            $allergie ?: null,
            $besoin_specifique ?: null,
        ]);
        header('Location: mon-enfant.php');
        exit;
    }
}

$enfants = enfants_du_parent($bdd, (int) $parent['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes enfants — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="mon-enfant.css">
</head>
<body>
<header>
    <nav>
        <ul>
            <li><a href="dashboard_parent.php">Mon profil</a></li>
            <li><a href="home.php">Accueil</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>

<header><h1>Mes enfants</h1></header>

<section>
    <h2>Ajouter un enfant</h2>

    <?php if ($erreurs): ?>
        <ul class="erreur">
            <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="mon-enfant.php" method="post">
        <?= champ_csrf() ?>

        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom" required><br>

        <label for="prenom">Prénom :</label>
        <input type="text" id="prenom" name="prenom" required><br>

        <label for="age">Âge :</label>
        <input type="number" id="age" name="age" min="0" max="25" required><br>

        <label for="allergie">Allergie :</label>
        <input type="text" id="allergie" name="allergie"><br>

        <label for="besoin_specifique">Besoin spécifique :</label>
        <textarea id="besoin_specifique" name="besoin_specifique"></textarea><br>

        <button type="submit" name="add_child">Ajouter</button>
    </form>
</section>

<section>
    <h2>Fiches enregistrées</h2>
    <?php if (!$enfants): ?>
        <p>Aucun enfant enregistré.</p>
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

<footer>
    <a href="dashboard_parent.php">Retour au tableau de bord</a>
</footer>
</body>
</html>
