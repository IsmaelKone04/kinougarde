<?php
/**
 * Modification d'une fiche enfant.
 *
 * C'est le fichier qui portait la faille la plus grave du projet : il chargeait
 * et mettait à jour `enfants WHERE id = $_GET['id']` sans jamais vérifier que
 * l'enfant appartenait au parent connecté. Incrémenter l'identifiant dans
 * l'URL suffisait à lire — et à modifier — la fiche d'un enfant d'une autre
 * famille : nom, âge, allergies, besoins spécifiques.
 *
 * Le contrôle passe désormais par enfant_du_parent(), qui filtre sur parent_id.
 */

declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$moi    = exiger_connexion('parent');
$parent = parent_par_utilisateur($bdd, (int) $moi['id']);
if ($parent === null) {
    http_response_code(500);
    exit('Profil parent introuvable pour ce compte.');
}

$enfant_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($enfant_id === false || $enfant_id === null) {
    header('Location: mon-enfant.php');
    exit;
}

$enfant = enfant_du_parent($bdd, $enfant_id, (int) $parent['id']);
if ($enfant === null) {
    // Même réponse que l'enfant n'existe pas ou appartienne à quelqu'un d'autre :
    // distinguer les deux cas permettrait d'énumérer les fiches existantes.
    http_response_code(404);
    exit('Fiche introuvable.');
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_child'])) {
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
        // Le parent_id est répété dans la clause WHERE : même si l'identifiant
        // d'enfant était deviné, la mise à jour ne toucherait aucune ligne.
        $req = $bdd->prepare(
            'UPDATE enfants
                SET nom = ?, prenom = ?, age = ?, allergie = ?, besoin_specifique = ?
              WHERE id = ? AND parent_id = ?'
        );
        $req->execute([
            $nom, $prenom, $age,
            $allergie ?: null,
            $besoin_specifique ?: null,
            $enfant_id, (int) $parent['id'],
        ]);
        header('Location: mon-enfant.php');
        exit;
    }

    $enfant = array_merge($enfant, [
        'nom' => $nom, 'prenom' => $prenom, 'age' => $age,
        'allergie' => $allergie, 'besoin_specifique' => $besoin_specifique,
    ]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une fiche — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/modifier-enfant.css">
</head>
<body>
<header>
    <nav>
        <ul>
            <li><a href="mon-enfant.php">Mes enfants</a></li>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>

<header><h1>Modifier la fiche de <?= e($enfant['prenom']) ?></h1></header>

<section>
    <?php if ($erreurs): ?>
        <ul class="erreur">
            <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="modifier-enfant.php?id=<?= (int) $enfant_id ?>" method="post">
        <?= champ_csrf() ?>

        <label for="nom">Nom :</label>
        <input type="text" id="nom" name="nom" required value="<?= e($enfant['nom']) ?>"><br>

        <label for="prenom">Prénom :</label>
        <input type="text" id="prenom" name="prenom" required value="<?= e($enfant['prenom']) ?>"><br>

        <label for="age">Âge :</label>
        <input type="number" id="age" name="age" min="0" max="25" required value="<?= (int) $enfant['age'] ?>"><br>

        <label for="allergie">Allergie :</label>
        <input type="text" id="allergie" name="allergie" value="<?= e($enfant['allergie']) ?>"><br>

        <label for="besoin_specifique">Besoin spécifique :</label>
        <textarea id="besoin_specifique" name="besoin_specifique"><?= e($enfant['besoin_specifique']) ?></textarea><br>

        <button type="submit" name="update_child">Enregistrer</button>
    </form>
</section>

<footer>
    <a href="mon-enfant.php">Retour à mes enfants</a>
</footer>
</body>
</html>
