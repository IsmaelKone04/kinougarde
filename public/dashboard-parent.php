<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$moi = exiger_connexion('parent');

$parent = parent_par_utilisateur($bdd, (int) $moi['id']);
if ($parent === null) {
    http_response_code(500);
    exit('Profil parent introuvable pour ce compte.');
}

$erreurs_avis = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    if (isset($_POST['marquer_termine'])) {
        marquer_contrat_termine($bdd, (int) $_POST['contrat_id'], (int) $parent['id']);
        header('Location: dashboard-parent.php');
        exit;
    }

    if (isset($_POST['laisser_avis'])) {
        $contrat_id  = (int) $_POST['contrat_id'];
        $note        = (int) ($_POST['note'] ?? 0);
        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

        $nounou_id = contrat_notable($bdd, $contrat_id, (int) $parent['id']);

        if ($nounou_id === null) {
            $erreurs_avis[] = "Ce contrat ne peut pas (ou plus) être noté.";
        } elseif ($note < 1 || $note > 5) {
            $erreurs_avis[] = 'La note doit être comprise entre 1 et 5.';
        } elseif (trop_long($commentaire, 500)) {
            $erreurs_avis[] = 'Le commentaire est limité à 500 caractères.';
        } else {
            laisser_avis($bdd, $contrat_id, (int) $parent['id'], $nounou_id, $note, $commentaire ?: null);
            header('Location: dashboard-parent.php');
            exit;
        }
    }
}

$enfants  = enfants_du_parent($bdd, (int) $parent['id']);
$contrats = contrats_parent($bdd, (int) $parent['id']);

$libelles_statut = [
    'en_attente' => ['En attente de réponse', 'badge'],
    'acceptee'   => ['Acceptée', 'badge-accent'],
    'refusee'    => ['Refusée', 'badge'],
    'terminee'   => ['Terminée', 'badge'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord parent — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/dashboard-parent.css">
    <script src="assets/js/menu.js" defer></script>
</head>
<body>
<div class="dash-shell">
    <?= sidebar_html($bdd, (int) $moi['id'], $moi['role'], 'dashboard-parent.php') ?>
    <div class="dash-main">
        <header class="dash-topbar">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰</button>
            <div style="display:flex; align-items:center; gap:1rem;">
                <?= avatar_html($parent['prenom'], $parent['nom'], null, 'md') ?>
                <div>
                    <span class="eyebrow">Tableau de bord parent</span>
                    <h1 style="font-size:1.3rem;"><?= e($parent['prenom'] . ' ' . $parent['nom']) ?></h1>
                </div>
            </div>
        </header>

        <div class="dash-content">
            <div class="card">
                <h2 class="card-title">Vos informations</h2>
                <p>Téléphone : <?= e($parent['telephone'] ?: 'non renseigné') ?></p>
                <p>Ville : <?= e($parent['ville']) ?></p>
                <p>Profession : <?= e($parent['profession'] ?: 'non renseignée') ?></p>
            </div>

            <div class="card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                    <h2 class="card-title" style="margin-bottom:0;">Mes enfants</h2>
                    <a href="mon-enfant.php" class="btn btn-outline btn-sm">+ Ajouter</a>
                </div>
                <?php if (!$enfants): ?>
                    <div class="etat-vide">
                        <p>Aucun enfant enregistré. <a href="mon-enfant.php">En ajouter un</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="cards-grid">
                        <?php foreach ($enfants as $enfant): ?>
                            <div class="card" style="box-shadow:none; background: var(--color-bg);">
                                <div style="display:flex; align-items:center; gap:.75rem; margin-bottom:.6rem;">
                                    <?= avatar_html($enfant['prenom'], $enfant['nom'], null, 'sm') ?>
                                    <strong><?= e($enfant['prenom'] . ' ' . $enfant['nom']) ?></strong>
                                </div>
                                <p>Âge : <?= (int) $enfant['age'] ?> ans</p>
                                <p>Allergie : <?= e($enfant['allergie'] ?: 'aucune') ?></p>
                                <p>Besoin spécifique : <?= e($enfant['besoin_specifique'] ?: '—') ?></p>
                                <a href="modifier-enfant.php?id=<?= (int) $enfant['id'] ?>" class="btn btn-outline btn-sm" style="margin-top:.75rem;">Modifier</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                    <h2 class="card-title" style="margin-bottom:0;">Mes contrats</h2>
                    <?php if ($contrats): ?>
                        <a href="export-contrats.php" class="btn btn-outline btn-sm">⬇ Exporter en CSV</a>
                    <?php endif; ?>
                </div>
                <?php if ($erreurs_avis): ?>
                    <ul class="erreur">
                        <?php foreach ($erreurs_avis as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!$contrats): ?>
                    <div class="etat-vide">
                        <p>Aucune proposition envoyée. <a href="liste-nounous.php">Trouver une nounou</a>.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($contrats as $c): ?>
                        <?php [$libelle, $classe] = $libelles_statut[$c['statut']]; ?>
                        <div class="card" style="box-shadow:none; background: var(--color-bg); margin-bottom:.75rem;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;">
                                <div>
                                    <strong><?= e($c['titre']) ?></strong>
                                    <p class="help-text">
                                        Avec <?= e($c['nounou_prenom'] . ' ' . $c['nounou_nom']) ?> —
                                        <?= number_format((float) $c['montant'], 0, ',', ' ') ?> FCFA
                                    </p>
                                </div>
                                <span class="<?= $classe ?>"><?= e($libelle) ?></span>
                            </div>

                            <?php if ($c['statut'] === 'acceptee'): ?>
                                <form method="post" action="dashboard-parent.php" style="margin-top:.75rem;">
                                    <?= champ_csrf() ?>
                                    <input type="hidden" name="contrat_id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" name="marquer_termine" class="btn btn-outline btn-sm">Marquer comme terminé</button>
                                </form>
                            <?php elseif ($c['statut'] === 'terminee' && !$c['deja_note']): ?>
                                <form method="post" action="dashboard-parent.php" style="margin-top:.75rem;">
                                    <?= champ_csrf() ?>
                                    <input type="hidden" name="contrat_id" value="<?= (int) $c['id'] ?>">
                                    <div class="field">
                                        <label for="note-<?= (int) $c['id'] ?>">Votre note</label>
                                        <select id="note-<?= (int) $c['id'] ?>" name="note" required>
                                            <option value="5">★★★★★ Excellent</option>
                                            <option value="4">★★★★☆ Très bien</option>
                                            <option value="3">★★★☆☆ Correct</option>
                                            <option value="2">★★☆☆☆ Décevant</option>
                                            <option value="1">★☆☆☆☆ Très décevant</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="commentaire-<?= (int) $c['id'] ?>">Commentaire (facultatif)</label>
                                        <textarea id="commentaire-<?= (int) $c['id'] ?>" name="commentaire" maxlength="500"></textarea>
                                    </div>
                                    <button type="submit" name="laisser_avis" class="btn btn-primary btn-sm">Laisser un avis</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <footer class="site-footer">
            <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
            <p>&copy; 2024 <?= e(APP_NOM) ?>. Projet étudiant, à but non commercial.</p>
        </footer>
    </div>
</div>
</body>
</html>
