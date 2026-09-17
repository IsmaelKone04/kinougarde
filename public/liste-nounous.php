<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$moi = exiger_connexion();

// GET plutôt que POST : une recherche doit pouvoir être partagée ou remise
// dans les favoris avec ses filtres, ce qu'un formulaire en POST empêcherait.
$filtre_ville     = trim((string) ($_GET['ville'] ?? ''));
$filtre_service   = trim((string) ($_GET['service'] ?? ''));
$filtre_tarif_max = (string) ($_GET['tarif_max'] ?? '');
$page             = max(1, (int) ($_GET['page'] ?? 1));

$filtres = [
    'ville'     => $filtre_ville,
    'service'   => $filtre_service,
    'tarif_max' => $filtre_tarif_max !== '' ? (float) $filtre_tarif_max : null,
];

$total       = compter_nounous($bdd, $filtres);
$total_pages = max(1, (int) ceil($total / NOUNOUS_PAR_PAGE));
$page        = min($page, $total_pages);
$nounous     = lister_nounous($bdd, $filtres, $page);

$villes = villes_disponibles($bdd);

// Reconstruit la chaîne de filtres pour les liens de pagination, sans le
// paramètre "page" lui-même (rajouté par chaque lien).
$query_filtres = array_filter([
    'ville'     => $filtre_ville,
    'service'   => $filtre_service,
    'tarif_max' => $filtre_tarif_max,
], static fn ($v) => $v !== '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nounous disponibles — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/liste-nounous.css">
    <script src="assets/js/liste-nounous.js" defer></script>
</head>
<body>
<div class="dash-shell">
    <?= sidebar_html($bdd, (int) $moi['id'], $moi['role'], 'liste-nounous.php') ?>
    <div class="dash-main">
        <header class="dash-topbar">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰</button>
            <div>
                <span class="eyebrow">Trouver une garde</span>
                <h1 style="font-size:1.3rem;">Nounous disponibles</h1>
            </div>
        </header>

        <div class="dash-content" style="max-width: 1000px;">
            <form method="get" action="liste-nounous.php" class="card filtres-nounou">
                <div class="field-row">
                    <div class="field">
                        <label for="ville">Ville</label>
                        <select id="ville" name="ville">
                            <option value="">Toutes les villes</option>
                            <?php foreach ($villes as $v): ?>
                                <option value="<?= e($v) ?>" <?= $filtre_ville === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="service">Service</label>
                        <input type="text" id="service" name="service" placeholder="Garde, ménage, devoirs…"
                               value="<?= e($filtre_service) ?>">
                    </div>
                    <div class="field">
                        <label for="tarif_max">Tarif maximum (FCFA)</label>
                        <input type="number" id="tarif_max" name="tarif_max" min="0" step="500"
                               value="<?= e($filtre_tarif_max) ?>">
                    </div>
                </div>
                <div style="display:flex; gap:.75rem;">
                    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                    <?php if ($filtre_ville !== '' || $filtre_service !== '' || $filtre_tarif_max !== ''): ?>
                        <a href="liste-nounous.php" class="btn btn-outline btn-sm">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </form>

            <p class="help-text" style="margin-bottom:1.25rem;"><?= $total ?> profil<?= $total > 1 ? 's' : '' ?> à découvrir<?= $total_pages > 1 ? sprintf(' — page %d sur %d', $page, $total_pages) : '' ?>.</p>

            <?php if (!$nounous): ?>
                <div class="etat-vide"><p>Aucun profil ne correspond à ces critères.</p></div>
            <?php else: ?>
                <div class="nounou-grid">
                    <?php foreach ($nounous as $n): ?>
                        <div class="profile">
                            <?= avatar_html($n['prenom'], $n['nom'], $n['photo_profil'], 'lg') ?>
                            <h3>
                                <?= e($n['prenom'] . ' ' . $n['nom']) ?>
                                <?php if ((int) $n['verifiee'] === 1): ?>
                                    <span class="badge-verifiee" title="Identité vérifiée par kiNouGarde">✅ Vérifiée</span>
                                <?php endif; ?>
                            </h3>
                            <p class="profile-ville">📍 <?= e($n['ville']) ?></p>
                            <p class="profile-detail">🕒 <?= e($n['horaires'] ?: 'horaires à convenir') ?></p>
                            <?php if ($n['note_moyenne'] !== null): ?>
                                <p class="profile-detail">⭐ <?= e((string) $n['note_moyenne']) ?>/5 (<?= (int) $n['nb_avis'] ?>)</p>
                            <?php elseif ((int) $n['nb_contrats'] > 0): ?>
                                <p class="profile-detail">✅ <?= (int) $n['nb_contrats'] ?> famille<?= $n['nb_contrats'] > 1 ? 's' : '' ?> accompagnée<?= $n['nb_contrats'] > 1 ? 's' : '' ?></p>
                            <?php endif; ?>
                            <div class="profile-badges">
                                <span class="badge"><?= e($n['type_service']) ?></span>
                                <span class="badge badge-accent"><?= number_format((float) $n['montant'], 0, ',', ' ') ?> FCFA / <?= e($n['paiement']) ?></span>
                            </div>
                            <a href="profil-nounou.php?id=<?= (int) $n['id'] ?>" class="btn btn-primary btn-sm btn-block">Voir le profil</a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav class="pagination" aria-label="Pages de résultats">
                        <?php if ($page > 1): ?>
                            <a class="btn btn-outline btn-sm"
                               href="liste-nounous.php?<?= e(http_build_query($query_filtres + ['page' => $page - 1])) ?>">← Précédent</a>
                        <?php endif; ?>
                        <span class="help-text">Page <?= $page ?> / <?= $total_pages ?></span>
                        <?php if ($page < $total_pages): ?>
                            <a class="btn btn-outline btn-sm"
                               href="liste-nounous.php?<?= e(http_build_query($query_filtres + ['page' => $page + 1])) ?>">Suivant →</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <footer class="site-footer">
            <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
        </footer>
    </div>
</div>
</body>
</html>
