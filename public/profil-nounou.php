<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

// L'ancienne version n'exigeait aucune connexion et construisait la requête
// par concaténation : profil-nounou.php?id=0 UNION SELECT … suffisait à lire
// la table entière, colonne mot de passe comprise.
$moi = exiger_connexion();

$nounou_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($nounou_id === false || $nounou_id === null) {
    header('Location: liste-nounous.php');
    exit;
}

$nounou = profil_nounou($bdd, $nounou_id);
if ($nounou === null) {
    http_response_code(404);
    $introuvable = true;
}

$erreurs  = [];
$propose  = false;

if (!empty($nounou) && $moi['role'] === 'parent' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proposer_contrat'])) {
    verifier_csrf();

    $parent  = parent_par_utilisateur($bdd, (int) $moi['id']);
    $titre   = trim((string) ($_POST['titre'] ?? ''));
    $montant = (float) str_replace(',', '.', (string) ($_POST['montant'] ?? '0'));

    if ($titre === '' || trop_long($titre, 150)) {
        $erreurs[] = 'Le titre est obligatoire (150 caractères maximum).';
    }
    if ($montant <= 0 || $montant > 10_000_000) {
        $erreurs[] = 'Le montant doit être compris entre 1 et 10 000 000 FCFA.';
    }

    if (!$erreurs && $parent !== null) {
        proposer_contrat($bdd, (int) $parent['id'], $nounou_id, $titre, $montant);
        $propose = true;
    }
}

$avis = !empty($nounou) ? avis_nounou($bdd, $nounou_id) : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil nounou — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/profil-nounou.css">
</head>
<body>
<div class="dash-shell">
    <?= sidebar_html($bdd, (int) $moi['id'], $moi['role'], 'liste-nounous.php') ?>
    <div class="dash-main">
        <header class="dash-topbar">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰</button>
            <div>
                <span class="eyebrow">Profil</span>
                <h1 style="font-size:1.3rem;">Fiche nounou</h1>
            </div>
        </header>

        <div class="dash-content" style="max-width: 720px;">
            <p><a href="liste-nounous.php">&larr; Retour à la liste</a></p>

            <?php if (!empty($introuvable)): ?>
                <div class="etat-vide" style="margin-top:1.25rem;">
                    <p>Aucun profil de nounou ne correspond à cet identifiant.</p>
                </div>
            <?php else: ?>
                <?php
                $libelles_sexe = ['femme' => 'Femme', 'homme' => 'Homme', 'autre' => 'Autre'];
                ?>
                <div class="card profile-details">
                    <?= avatar_html($nounou['prenom'], $nounou['nom'], $nounou['photo_profil'], 'lg') ?>
                    <h2>
                        <?= e($nounou['prenom'] . ' ' . $nounou['nom']) ?>
                        <?php if ((int) $nounou['verifiee'] === 1): ?>
                            <span class="badge-verifiee" title="Identité vérifiée par kiNouGarde">✅ Vérifiée</span>
                        <?php endif; ?>
                    </h2>
                    <p class="profile-ville">📍 <?= e($nounou['ville']) ?></p>

                    <div class="profile-badges">
                        <span class="badge"><?= e($nounou['type_service']) ?></span>
                        <span class="badge badge-accent"><?= number_format((float) $nounou['montant'], 0, ',', ' ') ?> FCFA / <?= e($nounou['paiement']) ?></span>
                        <?php if ((int) $nounou['nb_contrats'] > 0): ?>
                            <span class="badge">✅ <?= (int) $nounou['nb_contrats'] ?> famille<?= $nounou['nb_contrats'] > 1 ? 's' : '' ?> accompagnée<?= $nounou['nb_contrats'] > 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                        <?php if ($nounou['note_moyenne'] !== null): ?>
                            <span class="badge">⭐ <?= e((string) $nounou['note_moyenne']) ?>/5 (<?= (int) $nounou['nb_avis'] ?> avis)</span>
                        <?php endif; ?>
                    </div>

                    <dl class="profile-info">
                        <div><dt>Horaires</dt><dd><?= e($nounou['horaires'] ?: 'non précisés') ?></dd></div>
                        <div><dt>Téléphone</dt><dd><?= e($nounou['telephone'] ?: 'non renseigné') ?></dd></div>
                        <div><dt>Genre</dt><dd><?= e($libelles_sexe[$nounou['sexe']] ?? 'non précisé') ?></dd></div>
                    </dl>

                    <a href="messages.php?avec=<?= (int) $nounou['utilisateur_id'] ?>" class="btn btn-primary btn-block message-form">
                        Envoyer un message à <?= e($nounou['prenom']) ?>
                    </a>
                </div>

                <?php if ($moi['role'] === 'parent'): ?>
                    <div class="card">
                        <h2 class="card-title">Proposer un contrat</h2>

                        <?php if ($propose): ?>
                            <div class="etat-vide">
                                <p>Proposition envoyée à <?= e($nounou['prenom']) ?>. Vous la retrouverez dans « Mes contrats » sur votre tableau de bord.</p>
                            </div>
                        <?php else: ?>
                            <?php if ($erreurs): ?>
                                <ul class="erreur">
                                    <?php foreach ($erreurs as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <form method="post" action="profil-nounou.php?id=<?= (int) $nounou_id ?>">
                                <?= champ_csrf() ?>
                                <div class="field">
                                    <label for="titre">Titre</label>
                                    <input type="text" id="titre" name="titre" required maxlength="150"
                                           placeholder="Garde à domicile — année scolaire"
                                           value="<?= e($_POST['titre'] ?? '') ?>">
                                </div>
                                <div class="field">
                                    <label for="montant">Montant proposé (FCFA)</label>
                                    <input type="number" id="montant" name="montant" min="0" step="500" required
                                           value="<?= e($_POST['montant'] ?? '') ?>">
                                </div>
                                <button type="submit" name="proposer_contrat" class="btn-block">Envoyer la proposition</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h2 class="card-title">Avis (<?= count($avis) ?>)</h2>
                    <?php if (!$avis): ?>
                        <div class="etat-vide"><p>Aucun avis pour l'instant.</p></div>
                    <?php else: ?>
                        <?php foreach ($avis as $a): ?>
                            <div style="padding:.75rem 0; border-bottom:1px solid var(--color-border);">
                                <strong><?= str_repeat('★', (int) $a['note']) . str_repeat('☆', 5 - (int) $a['note']) ?></strong>
                                <span class="help-text"> — <?= e($a['prenom']) ?>, <?= e(substr($a['cree_le'], 0, 10)) ?></span>
                                <?php if ($a['commentaire']): ?>
                                    <p><?= e($a['commentaire']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <footer class="site-footer">
            <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
        </footer>
    </div>
</div>
</body>
</html>
