<?php
declare(strict_types=1);

namespace Tests;

/**
 * Régression sur l'absence de limite de tentatives de connexion, corrigée en
 * septembre 2026 (voir login.php). Avant ce correctif, rien n'empêchait un
 * script de tester des mots de passe sans limite.
 */
final class AntiBruteForceTest extends TestCaseAvecBase
{
    public function test_pas_de_blocage_avant_le_seuil(): void
    {
        $email = 'cible@test.local';

        for ($i = 0; $i < MAX_TENTATIVES_CONNEXION - 1; $i++) {
            enregistrer_tentative_echouee($this->bdd, $email, '127.0.0.1');
        }

        self::assertFalse(trop_de_tentatives($this->bdd, $email));
    }

    public function test_blocage_au_seuil(): void
    {
        $email = 'cible@test.local';

        for ($i = 0; $i < MAX_TENTATIVES_CONNEXION; $i++) {
            enregistrer_tentative_echouee($this->bdd, $email, '127.0.0.1');
        }

        self::assertTrue(trop_de_tentatives($this->bdd, $email));
    }

    public function test_le_blocage_ne_depend_pas_de_ladresse_ip(): void
    {
        // Un attaquant distribué sur plusieurs IP ne doit pas contourner la
        // limite : elle porte sur le compte visé, pas sur l'origine.
        $email = 'cible@test.local';

        for ($i = 0; $i < MAX_TENTATIVES_CONNEXION; $i++) {
            enregistrer_tentative_echouee($this->bdd, $email, "10.0.0.{$i}");
        }

        self::assertTrue(trop_de_tentatives($this->bdd, $email));
    }

    public function test_un_autre_compte_nest_pas_affecte(): void
    {
        $vise    = 'cible@test.local';
        $autre   = 'innocent@test.local';

        for ($i = 0; $i < MAX_TENTATIVES_CONNEXION; $i++) {
            enregistrer_tentative_echouee($this->bdd, $vise, '127.0.0.1');
        }

        self::assertFalse(trop_de_tentatives($this->bdd, $autre));
    }

    public function test_reinitialiser_les_tentatives_debloque_le_compte(): void
    {
        $email = 'cible@test.local';

        for ($i = 0; $i < MAX_TENTATIVES_CONNEXION; $i++) {
            enregistrer_tentative_echouee($this->bdd, $email, '127.0.0.1');
        }
        self::assertTrue(trop_de_tentatives($this->bdd, $email));

        reinitialiser_tentatives($this->bdd, $email);

        self::assertFalse(trop_de_tentatives($this->bdd, $email));
    }
}
