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
require_once __DIR__ . '/../src/bootstrap.php';

$moi = exiger_connexion();

$avec = filter_input(INPUT_GET, 'avec', FILTER_VALIDATE_INT);
$role_avec = $avec !== false && $avec !== null ? role_du_compte($bdd, $avec) : null;

// La messagerie n'a de sens qu'entre un parent et une nounou : rien
// n'empêchait jusqu'ici deux parents (ou deux nounous) de s'écrire, ce que
// la plateforme ne propose nulle part ailleurs comme fonctionnalité.
if ($avec === false || $avec === null || $avec === $moi['id'] || $role_avec === null || $role_avec === $moi['role']) {
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

// Marqués lus dès l'ouverture de la conversation, pas seulement à l'envoi
// d'une réponse : sinon le badge de non-lus resterait allumé après lecture.
marquer_conversation_lue($bdd, (int) $moi['id'], $avec);

$req = $bdd->prepare('SELECT nom, prenom FROM utilisateurs WHERE id = ?');
$req->execute([$avec]);
$interlocuteur = $req->fetch();

$photo_interlocuteur = null;
if ($moi['role'] === 'parent') {
    // Seule une nounou a une photo de profil (le champ vit sur la table `nounous`).
    $req = $bdd->prepare('SELECT photo_profil FROM nounous WHERE utilisateur_id = ?');
    $req->execute([$avec]);
    $photo_interlocuteur = $req->fetchColumn() ?: null;
}

$messages = conversation($bdd, (int) $moi['id'], $avec);
$retour   = $moi['role'] === 'nounou' ? 'dashboard-nounou.php' : 'dashboard-parent.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie — <?= e(APP_NOM) ?></title>
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="assets/css/messages.css">
</head>
<body>
<div class="dash-shell">
    <?= sidebar_html($bdd, (int) $moi['id'], $moi['role'], '') ?>
    <div class="dash-main">
        <header class="dash-topbar">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu">☰</button>
            <div>
                <span class="eyebrow">Messagerie</span>
                <h1 style="font-size:1.3rem;">Conversation</h1>
            </div>
        </header>

        <div class="dash-content" style="max-width: 760px;">
            <p><a href="<?= e($retour) ?>">&larr; Retour au tableau de bord</a></p>

            <div class="conversation-header">
                <?= avatar_html($interlocuteur['prenom'], $interlocuteur['nom'], $photo_interlocuteur, 'md') ?>
                <h1><?= e($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']) ?></h1>
            </div>

            <section id="messages">
                <?php if (!$messages): ?>
                    <div class="etat-vide"><p>Aucun message pour l'instant. Écrivez le premier.</p></div>
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

            <form method="post" action="messages.php?avec=<?= $avec ?>" class="card">
                <?= champ_csrf() ?>
                <textarea name="message" rows="4" maxlength="2000" required placeholder="Écrire un message…"></textarea>
                <button type="submit" class="btn-block">Envoyer</button>
            </form>
        </div>

        <footer class="site-footer">
            <p><?= e(APP_NOM) ?> — trouvez la garde d'enfants qu'il vous faut.</p>
        </footer>
    </div>
</div>
</body>
</html>
