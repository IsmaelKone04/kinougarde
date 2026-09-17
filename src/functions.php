<?php
/**
 * Requêtes métier partagées.
 *
 * Toutes prennent la connexion PDO en paramètre plutôt que d'aller la chercher
 * dans `global $conn` : une fonction qui dépend d'une variable globale ne peut
 * ni être testée ni être réutilisée ailleurs.
 */

declare(strict_types=1);

/** Profil nounou complet (compte + profil), ou null. */
function profil_nounou(PDO $bdd, int $nounou_id): ?array
{
    $req = $bdd->prepare(
        'SELECT n.*, u.nom, u.prenom, u.email, u.telephone, u.id AS utilisateur_id,
                (SELECT COUNT(*) FROM contrats_emploi c
                  WHERE c.nounou_id = n.id AND c.statut IN (\'acceptee\', \'terminee\')) AS nb_contrats,
                (SELECT ROUND(AVG(a.note), 1) FROM avis a WHERE a.nounou_id = n.id) AS note_moyenne,
                (SELECT COUNT(*) FROM avis a WHERE a.nounou_id = n.id) AS nb_avis
           FROM nounous n
           JOIN utilisateurs u ON u.id = n.utilisateur_id
          WHERE n.id = ?'
    );
    $req->execute([$nounou_id]);
    return $req->fetch() ?: null;
}

/** Profil nounou à partir de l'identifiant de compte. */
function nounou_par_utilisateur(PDO $bdd, int $utilisateur_id): ?array
{
    $req = $bdd->prepare(
        'SELECT n.*, u.nom, u.prenom, u.email, u.telephone
           FROM nounous n
           JOIN utilisateurs u ON u.id = n.utilisateur_id
          WHERE n.utilisateur_id = ?'
    );
    $req->execute([$utilisateur_id]);
    return $req->fetch() ?: null;
}

/** Profil parent à partir de l'identifiant de compte. */
function parent_par_utilisateur(PDO $bdd, int $utilisateur_id): ?array
{
    $req = $bdd->prepare(
        'SELECT p.*, u.nom, u.prenom, u.email, u.telephone
           FROM parents p
           JOIN utilisateurs u ON u.id = p.utilisateur_id
          WHERE p.utilisateur_id = ?'
    );
    $req->execute([$utilisateur_id]);
    return $req->fetch() ?: null;
}

/**
 * Liste des nounous, avec filtres optionnels (ville exacte, service — recherche
 * partielle —, tarif maximum). Tous facultatifs : `lister_nounous($bdd)` sans
 * second argument renvoie la liste complète, comme avant.
 */
/**
 * Met à jour le profil d'une nounou. Filtré sur utilisateur_id, jamais sur
 * l'id du profil transmis par un formulaire : la personne connectée ne peut
 * modifier que le sien.
 */
function mettre_a_jour_profil_nounou(
    PDO $bdd,
    int $utilisateur_id,
    string $ville,
    string $type_service,
    string $horaires,
    float $montant,
    string $paiement,
    ?string $photo
): void {
    if ($photo !== null) {
        $req = $bdd->prepare(
            'UPDATE nounous SET ville = ?, type_service = ?, horaires = ?, montant = ?, paiement = ?, photo_profil = ?
              WHERE utilisateur_id = ?'
        );
        $req->execute([$ville, $type_service, $horaires, $montant, $paiement, $photo, $utilisateur_id]);
    } else {
        $req = $bdd->prepare(
            'UPDATE nounous SET ville = ?, type_service = ?, horaires = ?, montant = ?, paiement = ?
              WHERE utilisateur_id = ?'
        );
        $req->execute([$ville, $type_service, $horaires, $montant, $paiement, $utilisateur_id]);
    }
}

/** Construit la clause WHERE + les paramètres liés, partagés entre le
 *  listing paginé et le comptage total (pour ne pas dupliquer les filtres). */
function conditions_filtre_nounous(array $filtres): array
{
    $conditions = [];
    $params     = [];

    if (!empty($filtres['ville'])) {
        $conditions[] = 'n.ville = ?';
        $params[]     = $filtres['ville'];
    }
    if (!empty($filtres['service'])) {
        $conditions[] = 'n.type_service LIKE ?';
        $params[]     = '%' . $filtres['service'] . '%';
    }
    if (!empty($filtres['tarif_max'])) {
        $conditions[] = 'n.montant <= ?';
        $params[]     = $filtres['tarif_max'];
    }

    return [$conditions, $params];
}

const NOUNOUS_PAR_PAGE = 9;

function lister_nounous(PDO $bdd, array $filtres = [], int $page = 1): array
{
    [$conditions, $params] = conditions_filtre_nounous($filtres);
    $ou = $conditions ? ('WHERE ' . implode(' AND ', $conditions) . ' ') : '';

    // LIMIT/OFFSET interpolés directement : PDO ne les lie pas de façon
    // fiable en paramètres liés sur tous les pilotes. Sans danger d'injection
    // ici, les deux valeurs sont des entiers déjà bornés ci-dessous.
    $page     = max(1, $page);
    $decalage = ($page - 1) * NOUNOUS_PAR_PAGE;

    $req = $bdd->prepare(
        'SELECT n.id, n.ville, n.type_service, n.montant, n.paiement, n.photo_profil,
                n.horaires, n.sexe, n.verifiee,
                u.nom, u.prenom,
                (SELECT COUNT(*) FROM contrats_emploi c
                  WHERE c.nounou_id = n.id AND c.statut IN (\'acceptee\', \'terminee\')) AS nb_contrats,
                (SELECT ROUND(AVG(a.note), 1) FROM avis a WHERE a.nounou_id = n.id) AS note_moyenne,
                (SELECT COUNT(*) FROM avis a WHERE a.nounou_id = n.id) AS nb_avis
           FROM nounous n
           JOIN utilisateurs u ON u.id = n.utilisateur_id
          ' . $ou . 'ORDER BY u.nom, u.prenom
          LIMIT ' . NOUNOUS_PAR_PAGE . ' OFFSET ' . $decalage
    );
    $req->execute($params);
    return $req->fetchAll();
}

/** Nombre total de nounous correspondant aux filtres, pour calculer le
 *  nombre de pages sans charger tous les profils. */
function compter_nounous(PDO $bdd, array $filtres = []): int
{
    [$conditions, $params] = conditions_filtre_nounous($filtres);
    $ou = $conditions ? ('WHERE ' . implode(' AND ', $conditions) . ' ') : '';

    $req = $bdd->prepare(
        'SELECT COUNT(*) FROM nounous n JOIN utilisateurs u ON u.id = n.utilisateur_id ' . $ou
    );
    $req->execute($params);
    return (int) $req->fetchColumn();
}

/** Villes distinctes où au moins une nounou est inscrite, pour peupler un filtre. */
function villes_disponibles(PDO $bdd): array
{
    return $bdd->query('SELECT DISTINCT ville FROM nounous ORDER BY ville')->fetchAll(PDO::FETCH_COLUMN);
}

function contrats_nounou(PDO $bdd, int $nounou_id): array
{
    $req = $bdd->prepare(
        'SELECT c.*, u.nom AS parent_nom, u.prenom AS parent_prenom
           FROM contrats_emploi c
           JOIN parents p      ON p.id = c.parent_id
           JOIN utilisateurs u ON u.id = p.utilisateur_id
          WHERE c.nounou_id = ?
          ORDER BY c.date_signature DESC'
    );
    $req->execute([$nounou_id]);
    return $req->fetchAll();
}

/**
 * Propose un contrat : créé `en_attente`, jamais directement `acceptee` — la
 * nounou doit se prononcer (voir repondre_contrat()). C'est ce qui manquait
 * pour que `contrats_emploi` corresponde à un vrai flux plutôt qu'à des
 * lignes qui n'existaient que dans les données de démonstration.
 */
function proposer_contrat(PDO $bdd, int $parent_id, int $nounou_id, string $titre, float $montant): int
{
    $req = $bdd->prepare(
        "INSERT INTO contrats_emploi (parent_id, nounou_id, titre, montant, statut)
         VALUES (?, ?, ?, ?, 'en_attente')"
    );
    $req->execute([$parent_id, $nounou_id, $titre, $montant]);
    return (int) $bdd->lastInsertId();
}

/** Contrats proposés par un parent, les plus récents en premier. */
function contrats_parent(PDO $bdd, int $parent_id): array
{
    $req = $bdd->prepare(
        'SELECT c.*, u.nom AS nounou_nom, u.prenom AS nounou_prenom,
                (SELECT 1 FROM avis a WHERE a.contrat_id = c.id) AS deja_note
           FROM contrats_emploi c
           JOIN nounous n      ON n.id = c.nounou_id
           JOIN utilisateurs u ON u.id = n.utilisateur_id
          WHERE c.parent_id = ?
          ORDER BY c.date_signature DESC'
    );
    $req->execute([$parent_id]);
    return $req->fetchAll();
}

/**
 * La nounou accepte ou refuse une proposition qui la concerne.
 *
 * `AND nounou_id = ? AND statut = 'en_attente'` dans le WHERE : sans le
 * premier filtre, une nounou pourrait répondre à la proposition d'une
 * collègue en devinant l'identifiant ; sans le second, une proposition déjà
 * traitée pourrait être re-répondue.
 */
function repondre_contrat(PDO $bdd, int $contrat_id, int $nounou_id, bool $accepter): bool
{
    $req = $bdd->prepare(
        "UPDATE contrats_emploi SET statut = ?
          WHERE id = ? AND nounou_id = ? AND statut = 'en_attente'"
    );
    $req->execute([$accepter ? 'acceptee' : 'refusee', $contrat_id, $nounou_id]);
    return $req->rowCount() > 0;
}

/** Le parent marque un contrat accepté comme terminé (ouvre le droit à un avis). */
function marquer_contrat_termine(PDO $bdd, int $contrat_id, int $parent_id): bool
{
    $req = $bdd->prepare(
        "UPDATE contrats_emploi SET statut = 'terminee'
          WHERE id = ? AND parent_id = ? AND statut = 'acceptee'"
    );
    $req->execute([$contrat_id, $parent_id]);
    return $req->rowCount() > 0;
}

/**
 * Un contrat sur lequel on peut laisser un avis : terminé, appartenant au
 * parent, pas encore noté. Renvoie le nounou_id à noter, ou null sinon.
 */
function contrat_notable(PDO $bdd, int $contrat_id, int $parent_id): ?int
{
    $req = $bdd->prepare(
        "SELECT c.nounou_id
           FROM contrats_emploi c
          WHERE c.id = ? AND c.parent_id = ? AND c.statut = 'terminee'
            AND NOT EXISTS (SELECT 1 FROM avis a WHERE a.contrat_id = c.id)"
    );
    $req->execute([$contrat_id, $parent_id]);
    $nounou_id = $req->fetchColumn();
    return $nounou_id === false ? null : (int) $nounou_id;
}

function laisser_avis(PDO $bdd, int $contrat_id, int $parent_id, int $nounou_id, int $note, ?string $commentaire): void
{
    $req = $bdd->prepare(
        'INSERT INTO avis (contrat_id, parent_id, nounou_id, note, commentaire) VALUES (?, ?, ?, ?, ?)'
    );
    $req->execute([$contrat_id, $parent_id, $nounou_id, $note, $commentaire]);
}

/** Avis reçus par une nounou, les plus récents en premier. */
function avis_nounou(PDO $bdd, int $nounou_id): array
{
    $req = $bdd->prepare(
        'SELECT a.note, a.commentaire, a.cree_le, u.prenom
           FROM avis a
           JOIN parents p      ON p.id = a.parent_id
           JOIN utilisateurs u ON u.id = p.utilisateur_id
          WHERE a.nounou_id = ?
          ORDER BY a.cree_le DESC'
    );
    $req->execute([$nounou_id]);
    return $req->fetchAll();
}

/**
 * Total dû à une nounou.
 *
 * La somme est faite par MySQL, pas en PHP : l'ancienne version chargeait
 * toutes les lignes pour les additionner une à une.
 */
function total_contrats_nounou(PDO $bdd, int $nounou_id): float
{
    // Seuls les contrats acceptés ou terminés comptent : une proposition en
    // attente ou refusée ne représente aucun revenu réel.
    $req = $bdd->prepare(
        "SELECT COALESCE(SUM(montant), 0) FROM contrats_emploi
          WHERE nounou_id = ? AND statut IN ('acceptee', 'terminee')"
    );
    $req->execute([$nounou_id]);
    return (float) $req->fetchColumn();
}

/** Enfants d'un parent. */
function enfants_du_parent(PDO $bdd, int $parent_id): array
{
    $req = $bdd->prepare('SELECT * FROM enfants WHERE parent_id = ? ORDER BY prenom');
    $req->execute([$parent_id]);
    return $req->fetchAll();
}

/**
 * Un enfant, à condition qu'il appartienne bien au parent indiqué.
 *
 * Le `AND parent_id = ?` est le cœur du contrôle d'accès : sans lui, changer
 * l'identifiant dans l'URL donne accès à la fiche d'un enfant d'une autre
 * famille — nom, âge, allergies, besoins spécifiques.
 */
function enfant_du_parent(PDO $bdd, int $enfant_id, int $parent_id): ?array
{
    $req = $bdd->prepare('SELECT * FROM enfants WHERE id = ? AND parent_id = ?');
    $req->execute([$enfant_id, $parent_id]);
    return $req->fetch() ?: null;
}

/** Conversation entre deux comptes, dans l'ordre chronologique. */
function conversation(PDO $bdd, int $moi, int $autre): array
{
    $req = $bdd->prepare(
        'SELECT * FROM messages
          WHERE (expediteur_id = ? AND destinataire_id = ?)
             OR (expediteur_id = ? AND destinataire_id = ?)
          ORDER BY envoye_le'
    );
    $req->execute([$moi, $autre, $autre, $moi]);
    return $req->fetchAll();
}

function envoyer_message(PDO $bdd, int $expediteur_id, int $destinataire_id, string $contenu): void
{
    $req = $bdd->prepare(
        'INSERT INTO messages (expediteur_id, destinataire_id, contenu) VALUES (?, ?, ?)'
    );
    $req->execute([$expediteur_id, $destinataire_id, $contenu]);
}

/**
 * Nombre de messages non lus, toutes conversations confondues.
 *
 * Aucun serveur SMTP n'est configuré sur ce projet (voir README) : ce badge
 * remplace la notification par e-mail qu'on attendrait normalement à la
 * réception d'un message.
 */
function messages_non_lus_total(PDO $bdd, int $utilisateur_id): int
{
    $req = $bdd->prepare('SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND lu = 0');
    $req->execute([$utilisateur_id]);
    return (int) $req->fetchColumn();
}

/** Marque comme lus tous les messages reçus d'un interlocuteur donné. */
function marquer_conversation_lue(PDO $bdd, int $moi, int $avec): void
{
    $req = $bdd->prepare('UPDATE messages SET lu = 1 WHERE destinataire_id = ? AND expediteur_id = ? AND lu = 0');
    $req->execute([$moi, $avec]);
}

/**
 * Vrai si $valeur dépasse $max caractères.
 *
 * Les colonnes VARCHAR ont toujours eu leur longueur — le formulaire, lui,
 * ne posait jamais de `maxlength` : un POST direct plus long qu'une colonne
 * faisait échouer l'insertion avec le message générique « L'inscription a
 * échoué », sans dire pourquoi.
 */
function trop_long(string $valeur, int $max): bool
{
    return mb_strlen($valeur) > $max;
}

/** Rôle d'un compte, ou null s'il n'existe pas. Sert aussi à vérifier son existence. */
function role_du_compte(PDO $bdd, int $utilisateur_id): ?string
{
    $req = $bdd->prepare('SELECT role FROM utilisateurs WHERE id = ?');
    $req->execute([$utilisateur_id]);
    $role = $req->fetchColumn();
    return $role === false ? null : $role;
}

const MAX_TENTATIVES_CONNEXION = 5;
const FENETRE_TENTATIVES_MINUTES = 15;

/**
 * Vrai si trop de tentatives de connexion ont échoué récemment pour cet
 * e-mail, quelle que soit l'IP d'origine (un attaquant distribué sur
 * plusieurs IP ne doit pas contourner la limite).
 *
 * Avant la refonte de septembre 2026, login.php n'avait aucune protection :
 * un script pouvait essayer des mots de passe sans limite.
 */
function trop_de_tentatives(PDO $bdd, string $email): bool
{
    $req = $bdd->prepare(
        'SELECT COUNT(*) FROM tentatives_connexion
          WHERE email = ? AND tentee_le > (NOW() - INTERVAL ? MINUTE)'
    );
    $req->execute([$email, FENETRE_TENTATIVES_MINUTES]);
    return (int) $req->fetchColumn() >= MAX_TENTATIVES_CONNEXION;
}

function enregistrer_tentative_echouee(PDO $bdd, string $email, string $ip): void
{
    $req = $bdd->prepare('INSERT INTO tentatives_connexion (email, adresse_ip) VALUES (?, ?)');
    $req->execute([$email, $ip]);

    // Purge opportuniste : pas de tâche planifiée pour ce projet, donc chaque
    // tentative en profite pour nettoyer l'historique au-delà de la fenêtre.
    $bdd->prepare('DELETE FROM tentatives_connexion WHERE tentee_le < (NOW() - INTERVAL 1 DAY)')->execute();
}

function reinitialiser_tentatives(PDO $bdd, string $email): void
{
    $req = $bdd->prepare('DELETE FROM tentatives_connexion WHERE email = ?');
    $req->execute([$email]);
}

const DUREE_VALIDITE_REINIT_MINUTES = 30;

/**
 * Démarre une réinitialisation de mot de passe : génère un jeton, stocke son
 * empreinte, invalide les jetons précédents du compte. Renvoie le jeton EN
 * CLAIR une seule fois — c'est celui-ci qu'il faut mettre dans le lien envoyé
 * à l'utilisateur, jamais celui stocké en base.
 */
function demarrer_reinitialisation(PDO $bdd, int $utilisateur_id): string
{
    // Un seul jeton actif à la fois : sans ça, un ancien lien resterait
    // valable même après qu'un nouveau a été demandé.
    $bdd->prepare('DELETE FROM reinitialisations_mot_de_passe WHERE utilisateur_id = ?')
        ->execute([$utilisateur_id]);

    $jeton = bin2hex(random_bytes(32));
    $req = $bdd->prepare(
        'INSERT INTO reinitialisations_mot_de_passe (utilisateur_id, jeton_hache, expire_le)
         VALUES (?, ?, NOW() + INTERVAL ? MINUTE)'
    );
    $req->execute([$utilisateur_id, hash('sha256', $jeton), DUREE_VALIDITE_REINIT_MINUTES]);

    return $jeton;
}

/**
 * Vérifie un jeton de réinitialisation et renvoie l'utilisateur_id associé,
 * ou null s'il est absent, expiré ou déjà utilisé.
 */
function verifier_jeton_reinitialisation(PDO $bdd, string $jeton): ?int
{
    $req = $bdd->prepare(
        'SELECT utilisateur_id FROM reinitialisations_mot_de_passe
          WHERE jeton_hache = ? AND expire_le > NOW() AND utilise_le IS NULL'
    );
    $req->execute([hash('sha256', $jeton)]);
    $id = $req->fetchColumn();

    return $id === false ? null : (int) $id;
}

/**
 * Change le mot de passe et invalide tous les jetons du compte.
 *
 * Ne reçoit que l'utilisateur_id, pas le jeton : appelant a déjà dû le
 * vérifier via verifier_jeton_reinitialisation() pour obtenir cet id, cette
 * fonction n'a pas besoin de le revérifier.
 */
function appliquer_reinitialisation(PDO $bdd, int $utilisateur_id, string $nouveau_mdp): void
{
    $bdd->beginTransaction();

    $maj = $bdd->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
    $maj->execute([password_hash($nouveau_mdp, PASSWORD_DEFAULT), $utilisateur_id]);

    // Supprime tous les jetons du compte, pas seulement celui utilisé : un
    // second lien généré plus tôt et non consulté ne doit pas rester valable.
    $bdd->prepare('DELETE FROM reinitialisations_mot_de_passe WHERE utilisateur_id = ?')
        ->execute([$utilisateur_id]);

    $bdd->commit();
}

/**
 * Valide et enregistre une photo de profil téléversée.
 *
 * Renvoie le nom de fichier stocké (à écrire tel quel en base), ou null si
 * aucun fichier n'a été fourni. Lève une exception avec un message
 * utilisateur si le fichier fourni est invalide — l'inscription reste
 * possible sans photo, mais un fichier refusé ne doit pas passer sous silence.
 *
 * Le nom de fichier est régénéré au hasard : ni le nom d'origine, ni son
 * extension déclarée ne sont fiables. Le type réel est confirmé par
 * getimagesize(), qui échoue sur autre chose qu'une image.
 */
function enregistrer_photo_profil(array $fichier): ?string
{
    if (!isset($fichier['error']) || $fichier['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Le téléversement de la photo a échoué.");
    }
    if (!is_uploaded_file($fichier['tmp_name'])) {
        throw new RuntimeException('Fichier de photo invalide.');
    }

    $tailleMax = 2 * 1024 * 1024; // 2 Mo
    if ($fichier['size'] > $tailleMax) {
        throw new RuntimeException('La photo dépasse la taille maximale de 2 Mo.');
    }

    $infos = getimagesize($fichier['tmp_name']);
    if ($infos === false) {
        throw new RuntimeException("Le fichier envoyé n'est pas une image valide.");
    }

    $extensions = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    $type = $infos[2];
    if (!isset($extensions[$type])) {
        throw new RuntimeException('Formats acceptés : JPEG, PNG ou WebP.');
    }

    $dossier = __DIR__ . '/../public/uploads';
    if (!is_dir($dossier) && !mkdir($dossier, 0755, true) && !is_dir($dossier)) {
        throw new RuntimeException("Impossible de préparer le dossier de stockage.");
    }

    $nomFichier = bin2hex(random_bytes(16)) . '.' . $extensions[$type];
    if (!move_uploaded_file($fichier['tmp_name'], $dossier . '/' . $nomFichier)) {
        throw new RuntimeException("Impossible d'enregistrer la photo.");
    }

    return $nomFichier;
}

/**
 * Barre latérale de navigation, partagée par toutes les pages connectées.
 *
 * Centralisée ici plutôt que recopiée dans chaque page : avec sept pages qui
 * l'utilisent, une liste de liens dupliquée aurait fini par diverger (c'était
 * déjà arrivé une fois avec le menu — voir dashboard-nounou.php).
 */
function sidebar_html(PDO $bdd, int $utilisateur_id, string $role, string $page_actif): string
{
    $tableau_de_bord = $role === 'nounou' ? 'dashboard-nounou.php' : 'dashboard-parent.php';

    $liens = $role === 'nounou'
        ? [
            [$tableau_de_bord,        '🏠', 'Tableau de bord'],
            ['mon-profil-nounou.php', '✏️', 'Mon profil'],
            ['index.php',             '🌐', 'Accueil'],
        ]
        : [
            [$tableau_de_bord,     '🏠', 'Tableau de bord'],
            ['mon-enfant.php',     '🧒', 'Mes enfants'],
            ['liste-nounous.php', '🔎', 'Trouver une nounou'],
            ['index.php',          '🌐', 'Accueil'],
        ];

    $non_lus = messages_non_lus_total($bdd, $utilisateur_id);

    $html = '<aside class="dash-sidebar" id="menu">';
    $html .= '<a href="index.php" class="brand"><span class="brand-mark">🍼</span> kiNouGarde</a>';
    $html .= '<nav class="dash-nav">';
    foreach ($liens as [$href, $icone, $libelle]) {
        $actif = $href === $page_actif ? ' active' : '';
        // Aucun serveur SMTP configuré (voir README) : ce badge remplace la
        // notification par e-mail qu'on attendrait à la réception d'un message.
        $badge = ($href === $tableau_de_bord && $non_lus > 0)
            ? sprintf(' <span class="dash-nav-badge">%d</span>', $non_lus)
            : '';
        $html .= sprintf(
            '<a href="%s" class="dash-nav-item%s"><span class="dash-nav-icon">%s</span> %s%s</a>',
            e($href),
            $actif,
            $icone,
            e($libelle),
            $badge
        );
    }
    $html .= '</nav>';
    $html .= '<div class="dash-sidebar-footer">';
    $html .= '<button type="button" id="theme-toggle" class="dash-nav-item" aria-pressed="false">'
        . '<span class="dash-nav-icon">🌙</span> <span id="theme-toggle-label">Mode sombre</span></button>';
    $html .= '<a href="logout.php" class="dash-nav-item dash-nav-danger"><span class="dash-nav-icon">🚪</span> Déconnexion</a>';
    $html .= '</div></aside>';
    $html .= '<div class="dash-overlay" data-menu-overlay></div>';

    return $html;
}

/**
 * Avatar d'un utilisateur : sa photo si elle existe, sinon un rond de
 * couleur avec ses initiales. Sans ce repli, l'absence de photo (aucun
 * formulaire ne permettait d'en téléverser une avant la refonte de
 * septembre 2026) laissait un espace vide dans les listes et les profils.
 *
 * La couleur est dérivée du nom : stable pour une même personne, mais
 * différente d'une nounou à l'autre pour rester repérable dans une liste.
 */
function avatar_html(string $prenom, string $nom, ?string $photo = null, string $taille = 'md'): string
{
    $classe = 'avatar avatar-' . $taille;

    if ($photo !== null && $photo !== '') {
        return sprintf(
            '<img src="uploads/%s" alt="Photo de %s %s" class="%s">',
            e(basename($photo)),
            e($prenom),
            e($nom),
            $classe
        );
    }

    $initiales = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
    $teinte    = crc32($prenom . $nom) % 360;

    return sprintf(
        '<div class="%s" style="background-color: hsl(%d, 62%%, 45%%)" aria-hidden="true">%s</div>',
        $classe,
        $teinte,
        e($initiales)
    );
}
