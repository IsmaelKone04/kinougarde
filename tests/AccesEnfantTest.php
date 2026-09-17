<?php
declare(strict_types=1);

namespace Tests;

/**
 * Régression sur la faille la plus grave trouvée dans ce projet (voir
 * modifier-enfant.php) : n'importe quel parent pouvait lire et modifier la
 * fiche d'un enfant d'une autre famille en changeant l'id dans l'URL. Le
 * correctif tient dans un seul filtre, enfant_du_parent() — ce test existe
 * pour qu'il ne disparaisse plus jamais sans qu'un test échoue.
 */
final class AccesEnfantTest extends TestCaseAvecBase
{
    public function test_un_parent_ne_peut_pas_lire_la_fiche_dun_autre_parent(): void
    {
        [, $parentA] = $this->creerParent('parentA@test.local');
        [, $parentB] = $this->creerParent('parentB@test.local');

        $req = $this->bdd->prepare(
            'INSERT INTO enfants (parent_id, nom, prenom, age) VALUES (?, ?, ?, ?)'
        );
        $req->execute([$parentB, 'Bamba', 'Awa', 6]);
        $enfant_id = (int) $this->bdd->lastInsertId();

        $resultat = enfant_du_parent($this->bdd, $enfant_id, $parentA);

        self::assertNull($resultat, "un parent a pu accéder à la fiche d'un enfant qui n'est pas le sien");
    }

    public function test_un_parent_peut_lire_sa_propre_fiche_enfant(): void
    {
        [, $parentA] = $this->creerParent('parentA@test.local');

        $req = $this->bdd->prepare(
            'INSERT INTO enfants (parent_id, nom, prenom, age) VALUES (?, ?, ?, ?)'
        );
        $req->execute([$parentA, 'Traoré', 'Awa', 4]);
        $enfant_id = (int) $this->bdd->lastInsertId();

        $resultat = enfant_du_parent($this->bdd, $enfant_id, $parentA);

        self::assertNotNull($resultat);
        self::assertSame('Traoré', $resultat['nom']);
    }

    public function test_un_identifiant_inexistant_renvoie_null(): void
    {
        [, $parentA] = $this->creerParent('parentA@test.local');

        self::assertNull(enfant_du_parent($this->bdd, 999999, $parentA));
    }
}
