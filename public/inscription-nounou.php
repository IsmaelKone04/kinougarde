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
    if (trop_long($nom, 100) || trop_long($prenom, 100)) {
        $erreurs[] = 'Le nom et le prénom sont limités à 100 caractères.';
    }
    if (trop_long($ville, 50)) {
        $erreurs[] = 'La ville est limitée à 50 caractères.';
    }
    if (trop_long($numero, 20)) {
        $erreurs[] = 'Le numéro de téléphone est limité à 20 caractères.';
    }
    if (trop_long($type_service, 100)) {
        $erreurs[] = 'Le service proposé est limité à 100 caractères.';
    }
    if (trop_long($horaires, 100)) {
        $erreurs[] = 'Les horaires sont limités à 100 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || trop_long($email, 190)) {
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
    // La borne haute évite qu'une valeur absurde échoue silencieusement à
    // l'insertion (la colonne est un DECIMAL(10,2), plafonné à 99 999 999,99).
    if ($montant <= 0 || $montant > 10_000_000) {
        $erreurs[] = 'Le montant doit être compris entre 1 et 10 000 000 FCFA.';
    }
    if (strlen($mdp) < 8) {
        $erreurs[] = 'Le mot de passe doit faire au moins 8 caractères.';
    }
    if ($mdp !== $mdp2) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    $photo = null;
    if (!$erreurs) {
        try {
            $photo = enregistrer_photo_profil($_FILES['photo_profil'] ?? []);
        } catch (RuntimeException $e) {
            $erreurs[] = $e->getMessage();
        }
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
                'INSERT INTO nounous (utilisateur_id, sexe, ville, type_service, horaires, montant, paiement, photo_profil)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $req->execute([$utilisateur_id, $sexe, $ville, $type_service, $horaires, $montant, $paiement, $photo]);

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
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/inscription-nounou.css">
</head>
<body class="marque">
<header class="topbar">
    <a href="index.php" class="brand"><span class="brand-mark">🍼</span> kiNouGarde</a>
</header>

<div class="auth-shell">
    <div class="auth-card auth-card-wide">
        <span class="eyebrow" style="display:block; text-align:center;">Espace nounou</span>
        <h2>Créer mon profil</h2>
        <p>Renseignez vos services pour apparaître auprès des parents.</p>

        <?php if ($erreurs): ?>
            <ul class="erreur">
                <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="inscription-nounou.php" method="post" enctype="multipart/form-data">
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
                <label for="photo_profil">Photo de profil (facultatif)</label>
                <input type="file" id="photo_profil" name="photo_profil" accept="image/jpeg,image/png,image/webp">
                <span class="help-text">JPEG, PNG ou WebP, 2 Mo maximum. Aide les parents à vous reconnaître.</span>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="sexe">Sexe</label>
                    <select id="sexe" name="sexe" required>
                        <?php foreach ($sexes as $s): ?>
                            <option value="<?= e($s) ?>" <?= (($_POST['sexe'] ?? '') === $s) ? 'selected' : '' ?>>
                                <?= e(ucfirst($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="ville">Ville</label>
                    <input type="text" id="ville" name="ville" required maxlength="50" value="<?= e($_POST['ville'] ?? '') ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="numero">Téléphone</label>
                    <input type="tel" id="numero" name="numero" maxlength="20" value="<?= e($_POST['numero'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" minlength="8"
                           placeholder="8 caractères minimum" required>
                </div>
                <div class="field">
                    <label for="confirmation_mot_de_passe">Confirmation</label>
                    <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe"
                           minlength="8" required>
                </div>
            </div>

            <div class="field">
                <label for="type_service">Service proposé</label>
                <input type="text" id="type_service" name="type_service" required maxlength="100"
                       placeholder="Garde d'enfants, ménage, aide aux devoirs…"
                       value="<?= e($_POST['type_service'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="horaires">Horaires de travail</label>
                <input type="text" id="horaires" name="horaires" maxlength="100"
                       placeholder="Lun-Ven, 7h-18h" value="<?= e($_POST['horaires'] ?? '') ?>">
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="montant">Montant demandé (FCFA)</label>
                    <input type="number" id="montant" name="montant" min="0" step="500" required
                           value="<?= e($_POST['montant'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="paiement">Facturation</label>
                    <select id="paiement" name="paiement" required>
                        <?php foreach ($paiements as $p): ?>
                            <option value="<?= e($p) ?>" <?= (($_POST['paiement'] ?? '') === $p) ? 'selected' : '' ?>>
                                Par <?= e($p) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-block">S'inscrire</button>
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
