<?php
declare(strict_types=1);

namespace Tests;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base commune aux tests qui ont besoin d'une vraie connexion MySQL.
 *
 * Se connecte à `kinougarde_test`, JAMAIS à la base de développement — une
 * mauvaise config ici viderait des données réelles à chaque suite de tests.
 * Le nom de la base de test est volontairement distinct (`_test`) plutôt que
 * configurable, pour qu'aucune variable d'environnement mal réglée ne puisse
 * faire pointer les tests vers `kinougarde`.
 */
abstract class TestCaseAvecBase extends TestCase
{
    protected PDO $bdd;

    private const TABLES_DANS_ORDRE_FK = [
        'reinitialisations_mot_de_passe',
        'tentatives_connexion',
        'avis',
        'messages',
        'contrats_emploi',
        'enfants',
        'nounous',
        'parents',
        'utilisateurs',
    ];

    protected function setUp(): void
    {
        $this->bdd = $this->connexion();
        $this->viderLesTables();
    }

    private function connexion(): PDO
    {
        static $bdd = null;
        if ($bdd === null) {
            $bdd = new PDO(
                'mysql:host=127.0.0.1;dbname=kinougarde_test;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }
        return $bdd;
    }

    /**
     * Table vidée avant chaque test plutôt qu'une transaction ouverte/annulée :
     * plusieurs fonctions testées (ex. appliquer_reinitialisation()) ouvrent
     * elles-mêmes une transaction, et PDO ne supporte pas les transactions
     * imbriquées.
     */
    private function viderLesTables(): void
    {
        $this->bdd->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::TABLES_DANS_ORDRE_FK as $table) {
            $this->bdd->exec("TRUNCATE TABLE `{$table}`");
        }
        $this->bdd->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /** Crée un compte + profil minimal, renvoie [utilisateur_id, profil_id]. */
    protected function creerParent(string $email = 'parent@test.local'): array
    {
        $req = $this->bdd->prepare(
            'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)'
        );
        $req->execute(['Test', 'Parent', $email, password_hash('Demo1234!', PASSWORD_DEFAULT), 'parent']);
        $utilisateur_id = (int) $this->bdd->lastInsertId();

        $req = $this->bdd->prepare('INSERT INTO parents (utilisateur_id, ville) VALUES (?, ?)');
        $req->execute([$utilisateur_id, 'Abidjan']);
        $parent_id = (int) $this->bdd->lastInsertId();

        return [$utilisateur_id, $parent_id];
    }

    /** Crée un compte + profil nounou minimal, renvoie [utilisateur_id, nounou_id]. */
    protected function creerNounou(string $email = 'nounou@test.local'): array
    {
        $req = $this->bdd->prepare(
            'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)'
        );
        $req->execute(['Test', 'Nounou', $email, password_hash('Demo1234!', PASSWORD_DEFAULT), 'nounou']);
        $utilisateur_id = (int) $this->bdd->lastInsertId();

        $req = $this->bdd->prepare(
            'INSERT INTO nounous (utilisateur_id, sexe, ville, type_service, horaires, montant, paiement)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $req->execute([$utilisateur_id, 'femme', 'Abidjan', 'Garde d\'enfants', 'Lun-Ven', 2000, 'heure']);
        $nounou_id = (int) $this->bdd->lastInsertId();

        return [$utilisateur_id, $nounou_id];
    }
}
