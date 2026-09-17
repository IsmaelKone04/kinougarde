<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

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
    if (trop_long($nom, 50) || trop_long($prenom, 50)) {
        $erreurs[] = 'Le nom et le prénom sont limités à 50 caractères.';
    }
    if (trop_long($allergie, 100)) {
        $erreurs[] = "L'allergie est limitée à 100 caractères.";
    }
    if (trop_long($besoin_specifique, 250)) {
        $erreurs[] = 'Le besoin spécifique est limité à 250 caractères.';
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
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/mon-enfant.css">
    <script src="assets/js/menu.js" defer></script>
</head>
<body>
<div class="dash-shell">
    <?= sidebar_html($bdd, (int) $moi['id'], $moi['role'], 'mon-enfant.php') ?>
    <div class="dash-main">
        <header class="dash-topbar">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰</button>
            <div>
                <span class="eyebrow">Espace parent</span>
                <h1 style="font-size:1.3rem;">Mes enfants</h1>
            </div>
        </header>

        <div class="dash-content">
            <div class="card">
                <h2 class="card-title">Ajouter un enfant</h2>

                <?php if ($erreurs): ?>
                    <ul class="erreur">
                        <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form action="mon-enfant.php" method="post">
                    <?= champ_csrf() ?>

                    <div class="field-row">
                        <div class="field">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" required maxlength="50">
                        </div>
                        <div class="field">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" required maxlength="50">
                        </div>
                    </div>

                    <div class="field">
                        <label for="age">Âge</label>
                        <input type="number" id="age" name="age" min="0" max="25" required>
                    </div>

                    <div class="field">
                        <label for="allergie">Allergie</label>
                        <input type="text" id="allergie" name="allergie" maxlength="100">
                    </div>

                    <div class="field">
                        <label for="besoin_specifique">Besoin spécifique</label>
                        <textarea id="besoin_specifique" name="besoin_specifique" maxlength="250"></textarea>
                    </div>

                    <button type="submit" name="add_child" class="btn-block">Ajouter</button>
                </form>
            </div>

            <div class="card">
                <h2 class="card-title">Fiches enregistrées</h2>
                <?php if (!$enfants): ?>
                    <div class="etat-vide"><p>Aucun enfant enregistré.</p></div>
                <?php else: ?>
                    <div class="cards-grid">
                        <?php foreach ($enfants as $enfant): ?>
                            <div class="card" style="box-shadow:none; background: var(--color-bg);">
                                <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:.6rem;">
                                    <?= avatar_html($enfant['prenom'], $enfant['nom'], null, 'sm') ?>
                                    <strong><?= e($enfant['prenom'] . ' ' . $enfant['nom']) ?></strong>
                                </div>
                                <p>Âge : <?= (int) $enfant['age'] ?> ans</p>
                                <p>Allergie : <?= e($enfant['allergie'] ?: 'aucune') ?></p>
                                <p>Besoin spécifique : <?= e($enfant['besoin_specifique'] ?: '—') ?></p>
                                <a href="modifier-enfant.php?id=<?= (int) $enfant['id'] ?>" class="btn btn-outline btn-sm" style="margin-top:.75rem;">Modifier</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer class="site-footer">
            <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
            <p>&copy; 2024 <?= e(APP_NOM) ?>. Projet étudiant, à but non commercial.</p>
        </footer>
    </div>
</div>
</body>
</html>
