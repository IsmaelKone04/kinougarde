<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

exiger_connexion();

$nounous = lister_nounous($bdd);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nounous disponibles — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="liste_nounous.css">
</head>
<body>

<p><a href="dashboard_parent.php">&larr; Retour au tableau de bord</a></p>
<h1>Nounous disponibles</h1>

<?php if (!$nounous): ?>
    <p>Aucun profil de nounou pour le moment.</p>
<?php else: ?>
    <?php foreach ($nounous as $n): ?>
        <div class="profile">
            <h3><?= e($n['prenom'] . ' ' . $n['nom']) ?></h3>
            <?php if (!empty($n['photo_profil'])): ?>
                <img src="uploads/<?= e(basename($n['photo_profil'])) ?>"
                     alt="Photo de profil">
            <?php endif; ?>
            <p>Ville : <?= e($n['ville']) ?></p>
            <p>Service : <?= e($n['type_service']) ?></p>
            <p>Tarif : <?= number_format((float) $n['montant'], 0, ',', ' ') ?> FCFA / <?= e($n['paiement']) ?></p>
            <a href="profil-nounou.php?id=<?= (int) $n['id'] ?>">Voir le profil détaillé</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".profile").forEach(function (profile, index) {
        setTimeout(function () {
            profile.classList.add("show");
        }, index * 150);
    });
});
</script>
</body>
</html>
