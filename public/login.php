<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $email        = trim((string) ($_POST['email'] ?? ''));
    $mot_de_passe = (string) ($_POST['mot_de_passe'] ?? '');
    $ip           = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    // Aucune protection contre les essais répétés avant cette vérification :
    // un script pouvait tester des mots de passe sans limite. La limite porte
    // sur l'e-mail visé, pas sur l'IP seule, pour ne pas se laisser contourner
    // par un attaquant distribué sur plusieurs adresses.
    if (trop_de_tentatives($bdd, $email)) {
        $erreur = 'Trop de tentatives pour ce compte. Réessayez dans quelques minutes.';
    } else {
        $req = $bdd->prepare('SELECT id, email, mot_de_passe, role FROM utilisateurs WHERE email = ?');
        $req->execute([$email]);
        $compte = $req->fetch();

        // Une seule vérification, identique pour les deux rôles. L'ancienne version
        // comparait le mot de passe des nounous avec === (donc en clair) et celui
        // des parents avec password_verify() : la moitié des comptes n'était pas
        // protégée du tout.
        if ($compte && password_verify($mot_de_passe, $compte['mot_de_passe'])) {
            // Ré-hache si le coût par défaut de PHP a augmenté depuis l'inscription.
            if (password_needs_rehash($compte['mot_de_passe'], PASSWORD_DEFAULT)) {
                $maj = $bdd->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
                $maj->execute([password_hash($mot_de_passe, PASSWORD_DEFAULT), $compte['id']]);
            }

            reinitialiser_tentatives($bdd, $email);
            connecter((int) $compte['id'], $compte['role'], $compte['email']);
            header('Location: ' . ($compte['role'] === 'nounou' ? 'dashboard-nounou.php' : 'dashboard-parent.php'));
            exit;
        }

        enregistrer_tentative_echouee($bdd, $email, $ip);

        // Message volontairement identique que l'e-mail existe ou non : préciser
        // « aucun compte trouvé avec cet email » revient à confirmer, adresse par
        // adresse, qui est inscrit sur une plateforme de garde d'enfants.
        $erreur = 'Adresse e-mail ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="marque">
<header class="topbar">
    <a href="index.php" class="brand"><span class="brand-mark">🍼</span> kiNouGarde</a>
</header>

<div class="auth-shell">
    <div class="auth-card">
        <h2>Bon retour</h2>
        <p>Connectez-vous à votre espace.</p>

        <?php if ($erreur !== null): ?>
            <p class="erreur"><?= e($erreur) ?></p>
        <?php endif; ?>

        <form class="login-form" method="post" action="login.php">
            <?= champ_csrf() ?>
            <div class="field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <input type="submit" value="Se connecter">
        </form>

        <p class="auth-footer"><a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>

        <p class="auth-footer">
            Pas encore de compte ?
            <a href="inscription-parents.php">Je suis parent</a> ·
            <a href="inscription-nounou.php">Je suis nounou</a>
        </p>
    </div>
</div>

<footer class="site-footer site-footer-clair">
    <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
    <p>&copy; 2024 <?= e(APP_NOM) ?>. Projet étudiant, à but non commercial.</p>
</footer>
</body>
</html>
