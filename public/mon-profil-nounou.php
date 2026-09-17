<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$moi    = exiger_connexion('nounou');
$nounou = nounou_par_utilisateur($bdd, (int) $moi['id']);
if ($nounou === null) {
    http_response_code(500);
    exit('Profil nounou introuvable pour ce compte.');
}

$erreurs = [];
$succes  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $ville        = trim((string) ($_POST['ville'] ?? ''));
    $type_service = trim((string) ($_POST['type_service'] ?? ''));
    $horaires     = trim((string) ($_POST['horaires'] ?? ''));
    $montant      = (float) str_replace(',', '.', (string) ($_POST['montant'] ?? '0'));
    $paiement     = (string) ($_POST['paiement'] ?? '');
    $paiements    = ['heure', 'jour', 'mois'];

    if ($ville === '' || $type_service === '') {
        $erreurs[] = 'Ville et service proposé sont obligatoires.';
    }
    if (trop_long($ville, 50)) {
        $erreurs[] = 'La ville est limitée à 50 caractères.';
    }
    if (trop_long($type_service, 100)) {
        $erreurs[] = 'Le service proposé est limité à 100 caractères.';
    }
    if (trop_long($horaires, 100)) {
        $erreurs[] = 'Les horaires sont limités à 100 caractères.';
    }
    if (!in_array($paiement, $paiements, true)) {
        $erreurs[] = 'Mode de paiement invalide.';
    }
    if ($montant <= 0 || $montant > 10_000_000) {
        $erreurs[] = 'Le montant doit être compris entre 1 et 10 000 000 FCFA.';
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
        mettre_a_jour_profil_nounou(
            $bdd, (int) $moi['id'], $ville, $type_service, $horaires, $montant, $paiement, $photo
        );
        header('Location: mon-profil-nounou.php?enregistre=1');
        exit;
    }
}

if (isset($_GET['enregistre'])) {
    $succes = true;
    // Recharge le profil : la photo éventuellement changée ne serait pas
    // visible sans ça, $nounou datant d'avant la mise à jour.
    $nounou = nounou_par_utilisateur($bdd, (int) $moi['id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <script src="assets/js/menu.js" defer></script>
</head>
<body>
<div class="dash-shell">
    <?= sidebar_html($bdd, (int) $moi['id'], $moi['role'], 'mon-profil-nounou.php') ?>
    <div class="dash-main">
        <header class="dash-topbar">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰</button>
            <div>
                <span class="eyebrow">Mon espace</span>
                <h1 style="font-size:1.3rem;">Mon profil</h1>
            </div>
        </header>

        <div class="dash-content" style="max-width: 620px;">
            <div class="card" style="text-align:center; margin-bottom:1.25rem;">
                <?= avatar_html($nounou['prenom'], $nounou['nom'], $nounou['photo_profil'], 'lg') ?>
                <h2 style="margin-top:.75rem;"><?= e($nounou['prenom'] . ' ' . $nounou['nom']) ?></h2>
                <p class="help-text">C'est ce que les parents voient sur votre fiche.</p>
            </div>

            <div class="card">
                <?php if ($succes): ?>
                    <div class="etat-vide" style="margin-bottom:1.25rem;"><p>Profil mis à jour.</p></div>
                <?php endif; ?>

                <?php if ($erreurs): ?>
                    <ul class="erreur">
                        <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="post" action="mon-profil-nounou.php" enctype="multipart/form-data">
                    <?= champ_csrf() ?>

                    <div class="field">
                        <label for="photo_profil">Changer la photo de profil</label>
                        <input type="file" id="photo_profil" name="photo_profil" accept="image/jpeg,image/png,image/webp">
                        <span class="help-text">JPEG, PNG ou WebP, 2 Mo maximum. Laisser vide pour ne pas changer.</span>
                    </div>

                    <div class="field">
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" required maxlength="50"
                               value="<?= e($_POST['ville'] ?? $nounou['ville']) ?>">
                    </div>

                    <div class="field">
                        <label for="type_service">Service proposé</label>
                        <input type="text" id="type_service" name="type_service" required maxlength="100"
                               value="<?= e($_POST['type_service'] ?? $nounou['type_service']) ?>">
                    </div>

                    <div class="field">
                        <label for="horaires">Horaires de travail</label>
                        <input type="text" id="horaires" name="horaires" maxlength="100"
                               placeholder="Lun-Ven, 7h-18h" value="<?= e($_POST['horaires'] ?? $nounou['horaires']) ?>">
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="montant">Montant demandé (FCFA)</label>
                            <input type="number" id="montant" name="montant" min="0" step="500" required
                                   value="<?= e($_POST['montant'] ?? (string) $nounou['montant']) ?>">
                        </div>
                        <div class="field">
                            <label for="paiement">Facturation</label>
                            <?php $paiement_actuel = $_POST['paiement'] ?? $nounou['paiement']; ?>
                            <select id="paiement" name="paiement" required>
                                <?php foreach (['heure', 'jour', 'mois'] as $p): ?>
                                    <option value="<?= e($p) ?>" <?= $paiement_actuel === $p ? 'selected' : '' ?>>
                                        Par <?= e($p) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-block">Enregistrer</button>
                </form>
            </div>
        </div>

        <footer class="site-footer">
            <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
        </footer>
    </div>
</div>
</body>
</html>
