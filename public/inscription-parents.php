<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $nom        = trim((string) ($_POST['nom'] ?? ''));
    $prenom     = trim((string) ($_POST['prenom'] ?? ''));
    $telephone  = trim((string) ($_POST['telephone'] ?? ''));
    $ville      = trim((string) ($_POST['ville'] ?? ''));
    $email      = trim((string) ($_POST['email'] ?? ''));
    $profession = trim((string) ($_POST['profession'] ?? ''));
    // Bornage serveur : le formulaire limite déjà 0-20 côté navigateur, mais
    // rien n'empêchait un POST direct d'envoyer une valeur hors limites.
    $nb_enfants = max(0, min(20, (int) ($_POST['nb_enfants'] ?? 0)));
    $mdp        = (string) ($_POST['mot_de_passe'] ?? '');
    $mdp2       = (string) ($_POST['confirmer_mot_de_passe'] ?? '');

    if ($nom === '' || $prenom === '' || $ville === '') {
        $erreurs[] = 'Nom, prénom et ville sont obligatoires.';
    }
    if (trop_long($nom, 100) || trop_long($prenom, 100)) {
        $erreurs[] = 'Le nom et le prénom sont limités à 100 caractères.';
    }
    if (trop_long($telephone, 20)) {
        $erreurs[] = 'Le téléphone est limité à 20 caractères.';
    }
    if (trop_long($ville, 50)) {
        $erreurs[] = 'La ville est limitée à 50 caractères.';
    }
    if (trop_long($profession, 100)) {
        $erreurs[] = 'La profession est limitée à 100 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || trop_long($email, 190)) {
        $erreurs[] = "L'adresse e-mail est invalide.";
    }
    if (strlen($mdp) < 8) {
        $erreurs[] = 'Le mot de passe doit faire au moins 8 caractères.';
    }
    if ($mdp !== $mdp2) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (!$erreurs) {
        try {
            // Les deux insertions forment un tout : sans transaction, un échec
            // sur la seconde laissait un compte sans profil, impossible à
            // utiliser mais occupant l'adresse e-mail.
            $bdd->beginTransaction();

            $req = $bdd->prepare(
                'INSERT INTO utilisateurs (nom, prenom, telephone, email, mot_de_passe, role)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $req->execute([
                $nom, $prenom, $telephone, $email,
                password_hash($mdp, PASSWORD_DEFAULT),   // jamais le mot de passe en clair
                'parent',
            ]);
            $utilisateur_id = (int) $bdd->lastInsertId();

            $req = $bdd->prepare(
                'INSERT INTO parents (utilisateur_id, ville, profession, nb_enfants) VALUES (?, ?, ?, ?)'
            );
            $req->execute([$utilisateur_id, $ville, $profession, $nb_enfants]);

            $bdd->commit();

            connecter($utilisateur_id, 'parent', $email);
            header('Location: dashboard-parent.php');
            exit;
        } catch (PDOException $e) {
            $bdd->rollBack();
            // 23000 = violation de contrainte ; ici, l'unicité de l'e-mail.
            $erreurs[] = $e->getCode() === '23000'
                ? 'Un compte existe déjà avec cette adresse e-mail.'
                : "L'inscription a échoué. Merci de réessayer.";
            error_log('Inscription parent : ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription parent — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/inscription-parent.css">
</head>
<body class="marque">
<header class="topbar">
    <a href="index.php" class="brand"><span class="brand-mark">🍼</span> kiNouGarde</a>
</header>

<div class="auth-shell">
    <div class="auth-card">
        <span class="eyebrow" style="display:block; text-align:center;">Espace parent</span>
        <h2>Créer mon compte</h2>
        <p>Pour trouver la nounou qu'il vous faut.</p>

        <?php if ($erreurs): ?>
            <ul class="erreur">
                <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form id="inscriptionForm" action="inscription-parents.php" method="post">
            <?= champ_csrf() ?>

            <div class="field-row">
                <div class="field">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required maxlength="100" value="<?= e($_POST['nom'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" required maxlength="100" value="<?= e($_POST['prenom'] ?? '') ?>">
                </div>
            </div>

            <div class="field">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" maxlength="20" value="<?= e($_POST['telephone'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="ville">Ville</label>
                <input type="text" id="ville" name="ville" required maxlength="50" value="<?= e($_POST['ville'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="profession">Profession</label>
                    <input type="text" id="profession" name="profession" maxlength="100" value="<?= e($_POST['profession'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="nb_enfants">Nombre d'enfants</label>
                    <input type="number" id="nb_enfants" name="nb_enfants" min="0" max="20"
                           value="<?= e($_POST['nb_enfants'] ?? '0') ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" minlength="8" required>
                </div>
                <div class="field">
                    <label for="confirmer_mot_de_passe">Confirmation</label>
                    <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" minlength="8" required>
                </div>
            </div>

            <button type="submit" class="btn-block">Terminer</button>
        </form>

        <p class="auth-footer">Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
    </div>
</div>

<footer class="site-footer site-footer-clair">
    <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
    <p>&copy; 2024 <?= e(APP_NOM) ?>. Projet étudiant, à but non commercial.</p>
</footer>
</body>
</html>
