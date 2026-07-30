<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$moi = exiger_connexion('nounou');

$nounou = nounou_par_utilisateur($bdd, (int) $moi['id']);
if ($nounou === null) {
    http_response_code(500);
    exit('Profil nounou introuvable pour ce compte.');
}

// L'ancienne version faisait get_nounou_info() qui ne retournait que le nom et
// l'e-mail, puis passait $nounou_info['id'] — une clé absente du tableau — à
// toutes les requêtes suivantes.
$contrats      = contrats_nounou($bdd, (int) $nounou['id']);
$montant_total = total_contrats_nounou($bdd, (int) $nounou['id']);

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
    <link rel="stylesheet" href="dashboard_nounou.css">
</head>
<body>
<header>
    <button class="menu-toggle" onclick="toggleMenu()">☰ Menu</button>
</header>
<nav>
    <ul>
        <li><a href="home.php">Accueil</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
    </ul>
</nav>
<div class="menu" id="menu">
    <ul>
        <li><a href="home.php">Accueil</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
    </ul>
</div>

<div class="container">
    <h2>Tableau de bord</h2>

    <h3>Informations personnelles</h3>
    <p>Nom : <?= e($nounou['prenom'] . ' ' . $nounou['nom']) ?></p>
    <p>E-mail : <?= e($nounou['email']) ?></p>
    <p>Ville : <?= e($nounou['ville']) ?></p>
    <p>Service : <?= e($nounou['type_service']) ?></p>
    <p>Tarif : <?= number_format((float) $nounou['montant'], 0, ',', ' ') ?> FCFA / <?= e($nounou['paiement']) ?></p>

    <h3>Contrats en cours</h3>
    <?php if (!$contrats): ?>
        <p>Aucun contrat en cours.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($contrats as $c): ?>
                <li>
                    <?= e($c['titre']) ?> —
                    <?= number_format((float) $c['montant'], 0, ',', ' ') ?> FCFA
                    (<?= e($c['parent_prenom'] . ' ' . $c['parent_nom']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Montant total</h3>
    <p><?= number_format($montant_total, 0, ',', ' ') ?> FCFA</p>

    <h3>Messages reçus</h3>
    <?php if (!$messages): ?>
        <p>Aucun message reçu.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($messages as $m): ?>
                <li>
                    <strong><?= e($m['prenom'] . ' ' . $m['nom']) ?></strong> —
                    <?= e($m['contenu']) ?>
                    <a href="messages.php?avec=<?= (int) $m['expediteur_id'] ?>">Répondre</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<footer>
    <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
    <p>&copy; 2024 <?= e(APP_NOM) ?>. Projet étudiant, à but non commercial.</p>
</footer>

<script>
    function toggleMenu() {
        document.getElementById("menu").classList.toggle("open");
    }
</script>
</body>
</html>
