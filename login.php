<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $email        = trim((string) ($_POST['email'] ?? ''));
    $mot_de_passe = (string) ($_POST['mot_de_passe'] ?? '');

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

        connecter((int) $compte['id'], $compte['role'], $compte['email']);
        header('Location: ' . ($compte['role'] === 'nounou' ? 'dashboard_nounou.php' : 'dashboard_parent.php'));
        exit;
    }

    // Message volontairement identique que l'e-mail existe ou non : préciser
    // « aucun compte trouvé avec cet email » revient à confirmer, adresse par
    // adresse, qui est inscrit sur une plateforme de garde d'enfants.
    $erreur = 'Adresse e-mail ou mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <h2>Connexion</h2>

        <?php if ($erreur !== null): ?>
            <p class="erreur"><?= e($erreur) ?></p>
        <?php endif; ?>

        <form class="login-form" method="post" action="login.php">
            <?= champ_csrf() ?>
            <div class="form-group">
                <label for="email">Adresse e-mail</label><br>
                <input type="email" id="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>"><br>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label><br>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required><br><br>
            </div>
            <input type="submit" value="Se connecter">
        </form>

        <p>
            Pas encore de compte ?
            <a href="inscription-parents.php">Je suis parent</a> ·
            <a href="inscription-nounou.php">Je suis nounou</a>
        </p>
    </div>
</body>
</html>
