<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

// L'ancienne version n'exigeait aucune connexion et construisait la requête
// par concaténation : profil-nounou.php?id=0 UNION SELECT … suffisait à lire
// la table entière, colonne mot de passe comprise.
exiger_connexion();

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil nounou — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/profil-nounou.css">
</head>
<body>

<p><a href="liste-nounous.php">&larr; Retour à la liste</a></p>

<?php if (!empty($introuvable)): ?>
    <p>Aucun profil de nounou ne correspond à cet identifiant.</p>
<?php else: ?>
    <h2><?= e($nounou['prenom'] . ' ' . $nounou['nom']) ?></h2>
    <p>Ville : <?= e($nounou['ville']) ?></p>
    <p>Type de service : <?= e($nounou['type_service']) ?></p>
    <p>Horaires : <?= e($nounou['horaires']) ?></p>
    <p>Tarif : <?= number_format((float) $nounou['montant'], 0, ',', ' ') ?> FCFA / <?= e($nounou['paiement']) ?></p>

    <p>
        <a href="messages.php?avec=<?= (int) $nounou['utilisateur_id'] ?>">
            Envoyer un message à <?= e($nounou['prenom']) ?>
        </a>
    </p>
<?php endif; ?>

</body>
</html>
