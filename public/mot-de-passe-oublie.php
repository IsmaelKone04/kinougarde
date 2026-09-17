<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$envoye = false;
$lien_demo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));

    $req = $bdd->prepare('SELECT id FROM utilisateurs WHERE email = ?');
    $req->execute([$email]);
    $utilisateur_id = $req->fetchColumn();

    // Même écran de confirmation que le compte existe ou non : sinon, ce
    // formulaire deviendrait un moyen de vérifier qui est inscrit sur une
    // plateforme de garde d'enfants (même raisonnement que login.php).
    if ($utilisateur_id !== false) {
        $jeton = demarrer_reinitialisation($bdd, (int) $utilisateur_id);

        // Pas de serveur SMTP configuré sur ce projet de démonstration : le
        // lien serait normalement envoyé par e-mail. Affiché ici à la place,
        // avec un avertissement explicite — voir README (§ limites connues).
        $lien_demo = sprintf(
            '%s://%s/reinitialiser-mot-de-passe.php?jeton=%s',
            (!empty($_SERVER['HTTPS']) ? 'https' : 'http'),
            $_SERVER['HTTP_HOST'] ?? 'localhost',
            $jeton
        );
    }

    $envoye = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
</head>
<body class="marque">
<header class="topbar">
    <a href="index.php" class="brand"><span class="brand-mark">🍼</span> kiNouGarde</a>
</header>

<div class="auth-shell">
    <div class="auth-card">
        <h2>Mot de passe oublié</h2>

        <?php if ($envoye): ?>
            <p>Si un compte existe avec cette adresse, un lien de réinitialisation vient d'être généré. Il est valable 30 minutes.</p>

            <?php if ($lien_demo !== null): ?>
                <div class="etat-vide" style="text-align:left; margin-top:1.25rem;">
                    <p style="font-weight:700; margin-bottom:.5rem;">⚠️ Mode démonstration</p>
                    <p class="help-text" style="margin-bottom:.75rem;">
                        Ce projet n'a pas de serveur d'e-mail configuré. En production, ce lien
                        serait envoyé à l'adresse du compte plutôt qu'affiché ici.
                    </p>
                    <p><a href="<?= e($lien_demo) ?>"><?= e($lien_demo) ?></a></p>
                </div>
            <?php endif; ?>

            <p class="auth-footer"><a href="login.php">&larr; Retour à la connexion</a></p>
        <?php else: ?>
            <p>Renseignez votre adresse e-mail pour recevoir un lien de réinitialisation.</p>

            <form method="post" action="mot-de-passe-oublie.php">
                <?= champ_csrf() ?>
                <div class="field">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                <input type="submit" value="Envoyer le lien">
            </form>

            <p class="auth-footer"><a href="login.php">&larr; Retour à la connexion</a></p>
        <?php endif; ?>
    </div>
</div>

<footer class="site-footer site-footer-clair">
    <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
</footer>
</body>
</html>
