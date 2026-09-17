<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$moi = exiger_connexion('nounou');

$nounou = nounou_par_utilisateur($bdd, (int) $moi['id']);
if ($nounou === null) {
    http_response_code(500);
    exit('Profil nounou introuvable pour ce compte.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reponse_contrat'])) {
    verifier_csrf();
    repondre_contrat(
        $bdd,
        (int) $_POST['contrat_id'],
        (int) $nounou['id'],
        $_POST['reponse_contrat'] === 'accepter'
    );
    header('Location: dashboard-nounou.php');
    exit;
}

// L'ancienne version faisait get_nounou_info() qui ne retournait que le nom et
// l'e-mail, puis passait $nounou_info['id'] — une clé absente du tableau — à
// toutes les requêtes suivantes.
$tous_les_contrats = contrats_nounou($bdd, (int) $nounou['id']);
$en_attente         = array_filter($tous_les_contrats, fn ($c) => $c['statut'] === 'en_attente');
$autres_contrats    = array_filter($tous_les_contrats, fn ($c) => $c['statut'] !== 'en_attente');
$contrats_actifs    = array_filter($tous_les_contrats, fn ($c) => $c['statut'] === 'acceptee');
$montant_total       = total_contrats_nounou($bdd, (int) $nounou['id']);

$libelles_statut = [
    'acceptee' => ['Acceptée', 'badge-accent'],
    'refusee'  => ['Refusée', 'badge'],
    'terminee' => ['Terminée', 'badge'],
];

$derniers = $bdd->prepare(
    'SELECT m.expediteur_id, m.contenu, m.envoye_le, u.nom, u.prenom
       FROM messages m
       JOIN utilisateurs u ON u.id = m.expediteur_id
      WHERE m.destinataire_id = ?
      ORDER BY m.envoye_le DESC
      LIMIT 20'
);
$derniers->execute([(int) $moi['id']]);
$messages = $derniers->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord nounou — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/dashboard-nounou.css">
    <script src="assets/js/menu.js" defer></script>
</head>
<body>
<div class="dash-shell">
    <?= sidebar_html($bdd, (int) $moi['id'], $moi['role'], 'dashboard-nounou.php') ?>
    <div class="dash-main">
        <header class="dash-topbar">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰</button>
            <div style="display:flex; align-items:center; gap:1rem;">
                <?= avatar_html($nounou['prenom'], $nounou['nom'], $nounou['photo_profil'], 'md') ?>
                <div>
                    <span class="eyebrow">Tableau de bord nounou</span>
                    <h1 style="font-size:1.3rem;"><?= e($nounou['prenom'] . ' ' . $nounou['nom']) ?></h1>
                </div>
            </div>
        </header>

        <div class="dash-content">
            <div class="cards-grid" style="margin-bottom:1.25rem;">
                <div class="card" style="text-align:center;">
                    <span class="eyebrow">Contrats en cours</span>
                    <p style="font-size:1.8rem; font-weight:800;"><?= count($contrats_actifs) ?></p>
                </div>
                <div class="card" style="text-align:center;">
                    <span class="eyebrow">Montant total</span>
                    <p style="font-size:1.8rem; font-weight:800;"><?= number_format($montant_total, 0, ',', ' ') ?> FCFA</p>
                </div>
                <div class="card" style="text-align:center;">
                    <span class="eyebrow">Tarif affiché</span>
                    <p style="font-size:1.8rem; font-weight:800;"><?= number_format((float) $nounou['montant'], 0, ',', ' ') ?> FCFA</p>
                    <p class="help-text">par <?= e($nounou['paiement']) ?></p>
                </div>
            </div>

            <div class="card">
                <h2 class="card-title">Informations personnelles</h2>
                <p>E-mail : <?= e($nounou['email']) ?></p>
                <p>Ville : <?= e($nounou['ville']) ?></p>
                <p>Service : <?= e($nounou['type_service']) ?></p>
                <p>Horaires : <?= e($nounou['horaires'] ?: 'non précisés') ?></p>
            </div>

            <?php if ($en_attente): ?>
                <div class="card">
                    <h2 class="card-title">Propositions en attente de réponse</h2>
                    <?php foreach ($en_attente as $c): ?>
                        <div class="card" style="box-shadow:none; background: var(--color-bg); margin-bottom:.75rem;">
                            <strong><?= e($c['titre']) ?></strong>
                            <p><?= number_format((float) $c['montant'], 0, ',', ' ') ?> FCFA — <?= e($c['parent_prenom'] . ' ' . $c['parent_nom']) ?></p>
                            <form method="post" action="dashboard-nounou.php" style="display:flex; gap:.5rem; margin-top:.75rem;">
                                <?= champ_csrf() ?>
                                <input type="hidden" name="contrat_id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" name="reponse_contrat" value="accepter" class="btn btn-primary btn-sm">Accepter</button>
                                <button type="submit" name="reponse_contrat" value="refuser" class="btn btn-outline btn-sm">Refuser</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                    <h2 class="card-title" style="margin-bottom:0;">Mes contrats</h2>
                    <?php if ($tous_les_contrats): ?>
                        <a href="export-contrats.php" class="btn btn-outline btn-sm">⬇ Exporter en CSV</a>
                    <?php endif; ?>
                </div>
                <?php if (!$autres_contrats): ?>
                    <div class="etat-vide"><p>Aucun contrat pour l'instant.</p></div>
                <?php else: ?>
                    <?php foreach ($autres_contrats as $c): ?>
                        <?php [$libelle, $classe] = $libelles_statut[$c['statut']]; ?>
                        <div class="card" style="box-shadow:none; background: var(--color-bg); margin-bottom:.75rem;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;">
                                <div>
                                    <strong><?= e($c['titre']) ?></strong>
                                    <p><?= number_format((float) $c['montant'], 0, ',', ' ') ?> FCFA — <?= e($c['parent_prenom'] . ' ' . $c['parent_nom']) ?></p>
                                </div>
                                <span class="<?= $classe ?>"><?= e($libelle) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2 class="card-title">Messages reçus</h2>
                <?php if (!$messages): ?>
                    <div class="etat-vide"><p>Aucun message reçu.</p></div>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div style="display:flex; align-items:center; gap:.75rem; padding:.75rem 0; border-bottom:1px solid var(--color-border);">
                            <?= avatar_html($m['prenom'], $m['nom'], null, 'sm') ?>
                            <div style="flex:1;">
                                <strong><?= e($m['prenom'] . ' ' . $m['nom']) ?></strong>
                                <p class="help-text" style="color:var(--color-text);"><?= e($m['contenu']) ?></p>
                            </div>
                            <a href="messages.php?avec=<?= (int) $m['expediteur_id'] ?>" class="btn btn-outline btn-sm">Répondre</a>
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
