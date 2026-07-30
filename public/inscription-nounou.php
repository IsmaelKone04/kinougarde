<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$erreurs  = [];
$sexes    = ['femme', 'homme', 'autre'];
$paiements = ['heure', 'jour', 'mois'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $nom          = trim((string) ($_POST['nom'] ?? ''));
    $prenom       = trim((string) ($_POST['prenom'] ?? ''));
    $sexe         = (string) ($_POST['sexe'] ?? '');
    $ville        = trim((string) ($_POST['ville'] ?? ''));
    $numero       = trim((string) ($_POST['numero'] ?? ''));
    $email        = trim((string) ($_POST['email'] ?? ''));
    $type_service = trim((string) ($_POST['type_service'] ?? ''));
    $horaires     = trim((string) ($_POST['horaires'] ?? ''));
    $montant      = (float) str_replace(',', '.', (string) ($_POST['montant'] ?? '0'));
    $paiement     = (string) ($_POST['paiement'] ?? '');
    $mdp          = (string) ($_POST['mot_de_passe'] ?? '');
    $mdp2         = (string) ($_POST['confirmation_mot_de_passe'] ?? '');

    if ($nom === '' || $prenom === '' || $ville === '' || $type_service === '') {
        $erreurs[] = 'Nom, prénom, ville et type de service sont obligatoires.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "L'adresse e-mail est invalide.";
    }
    // Les listes déroulantes sont revalidées côté serveur : le navigateur peut
    // toujours envoyer une valeur absente du <select>.
    if (!in_array($sexe, $sexes, true)) {
        $erreurs[] = 'Sexe invalide.';
    }
    if (!in_array($paiement, $paiements, true)) {
        $erreurs[] = 'Mode de paiement invalide.';
    }
    if ($montant <= 0) {
        $erreurs[] = 'Le montant doit être supérieur à zéro.';
    }
    if (strlen($mdp) < 8) {
        $erreurs[] = 'Le mot de passe doit faire au moins 8 caractères.';
    }
    if ($mdp !== $mdp2) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (!$erreurs) {
        try {
            $bdd->beginTransaction();

            $req = $bdd->prepare(
                'INSERT INTO utilisateurs (nom, prenom, telephone, email, mot_de_passe, role)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            // C'est ici que se trouvait la faille principale : l'ancienne version
            // insérait $mot_de_passe tel quel, sans password_hash().
            $req->execute([
                $nom, $prenom, $numero, $email,
                password_hash($mdp, PASSWORD_DEFAULT),
                'nounou',
            ]);
            $utilisateur_id = (int) $bdd->lastInsertId();

            $req = $bdd->prepare(
                'INSERT INTO nounous (utilisateur_id, sexe, ville, type_service, horaires, montant, paiement)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $req->execute([$utilisateur_id, $sexe, $ville, $type_service, $horaires, $montant, $paiement]);

            $bdd->commit();

            connecter($utilisateur_id, 'nounou', $email);
            header('Location: dashboard-nounou.php');
            exit;
        } catch (PDOException $e) {
            $bdd->rollBack();
            $erreurs[] = $e->getCode() === '23000'
                ? 'Un compte existe déjà avec cette adresse e-mail.'
                : "L'inscription a échoué. Merci de réessayer.";
            error_log('Inscription nounou : ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription nounou — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/inscription-nounou.css">
</head>
<body>
    <div class="container">
        <h2>Inscription — Nounou</h2>

        <?php if ($erreurs): ?>
            <ul class="erreur">
                <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="inscription-nounou.php" method="post">
            <?= champ_csrf() ?>

            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" required value="<?= e($_POST['nom'] ?? '') ?>"><br>

            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" required value="<?= e($_POST['prenom'] ?? '') ?>"><br>

            <label for="sexe">Sexe :</label>
            <select id="sexe" name="sexe" required>
                <?php foreach ($sexes as $s): ?>
                    <option value="<?= e($s) ?>" <?= (($_POST['sexe'] ?? '') === $s) ? 'selected' : '' ?>>
                        <?= e(ucfirst($s)) ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <label for="ville">Ville :</label>
            <input type="text" id="ville" name="ville" required value="<?= e($_POST['ville'] ?? '') ?>"><br>

            <label for="numero">Numéro de téléphone :</label>
            <input type="tel" id="numero" name="numero" value="<?= e($_POST['numero'] ?? '') ?>"><br>

            <label for="email">Adresse e-mail :</label>
            <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"><br>

            <label for="mot_de_passe">Mot de passe :</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" minlength="8"
                   placeholder="8 caractères minimum" required><br>

            <label for="confirmation_mot_de_passe">Confirmation du mot de passe :</label>
            <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe"
                   minlength="8" required><br>

            <label for="type_service">Service proposé :</label>
            <input type="text" id="type_service" name="type_service" required
                   placeholder="Garde d'enfants, ménage, aide aux devoirs…"
                   value="<?= e($_POST['type_service'] ?? '') ?>"><br>

            <label for="horaires">Horaires de travail :</label>
            <input type="text" id="horaires" name="horaires"
                   placeholder="Lun-Ven, 7h-18h" value="<?= e($_POST['horaires'] ?? '') ?>"><br>

            <label for="montant">Montant demandé (FCFA) :</label>
            <input type="number" id="montant" name="montant" min="0" step="500" required
                   value="<?= e($_POST['montant'] ?? '') ?>"><br>

            <label for="paiement">Facturation :</label>
            <select id="paiement" name="paiement" required>
                <?php foreach ($paiements as $p): ?>
                    <option value="<?= e($p) ?>" <?= (($_POST['paiement'] ?? '') === $p) ? 'selected' : '' ?>>
                        Par <?= e($p) ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <button type="submit">S'inscrire</button>
        </form>

        <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
    </div>
</body>
</html>
