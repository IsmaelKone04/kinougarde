<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $nom        = trim((string) ($_POST['nom'] ?? ''));
    $prenom     = trim((string) ($_POST['prenom'] ?? ''));
    $telephone  = trim((string) ($_POST['telephone'] ?? ''));
    $ville      = trim((string) ($_POST['ville'] ?? ''));
    $email      = trim((string) ($_POST['email'] ?? ''));
    $profession = trim((string) ($_POST['profession'] ?? ''));
    $nb_enfants = (int) ($_POST['nb_enfants'] ?? 0);
    $mdp        = (string) ($_POST['mot_de_passe'] ?? '');
    $mdp2       = (string) ($_POST['confirmer_mot_de_passe'] ?? '');

    if ($nom === '' || $prenom === '' || $ville === '') {
        $erreurs[] = 'Nom, prénom et ville sont obligatoires.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
            header('Location: dashboard_parent.php');
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
    <link rel="stylesheet" href="parents.css">
</head>
<body>
    <div class="container">
        <h2>Inscription — Parent</h2>

        <?php if ($erreurs): ?>
            <ul class="erreur">
                <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form id="inscriptionForm" action="inscription-parents.php" method="post">
            <?= champ_csrf() ?>

            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" required value="<?= e($_POST['nom'] ?? '') ?>"><br>

            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" required value="<?= e($_POST['prenom'] ?? '') ?>"><br>

            <label for="telephone">Téléphone :</label>
            <input type="tel" id="telephone" name="telephone" value="<?= e($_POST['telephone'] ?? '') ?>"><br>

            <label for="ville">Ville :</label>
            <input type="text" id="ville" name="ville" required value="<?= e($_POST['ville'] ?? '') ?>"><br>

            <label for="email">Adresse e-mail :</label>
            <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"><br>

            <label for="profession">Profession :</label>
            <input type="text" id="profession" name="profession" value="<?= e($_POST['profession'] ?? '') ?>"><br>

            <label for="nb_enfants">Nombre d'enfants :</label>
            <input type="number" id="nb_enfants" name="nb_enfants" min="0" max="20"
                   value="<?= e($_POST['nb_enfants'] ?? '0') ?>"><br>

            <label for="mot_de_passe">Mot de passe :</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" minlength="8" required><br>

            <label for="confirmer_mot_de_passe">Confirmer le mot de passe :</label>
            <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" minlength="8" required><br>

            <button type="submit">Terminer</button>
        </form>

        <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
    </div>
</body>
</html>
