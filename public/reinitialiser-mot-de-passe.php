<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$jeton = (string) ($_GET['jeton'] ?? $_POST['jeton'] ?? '');
$utilisateur_id = $jeton !== '' ? verifier_jeton_reinitialisation($bdd, $jeton) : null;

$erreurs  = [];
$reussi   = false;

if ($utilisateur_id === null) {
    $erreurs[] = 'Ce lien est invalide, expiré ou a déjà été utilisé.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $mdp  = (string) ($_POST['mot_de_passe'] ?? '');
    $mdp2 = (string) ($_POST['confirmation_mot_de_passe'] ?? '');

    if (strlen($mdp) < 8) {
        $erreurs[] = 'Le mot de passe doit faire au moins 8 caractères.';
    }
    if ($mdp !== $mdp2) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (!$erreurs) {
        appliquer_reinitialisation($bdd, $utilisateur_id, $mdp);
        $reussi = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
</head>
<body class="marque">
<header class="topbar">
    <a href="index.php" class="brand"><span class="brand-mark">🍼</span> kiNouGarde</a>
</header>

<div class="auth-shell">
    <div class="auth-card">
        <h2>Nouveau mot de passe</h2>

        <?php if ($reussi): ?>
            <p>Votre mot de passe a été mis à jour.</p>
            <p class="auth-footer"><a href="login.php">Se connecter</a></p>
        <?php else: ?>
            <?php if ($erreurs): ?>
                <ul class="erreur">
                    <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($utilisateur_id !== null): ?>
                <form method="post" action="reinitialiser-mot-de-passe.php">
                    <?= champ_csrf() ?>
                    <input type="hidden" name="jeton" value="<?= e($jeton) ?>">

                    <div class="field">
                        <label for="mot_de_passe">Nouveau mot de passe</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" minlength="8"
                               placeholder="8 caractères minimum" required autofocus>
                    </div>
                    <div class="field">
                        <label for="confirmation_mot_de_passe">Confirmation</label>
                        <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe"
                               minlength="8" required>
                    </div>
                    <input type="submit" value="Changer le mot de passe">
                </form>
            <?php else: ?>
                <p class="auth-footer"><a href="mot-de-passe-oublie.php">Demander un nouveau lien</a></p>
            <?php endif; ?>

            <p class="auth-footer"><a href="login.php">&larr; Retour à la connexion</a></p>
        <?php endif; ?>
    </div>
</div>

<footer class="site-footer site-footer-clair">
    <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
</footer>
</body>
</html>
