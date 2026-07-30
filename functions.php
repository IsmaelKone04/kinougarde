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
        'SELECT n.*, u.nom, u.prenom, u.email, u.telephone, u.id AS utilisateur_id
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

function lister_nounous(PDO $bdd): array
{
    return $bdd->query(
        'SELECT n.id, n.ville, n.type_service, n.montant, n.paiement, n.photo_profil,
                u.nom, u.prenom
           FROM nounous n
           JOIN utilisateurs u ON u.id = n.utilisateur_id
          ORDER BY u.nom, u.prenom'
    )->fetchAll();
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
 * Total dû à une nounou.
 *
 * La somme est faite par MySQL, pas en PHP : l'ancienne version chargeait
 * toutes les lignes pour les additionner une à une.
 */
function total_contrats_nounou(PDO $bdd, int $nounou_id): float
{
    $req = $bdd->prepare('SELECT COALESCE(SUM(montant), 0) FROM contrats_emploi WHERE nounou_id = ?');
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

/** Vrai si le compte existe. Évite d'écrire un message vers un destinataire fantôme. */
function compte_existe(PDO $bdd, int $utilisateur_id): bool
{
    $req = $bdd->prepare('SELECT 1 FROM utilisateurs WHERE id = ?');
    $req->execute([$utilisateur_id]);
    return (bool) $req->fetchColumn();
}
