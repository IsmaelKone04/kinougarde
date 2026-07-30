<?php
/**
 * Messagerie : une conversation entre le compte connecté et un autre compte.
 *
 * Remplace l'ancien trio messages.php / process_message.php / repondre_message.php,
 * qui écrivait dans deux tables différentes (`messages` et `messsages`), sous
 * deux noms de colonnes différents (`message` et `content`), et dont l'un des
 * trois concaténait directement les données du formulaire dans la requête SQL.
 */

declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$moi = exiger_connexion();

$avec = filter_input(INPUT_GET, 'avec', FILTER_VALIDATE_INT);
if ($avec === false || $avec === null || $avec === $moi['id'] || !compte_existe($bdd, $avec)) {
    http_response_code(400);
    exit('Destinataire invalide.');
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();

    $contenu = trim((string) ($_POST['message'] ?? ''));
    if ($contenu === '') {
        $erreur = 'Le message est vide.';
    } elseif (mb_strlen($contenu) > 2000) {
        $erreur = 'Le message est trop long (2000 caractères maximum).';
    } else {
        envoyer_message($bdd, (int) $moi['id'], $avec, $contenu);
        // Redirection après écriture : sans elle, actualiser la page renvoie
        // le formulaire et duplique le message.
        header('Location: messages.php?avec=' . $avec);
        exit;
    }
}

$req = $bdd->prepare('SELECT nom, prenom FROM utilisateurs WHERE id = ?');
$req->execute([$avec]);
$interlocuteur = $req->fetch();

$messages = conversation($bdd, (int) $moi['id'], $avec);
$retour   = $moi['role'] === 'nounou' ? 'dashboard_nounou.php' : 'dashboard_parent.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="message.css">
</head>
<body>

<p><a href="<?= e($retour) ?>">&larr; Retour au tableau de bord</a></p>
<h1>Conversation avec <?= e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) ?></h1>

<section id="messages">
    <?php if (!$messages): ?>
        <p>Aucun message pour l'instant. Écrivez le premier.</p>
    <?php endif; ?>

    <?php foreach ($messages as $m): ?>
        <?php $de_moi = (int) $m['expediteur_id'] === (int) $moi['id']; ?>
        <p class="<?= $de_moi ? 'envoye' : 'recu' ?>">
            <?= nl2br(e($m['contenu'])) ?>
            <small><?= e($m['envoye_le']) ?></small>
        </p>
    <?php endforeach; ?>
</section>

<?php if ($erreur !== null): ?>
    <p class="erreur"><?= e($erreur) ?></p>
<?php endif; ?>

<form method="post" action="messages.php?avec=<?= $avec ?>">
    <?= champ_csrf() ?>
    <textarea name="message" rows="4" cols="50" maxlength="2000" required></textarea><br>
    <button type="submit">Envoyer</button>
</form>

</body>
</html>
